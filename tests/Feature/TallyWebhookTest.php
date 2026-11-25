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
        SimulationEvent::factory()->for($respondent)->create(['response_at' => now()]);

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

    /**
     * A respondent whose simulation_event row only has sent_at (message was
     * sent, but they never actually acted on the portal — no response_at)
     * must be marked as "questionnaire only", never as having completed the
     * simulation too. A simulation_event row exists the moment a message is
     * sent, so its mere presence must not be read as "they did the
     * simulation".
     */
    public function test_a_submission_without_any_portal_response_is_marked_questionnaire_only(): void
    {
        $respondent = Respondent::factory()->create();
        SimulationEvent::factory()->for($respondent)->create(['response_at' => null]);

        $this->postJson(route('webhooks.tally'), $this->payload($respondent->session_token, 'sub_no_portal'))
            ->assertOk();

        $this->assertSame(RespondentStatus::CompletedQuestionnaire, $respondent->fresh()->status);
    }

    /**
     * A respondent who was never sent a simulation at all (no
     * simulation_event row whatsoever) must also be marked "questionnaire
     * only", not crash on a null simulationEvent.
     */
    public function test_a_submission_with_no_simulation_event_at_all_is_marked_questionnaire_only(): void
    {
        $respondent = Respondent::factory()->create();

        $this->postJson(route('webhooks.tally'), $this->payload($respondent->session_token, 'sub_no_event'))
            ->assertOk();

        $this->assertSame(RespondentStatus::CompletedQuestionnaire, $respondent->fresh()->status);
    }

    public function test_the_kelas_question_updates_the_respondents_class_group(): void
    {
        $respondent = Respondent::factory()->create(['class_group' => 'Default']);

        $this->postJson(route('webhooks.tally'), $this->payload($respondent->session_token, 'sub_kelas', [
            ['key' => '', 'label' => 'Kelas', 'value' => 'XII IPA 1'],
        ]))->assertOk();

        $this->assertSame('XII IPA 1', $respondent->fresh()->class_group);
    }

    public function test_short_keys_without_an_underscore_are_still_categorised(): void
    {
        $respondent = Respondent::factory()->create();

        $this->postJson(route('webhooks.tally'), [
            'eventId' => 'evt_2',
            'data' => [
                'submissionId' => 'sub_short_keys',
                'fields' => [
                    ['key' => 'session_token', 'label' => 'session_token', 'value' => $respondent->session_token],
                    ['key' => 'K1', 'label' => 'Apa yang dimaksud dengan phishing?', 'value' => 'B'],
                    ['key' => 'A1', 'label' => 'Seberapa yakin Anda bisa mengenali email phishing?', 'value' => 4],
                    ['key' => 'B1', 'label' => 'Apa yang Anda lakukan saat menerima email mencurigakan?', 'value' => 'Melapor'],
                ],
            ],
        ])->assertOk();

        $result = $respondent->questionnaireResult()->first();
        $this->assertSame('B', $result->knowledge_answers['K1']);
        $this->assertSame(4, $result->attitude_answers['A1']);
        $this->assertSame('Melapor', $result->behavior_answers['B1']);
    }

    public function test_multiple_choice_answers_are_resolved_from_option_id_to_option_text(): void
    {
        $respondent = Respondent::factory()->create();

        $this->postJson(route('webhooks.tally'), [
            'eventId' => 'evt_3',
            'data' => [
                'submissionId' => 'sub_mc',
                'fields' => [
                    ['key' => 'session_token', 'label' => 'session_token', 'value' => $respondent->session_token],
                    [
                        'key' => 'K1',
                        'label' => 'Apa yang dimaksud dengan phishing?',
                        'value' => ['opt-b'],
                        'options' => [
                            ['id' => 'opt-a', 'text' => 'Serangan virus komputer'],
                            ['id' => 'opt-b', 'text' => 'Upaya penipuan untuk mencuri data pribadi dengan menyamar sebagai pihak terpercaya'],
                            ['id' => 'opt-c', 'text' => 'Pembaruan software resmi'],
                        ],
                    ],
                ],
            ],
        ])->assertOk();

        $result = $respondent->questionnaireResult()->first();
        $this->assertSame(
            'Upaya penipuan untuk mencuri data pribadi dengan menyamar sebagai pihak terpercaya',
            $result->knowledge_answers['K1'],
        );
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
