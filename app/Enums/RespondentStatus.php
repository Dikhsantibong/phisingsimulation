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
            self::Sent => 'Email Terkirim',
            self::Clicked => 'Tautan Diklik',
            self::CompletedBehavior => 'Simulasi Selesai',
            self::CompletedQuestionnaire => 'Kuesioner Selesai',
            self::Finished => 'Tuntas',
        };
    }
}
