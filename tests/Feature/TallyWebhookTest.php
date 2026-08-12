<?php

namespace Tests\Feature;

use App\Enums\CompletionStatus;
use App\Enums\RespondentStatus;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TallyWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array<string, mixed>>  $extraFields
     * @return array<string, mixed>
     */
    private function payload(string $token, string $submissionId, array $extraFields = []): array
    {
        return [
            'eventId' => 'evt_1',
            'data' => [
                'submissionId' => $submissionId,
                'fields' => array_merge([
                    ['key' => 'session_token', 'label' => 'session_token', 'value' => $token],
                    ['key' => 'k_1', 'label' => 'Pengetahuan 1', 'value' => 4],
                    ['key' => 'a_1', 'label' => 'Sikap 1', 'value' => 5],
                    ['key' => 'b_1', 'label' => 'Perilaku 1', 'value' => 3],
                ], $extraFields),
            ],
        ];
    }

    public function test_valid_submission_is_linked_and_categorised(): void
    {
        $respondent = Respondent::factory()->create();
        SimulationEvent::factory()->for($respondent)->create();

        $this->postJson(route('webhooks.tally'), $this->payload($respondent->session_token, 'sub_1'))
            ->assertOk();

        $result = $respondent->questionnaireResult()->first();
        $this->assertNotNull($result);
        $this->assertSame(CompletionStatus::Selesai, $result->completion_status);
        $this->assertSame(4, $result->knowledge_answers['k_1']);
        $this->assertSame(5, $result->attitude_answers['a_1']);
        $this->assertSame(3, $result->behavior_answers['b_1']);
        $this->assertSame(RespondentStatus::Finished, $respondent->fresh()->status);
    }

    public function test_webhook_is_idempotent_on_submission_id(): void
    {
        $respondent = Respondent::factory()->create();

        $payload = $this->payload($respondent->session_token, 'sub_dup');
        $this->postJson(route('webhooks.tally'), $payload)->assertOk();
        $this->postJson(route('webhooks.tally'), $payload)->assertOk();

        $this->assertSame(1, $respondent->questionnaireResult()->count());
    }

    public function test_uncategorised_fields_are_preserved(): void
    {
        $respondent = Respondent::factory()->create();

        $this->postJson(route('webhooks.tally'), $this->payload($respondent->session_token, 'sub_x', [
            ['key' => 'random_note', 'label' => 'Catatan', 'value' => 'halo'],
        ]))->assertOk();

        $result = $respondent->questionnaireResult()->first();
        $this->assertSame('halo', $result->behavior_answers['_uncategorized']['random_note']);
    }

    public function test_unknown_token_returns_not_found(): void
    {
        $this->postJson(route('webhooks.tally'), $this->payload('00000000-0000-0000-0000-000000000000', 'sub_none'))
            ->assertNotFound();
    }

    public function test_missing_session_token_is_unprocessable(): void
    {
        $this->postJson(route('webhooks.tally'), [
            'data' => ['submissionId' => 'sub_1', 'fields' => []],
        ])->assertStatus(422);
    }

    public function test_signature_is_enforced_when_secret_configured(): void
    {
        config()->set('services.simulation.tally_signing_secret', 'shh');
        $respondent = Respondent::factory()->create();
        $payload = $this->payload($respondent->session_token, 'sub_sig');

        // No signature header -> rejected.
        $this->postJson(route('webhooks.tally'), $payload)->assertStatus(401);

        // Correct signature -> accepted.
        $body = json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $body, 'shh', true));

        $this->call('POST', route('webhooks.tally'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_TALLY_SIGNATURE' => $signature,
        ], $body)->assertOk();
    }
}
