<?php

namespace App\Enums;

enum CompletionStatus: string
{
    case Selesai = 'selesai';
    case BelumSelesai = 'belum_selesai';

    public function label(): string
    {
        return match ($this) {
            self::Selesai => 'Selesai',
            self::BelumSelesai => 'Belum Selesai',
        };
    }
}
