<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * The correct answer text for each Knowledge question (K1-K8), used to grade
 * a respondent's questionnaire answers for the Knowledge score formula.
 *
 * Source: Tabel 3.5 "Pertanyaan Dimensi Knowledge" (skripsi instrument).
 * Matched against the resolved Tally answer text, case/whitespace-insensitive.
 */
class KnowledgeAnswerKey
{
    /**
     * @var array<string, string>
     */
    private const ANSWERS = [
        'K1' => 'Upaya penipuan untuk mencuri data pribadi dengan menyamar sebagai pihak terpercaya',
        'K2' => 'URL yang menyerupai situs asli tetapi berbeda sedikit',
        'K3' => 'Tidak mengklik link dan memverifikasi langsung ke sumber resmi',
        'K4' => 'Phishing melalui pesan SMS atau WhatsApp',
        'K5' => 'Memeriksa alamat URL tujuan',
        'K6' => 'Menambahkan lapisan keamanan kedua selain password',
        'K7' => 'Phishing yang ditargetkan pada individu atau kelompok tertentu dengan informasi personal',
        'K8' => 'Memeriksa kebenaran informasi melalui kanal resmi sekolah',
    ];

    /**
     * Total number of Knowledge questions (the formula's fixed denominator).
     */
    public const TOTAL_QUESTIONS = 8;

    /**
     * Whether the given answer matches the correct option for a question key
     * (e.g. "K1"). Unknown question keys never count as correct.
     */
    public static function isCorrect(string $questionKey, mixed $answer): bool
    {
        $expected = self::ANSWERS[strtoupper($questionKey)] ?? null;

        if ($expected === null || ! is_string($answer)) {
            return false;
        }

        return Str::lower(trim($answer)) === Str::lower(trim($expected));
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::ANSWERS;
    }
}
