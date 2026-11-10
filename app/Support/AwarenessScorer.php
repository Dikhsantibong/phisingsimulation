<?php

namespace App\Support;

use App\Enums\BehaviorStatus;

/**
 * Implements "Rumus perhitungan KAB dan simulasi" from the research
 * instrument: per-dimension 0-100 scores, a simulation score from the
 * respondent's actual phishing-simulation behavior, and the combined
 * "Tingkat Kesadaran" (awareness level) classification.
 */
class AwarenessScorer
{
    private const SIMULATION_SCORES = [
        BehaviorStatus::Berisiko->value => 20,   // Risky Behavior
        BehaviorStatus::Netral->value => 60,     // Neutral Behavior
        BehaviorStatus::Waspada->value => 100,   // Aware Behavior
        BehaviorStatus::TidakMerespons->value => 100, // treated as Aware, per instrument
    ];

    /**
     * Skor Knowledge = (Jumlah Jawaban Benar / 8) x 100.
     * Null when the respondent has no Knowledge answers at all (questionnaire not done).
     *
     * @param  array<string, mixed>  $answers  Flattened K/A/B answers, keyed by question (e.g. "K1").
     */
    public static function knowledgeScore(array $answers): ?float
    {
        $knowledgeKeys = array_filter(array_keys($answers), fn (string $key) => preg_match('/^k\d+$/i', $key) === 1);

        if ($knowledgeKeys === []) {
            return null;
        }

        $correct = 0;
        foreach ($knowledgeKeys as $key) {
            if (KnowledgeAnswerKey::isCorrect($key, $answers[$key])) {
                $correct++;
            }
        }

        return round(($correct / KnowledgeAnswerKey::TOTAL_QUESTIONS) * 100, 2);
    }

    /**
     * Skor Attitude = (Total Skor Attitude / 40) x 100.
     * Null when the respondent has no Attitude answers at all.
     *
     * @param  array<string, mixed>  $answers
     */
    public static function attitudeScore(array $answers): ?float
    {
        return self::likertScore($answers, 'a', maxPerQuestion: 5, questionCount: 8);
    }

    /**
     * Skor Behavior_Kuesioner = (Total Skor Behavior / 40) x 100.
     * Null when the respondent has no Behavior answers at all.
     *
     * @param  array<string, mixed>  $answers
     */
    public static function behaviorQuestionnaireScore(array $answers): ?float
    {
        return self::likertScore($answers, 'b', maxPerQuestion: 5, questionCount: 8);
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private static function likertScore(array $answers, string $prefix, int $maxPerQuestion, int $questionCount): ?float
    {
        $pattern = '/^'.preg_quote($prefix, '/').'\d+$/i';
        $keys = array_filter(array_keys($answers), fn (string $key) => preg_match($pattern, $key) === 1);

        if ($keys === []) {
            return null;
        }

        $total = 0;
        foreach ($keys as $key) {
            $total += is_numeric($answers[$key]) ? (float) $answers[$key] : 0;
        }

        $maxTotal = $maxPerQuestion * $questionCount;

        return round(($total / $maxTotal) * 100, 2);
    }

    /**
     * Skor Simulasi, from the respondent's final behavior category.
     * Null when the respondent never reached a final behavior outcome.
     */
    public static function simulationScore(?BehaviorStatus $status): ?int
    {
        if ($status === null) {
            return null;
        }

        return self::SIMULATION_SCORES[$status->value];
    }

    /**
     * Tingkat Kesadaran = rata-rata (Knowledge + Attitude + Behavior + Simulasi) / 4.
     * Null unless all four component scores are available.
     */
    public static function awarenessTotal(?float $knowledge, ?float $attitude, ?float $behavior, ?int $simulation): ?float
    {
        if ($knowledge === null || $attitude === null || $behavior === null || $simulation === null) {
            return null;
        }

        return round(($knowledge + $attitude + $behavior + $simulation) / 4, 2);
    }

    /**
     * "Tinggi" (70-100) / "Sedang" (50-69) / "Rendah" (0-49).
     */
    public static function awarenessLevel(?float $total): ?string
    {
        if ($total === null) {
            return null;
        }

        return match (true) {
            $total >= 70 => 'Tinggi',
            $total >= 50 => 'Sedang',
            default => 'Rendah',
        };
    }
}
