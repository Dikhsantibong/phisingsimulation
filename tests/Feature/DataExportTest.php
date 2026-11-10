<?php

namespace Tests\Feature;

use App\Enums\RespondentStatus;
use App\Enums\SimulationTheme;
use App\Models\QuestionnaireResult;
use App\Models\Respondent;
use App\Models\SimulationCampaign;
use App\Models\SimulationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_download_streams_csv_with_headers(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create(['email' => 'siswa@example.com']);
        SimulationEvent::factory()->risky()->for($respondent)->create(['last_action' => 'submit']);
        QuestionnaireResult::factory()->for($respondent)->create();

        $response = $this->actingAs($user)->get(route('export.download'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('no_responden', $content);
        $this->assertStringContainsString('session_token', $content);
        $this->assertStringContainsString('behavior_status', $content);
        $this->assertStringContainsString('jenis_tindakan', $content);
        $this->assertStringContainsString('mengetik_form', $content);
        $this->assertStringContainsString('Submit Form (Verifikasi)', $content);
        $this->assertStringContainsString(sprintf('SID-%03d', $respondent->id), $content);
        $this->assertStringContainsString('siswa@example.com', $content);

        $header = explode("\n", $content)[0];
        $this->assertStringNotContainsString('sent_at', $header);
    }

    public function test_index_counts_respondents_whose_questionnaire_is_still_missing(): void
    {
        $user = User::factory()->create();

        $doneNoQuestionnaire = Respondent::factory()->create();
        SimulationEvent::factory()->for($doneNoQuestionnaire)->create();
        $doneNoQuestionnaire->update(['status' => RespondentStatus::CompletedBehavior]);

        $doneWithQuestionnaire = Respondent::factory()->create();
        SimulationEvent::factory()->for($doneWithQuestionnaire)->create();
        QuestionnaireResult::factory()->for($doneWithQuestionnaire)->create();
        $doneWithQuestionnaire->update(['status' => RespondentStatus::Finished]);

        Respondent::factory()->create(['status' => RespondentStatus::Sent]);

        $this->actingAs($user)->get(route('export.index'))
            ->assertInertia(fn (Assert $page) => $page->where('pendingQuestionnaire', 1));
    }

    public function test_export_includes_the_campaign_title_and_assigned_theme(): void
    {
        $user = User::factory()->create();
        $campaign = SimulationCampaign::factory()->create(['title' => 'Gelombang 1 - Kelas XII']);
        $respondent = Respondent::factory()->create([
            'campaign_id' => $campaign->id,
            'theme' => SimulationTheme::Tka->value,
        ]);

        $response = $this->actingAs($user)->get(route('export.download'));
        $content = $response->streamedContent();

        $this->assertStringContainsString('campaign', $content);
        $this->assertStringContainsString('theme', $content);
        $this->assertStringContainsString('Gelombang 1 - Kelas XII', $content);
        $this->assertStringContainsString(SimulationTheme::Tka->value, $content);
        $this->assertStringContainsString($respondent->session_token, $content);
    }

    public function test_each_questionnaire_question_becomes_its_own_column(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create();
        QuestionnaireResult::factory()->for($respondent)->create([
            'knowledge_answers' => ['K1' => 'Upaya penipuan untuk mencuri data pribadi'],
            'attitude_answers' => ['A1' => 4],
            'behavior_answers' => [
                'B1' => 2,
                '_uncategorized' => ['Kelas' => 'XII', 'K6' => ['option-text']],
            ],
        ]);

        $response = $this->actingAs($user)->get(route('export.download'));
        $content = $response->streamedContent();

        $header = explode("\n", $content)[0];
        $this->assertStringContainsString('K1', $header);
        $this->assertStringContainsString('A1', $header);
        $this->assertStringContainsString('B1', $header);
        $this->assertStringContainsString('Kelas', $header);
        $this->assertStringContainsString('K6', $header);
        $this->assertStringNotContainsString('knowledge_answers', $header);
        $this->assertStringNotContainsString('_uncategorized', $content);

        $this->assertStringContainsString('Upaya penipuan untuk mencuri data pribadi', $content);
        $this->assertStringContainsString('option-text', $content);
    }

    public function test_kelas_column_prefers_the_tally_answer_over_the_stored_class_group(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create(['class_group' => 'Default']);
        QuestionnaireResult::factory()->for($respondent)->create([
            'knowledge_answers' => [],
            'attitude_answers' => [],
            'behavior_answers' => ['_uncategorized' => ['Kelas' => 'XII IPA 1']],
        ]);

        $response = $this->actingAs($user)->get(route('export.download'));
        $content = $response->streamedContent();

        $header = explode("\n", $content)[0];
        $this->assertStringContainsString('kelas', $header);
        $this->assertStringNotContainsString('class_group', $header);
        $this->assertStringContainsString('XII IPA 1', $content);
    }

    public function test_kelas_column_falls_back_to_the_stored_class_group_without_a_tally_answer(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create(['class_group' => 'XI IPS 2']);

        $response = $this->actingAs($user)->get(route('export.download'));
        $content = $response->streamedContent();

        $this->assertStringContainsString('XI IPS 2', $content);
    }

    public function test_anonymised_export_excludes_pii(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create(['email' => 'rahasia@example.com']);
        SimulationEvent::factory()->for($respondent)->create();

        $response = $this->actingAs($user)->get(route('export.download', ['anonymise' => 1]));
        $content = $response->streamedContent();

        $this->assertStringNotContainsString('rahasia@example.com', $content);
        $this->assertStringNotContainsString(',email,', $content);
        $this->assertStringContainsString($respondent->session_token, $content);
    }

    public function test_export_requires_authentication(): void
    {
        $this->get(route('export.download'))->assertRedirect(route('login'));
    }
}
