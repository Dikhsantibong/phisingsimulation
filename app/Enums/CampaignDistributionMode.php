<?php

namespace App\Enums;

enum CampaignDistributionMode: string
{
    case Link = 'link';
    case Targeted = 'targeted';

    public function label(): string
    {
        return match ($this) {
            self::Link => 'Link Publik',
            self::Targeted => 'Email/WA Manual',
        };
    }
}
