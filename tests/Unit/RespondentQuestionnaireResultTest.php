<?php

namespace Tests\Unit;

use App\Models\QuestionnaireResult;
use App\Models\Respondent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RespondentQuestionnaireResultTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A respondent can legitimately submit the Tally form more than once
     * (re-submission, or re-imported under a different Tally submission id).
     * Each submission gets its own row, but scoring/export must always use
     * the latest one — not whichever row the database happens to return
     * first.
     */
    public function test_it_resolves_to_the_most_recently_submitted_questionnaire_result(): void
    {
        $respondent = Respondent::factory()->create();

        $first = QuestionnaireResult::factory()->for($respondent)->create([
            'tally_submission_id' => 'first-submission',
            'knowledge_answers' => ['K1' => 'Jawaban pertama'],
            'submitted_at' => now()->subMinutes(10),
        ]);

        $second = QuestionnaireResult::factory()->for($respondent)->create([
            'tally_submission_id' => 'second-submission',
            'knowledge_answers' => ['K1' => 'Jawaban kedua'],
            'submitted_at' => now(),
        ]);

        $resolved = $respondent->fresh()->questionnaireResult;

        $this->assertSame($second->id, $resolved->id);
        $this->assertSame('Jawaban kedua', $resolved->knowledge_answers['K1']);
        $this->assertNotSame($first->id, $resolved->id);
    }
}
