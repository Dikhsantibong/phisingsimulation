<?php

namespace App\Enums;

enum BehaviorStatus: string
{
    case Berisiko = 'berisiko';
    case Waspada = 'waspada';
    case Netral = 'netral';
    case TidakMerespons = 'tidak_merespons';

    public function label(): string
    {
        return match ($this) {
            self::Berisiko => 'Berisiko',
            self::Waspada => 'Waspada',
            self::Netral => 'Netral',
            self::TidakMerespons => 'Tidak Merespons',
        };
    }
}
