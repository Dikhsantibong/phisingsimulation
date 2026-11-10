<?php

namespace Tests\Unit;

use App\Enums\BehaviorStatus;
use App\Support\AwarenessScorer;
use Tests\TestCase;

class AwarenessScorerTest extends TestCase
{
    public function test_knowledge_score_counts_correct_answers_out_of_eight(): void
    {
        $answers = [
            'K1' => 'Upaya penipuan untuk mencuri data pribadi dengan menyamar sebagai pihak terpercaya', // correct
            'K2' => 'URL yang menyerupai situs asli tetapi berbeda sedikit', // correct
            'K3' => 'Mengklik link dan memasukkan data', // wrong
            'K4' => 'Phishing melalui pesan SMS atau WhatsApp', // correct
            'K5' => 'Memeriksa alamat URL tujuan', // correct
            'K6' => 'Menambahkan lapisan keamanan kedua selain password', // correct
            'K7' => 'Phishing massal melalui email', // wrong
            'K8' => 'Memeriksa kebenaran informasi melalui kanal resmi sekolah', // correct
        ];

        $this->assertSame(75.0, AwarenessScorer::knowledgeScore($answers));
    }

    public function test_knowledge_score_is_null_when_no_knowledge_answers_present(): void
    {
        $this->assertNull(AwarenessScorer::knowledgeScore(['A1' => 4]));
    }

    public function test_attitude_score_divides_the_raw_sum_by_forty(): void
    {
        $answers = ['A1' => 4, 'A2' => 4, 'A3' => 4, 'A4' => 3, 'A5' => 3, 'A6' => 3, 'A7' => 4, 'A8' => 3];
        // sum = 28, (28/40)*100 = 70
        $this->assertSame(70.0, AwarenessScorer::attitudeScore($answers));
    }

    public function test_behavior_questionnaire_score_divides_the_raw_sum_by_forty(): void
    {
        $answers = ['B1' => 3, 'B2' => 3, 'B3' => 3, 'B4' => 3, 'B5' => 3, 'B6' => 3, 'B7' => 3, 'B8' => 3];
        // sum = 24, (24/40)*100 = 60
        $this->assertSame(60.0, AwarenessScorer::behaviorQuestionnaireScore($answers));
    }

    public function test_simulation_score_maps_behavior_status(): void
    {
        $this->assertSame(20, AwarenessScorer::simulationScore(BehaviorStatus::Berisiko));
        $this->assertSame(60, AwarenessScorer::simulationScore(BehaviorStatus::Netral));
        $this->assertSame(100, AwarenessScorer::simulationScore(BehaviorStatus::Waspada));
        $this->assertSame(100, AwarenessScorer::simulationScore(BehaviorStatus::TidakMerespons));
        $this->assertNull(AwarenessScorer::simulationScore(null));
    }

    public function test_awareness_total_is_the_average_of_the_four_scores(): void
    {
        // The worked example confirmed with the user: 75, 70, 60, 60 -> 66.25 -> Sedang.
        $total = AwarenessScorer::awarenessTotal(75.0, 70.0, 60.0, 60);

        $this->assertSame(66.25, $total);
        $this->assertSame('Sedang', AwarenessScorer::awarenessLevel($total));
    }

    public function test_awareness_total_is_null_unless_all_four_components_are_present(): void
    {
        $this->assertNull(AwarenessScorer::awarenessTotal(75.0, 70.0, 60.0, null));
        $this->assertNull(AwarenessScorer::awarenessTotal(null, 70.0, 60.0, 60));
    }

    public function test_awareness_level_boundaries(): void
    {
        $this->assertSame('Tinggi', AwarenessScorer::awarenessLevel(70));
        $this->assertSame('Sedang', AwarenessScorer::awarenessLevel(50));
        $this->assertSame('Sedang', AwarenessScorer::awarenessLevel(69));
        $this->assertSame('Rendah', AwarenessScorer::awarenessLevel(49));
        $this->assertSame('Rendah', AwarenessScorer::awarenessLevel(0));
    }
}
