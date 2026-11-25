<?php

namespace App\Enums;

enum RespondentStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Clicked = 'clicked';
    case CompletedBehavior = 'completed_behavior';
    case CompletedQuestionnaire = 'completed_questionnaire';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Dikirim',
            self::Sent => 'Terkirim',
            self::Clicked => 'Akses Link',
            self::CompletedBehavior => 'Selesai Simulasi',
            // Distinct from Finished on purpose: this respondent filled the
            // questionnaire without ever going through the simulation
            // portal (e.g. a direct Tally link, or a resend that skipped
            // straight to the questionnaire) — must read differently from
            // "did both", not collapse into the same label.
            self::CompletedQuestionnaire => 'Hanya Kuesioner (Tanpa Simulasi)',
            self::Finished => 'Selesai Kuesioner',
        };
    }
}
