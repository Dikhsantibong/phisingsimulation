<?php

namespace App\Enums;

enum DeviceType: string
{
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Desktop = 'desktop';

    public function label(): string
    {
        return match ($this) {
            self::Mobile => 'Ponsel',
            self::Tablet => 'Tablet',
            self::Desktop => 'Desktop',
        };
    }
}
