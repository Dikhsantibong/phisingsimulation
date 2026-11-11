<?php

namespace Tests\Feature;

use App\Enums\RespondentStatus;
use App\Models\QuestionnaireResult;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TallyImportTest extends TestCase
{
    use RefreshDatabase;

    private function csvFile(string $contents, string $name = 'tally.csv'): UploadedFile
    {
        Storage::fake('local');
        $path = sys_get_temp_dir().'/'.$name;
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    public function test_matching_rows_fill_in_questionnaire_results(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create();

        $csv = "Submission ID,session_token,K1,A1,B1\n"
            ."sub_1,{$respondent->session_token},Upaya penipuan untuk mencuri data pribadi,4,2\n";

        $this->actingAs($user)
            ->post(route('export.import-tally'), ['file' => $this->csvFile($csv)])
            ->assertRedirect();

        $result = $respondent->questionnaireResult()->first();
        $this->assertNotNull($result);
        $this->assertSame('Upaya penipuan untuk mencuri data pribadi', $result->knowledge_answers['K1']);
        $this->assertSame('4', $result->attitude_answers['A1']);
        $this->assertSame('2', $result->behavior_answers['B1']);
        $this->assertSame(RespondentStatus::CompletedQuestionnaire, $respondent->fresh()->status);
    }

    public function test_respondent_with_a_finished_simulation_event_is_marked_finished(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create();
        SimulationEvent::factory()->for($respondent)->create();

        $csv = "Submission ID,session_token,K1\n"
            ."sub_1,{$respondent->session_token},jawaban\n";

        $this->actingAs($user)->post(route('export.import-tally'), ['file' => $this->csvFile($csv)]);

        $this->assertSame(RespondentStatus::Finished, $respondent->fresh()->status);
    }

    public function test_unknown_session_tokens_are_skipped_and_counted(): void
    {
        $user = User::factory()->create();

        $csv = "Submission ID,session_token,K1\n"
            ."sub_1,00000000-0000-0000-0000-000000000000,jawaban\n";

        $this->actingAs($user)->post(route('export.import-tally'), ['file' => $this->csvFile($csv)])
            ->assertRedirect();

        $this->assertSame(0, QuestionnaireResult::count());
    }

    public function test_missing_session_token_column_is_rejected(): void
    {
        $user = User::factory()->create();

        $csv = "Submission ID,name\nsub_1,Budi\n";

        $this->actingAs($user)->post(route('export.import-tally'), ['file' => $this->csvFile($csv)])
            ->assertRedirect();

        $this->assertSame(0, QuestionnaireResult::count());
    }

    public function test_importing_the_same_submission_twice_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create();

        $csv = "Submission ID,session_token,K1\n"
            ."sub_1,{$respondent->session_token},jawaban\n";

        $this->actingAs($user)->post(route('export.import-tally'), ['file' => $this->csvFile($csv)]);
        $this->actingAs($user)->post(route('export.import-tally'), ['file' => $this->csvFile($csv)]);

        $this->assertSame(1, $respondent->questionnaireResult()->count());
    }

    public function test_two_submissions_for_the_same_respondent_resolve_to_the_latest_by_real_submitted_at(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create();

        $csv = "Submission ID,\"Submitted at\",session_token,K1\n"
            ."sub_first,2026-11-11 02:50:16,{$respondent->session_token},Jawaban pertama\n"
            ."sub_second,2026-11-11 02:55:49,{$respondent->session_token},Jawaban kedua\n";

        $this->actingAs($user)->post(route('export.import-tally'), ['file' => $this->csvFile($csv)]);

        $this->assertSame(2, $respondent->questionnaireResult()->getRelated()->where('respondent_id', $respondent->id)->count());

        $resolved = $respondent->fresh()->questionnaireResult;
        $this->assertSame('Jawaban kedua', $resolved->knowledge_answers['K1']);
        $this->assertSame('2026-11-11 02:55:49', $resolved->submitted_at->format('Y-m-d H:i:s'));
    }

    public function test_the_kelas_column_updates_the_respondents_class_group(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create(['class_group' => 'Default']);

        $csv = "Submission ID,session_token,kelas,Kelas,K1\n"
            ."sub_1,{$respondent->session_token},,XII IPA 1,jawaban\n";

        $this->actingAs($user)->post(route('export.import-tally'), ['file' => $this->csvFile($csv)]);

        $this->assertSame('XII IPA 1', $respondent->fresh()->class_group);
    }

    public function test_an_xlsx_export_from_tally_is_imported_the_same_way_as_csv(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create(['session_token' => 'fixture-token-aaa']);

        $file = new UploadedFile(
            base_path('tests/Fixtures/sample-tally-export.xlsx'),
            'sample-tally-export.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $this->actingAs($user)
            ->post(route('export.import-tally'), ['file' => $file])
            ->assertRedirect();

        $result = $respondent->questionnaireResult()->first();
        $this->assertNotNull($result);
        $this->assertSame('Jawaban Benar Fixture', $result->knowledge_answers['K1']);
        $this->assertSame('4', $result->attitude_answers['A1']);
        $this->assertSame('2', $result->behavior_answers['B1']);
    }

    public function test_import_requires_authentication(): void
    {
        $csv = "Submission ID,session_token,K1\nsub_1,token,jawaban\n";

        $this->post(route('export.import-tally'), ['file' => $this->csvFile($csv)])
            ->assertRedirect(route('login'));
    }
}
