<?php

namespace App\Services;

use App\Enums\DeviceType;

/**
 * Lightweight, dependency-free User-Agent parser.
 *
 * Extracts only coarse device / OS / browser signals needed as study features.
 * It never stores the raw User-Agent string.
 */
class UserAgentParser
{
    /**
     * Parse a raw User-Agent string into study features.
     *
     * @return array{device_type: DeviceType, os_name: ?string, browser_name: ?string}
     */
    public function parse(?string $userAgent): array
    {
        $userAgent ??= '';

        return [
            'device_type' => $this->deviceType($userAgent),
            'os_name' => $this->osName($userAgent),
            'browser_name' => $this->browserName($userAgent),
        ];
    }

    private function deviceType(string $ua): DeviceType
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua)) {
            return DeviceType::Tablet;
        }

        if (preg_match('/Mobi|Android.*Mobile|iPhone|iPod|IEMobile|Opera Mini|BlackBerry/i', $ua)) {
            return DeviceType::Mobile;
        }

        return DeviceType::Desktop;
    }

    private function osName(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/Windows NT/i', $ua) => 'Windows',
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'iOS',
            (bool) preg_match('/Android/i', $ua) => 'Android',
            (bool) preg_match('/Mac OS X/i', $ua) => 'macOS',
            (bool) preg_match('/Linux/i', $ua) => 'Linux',
            default => null,
        };
    }

    private function browserName(string $ua): ?string
    {
        return match (true) {
            (bool) preg_match('/Edg[eiOSA]*\//i', $ua) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua) => 'Opera',
            (bool) preg_match('/SamsungBrowser/i', $ua) => 'Samsung Internet',
            (bool) preg_match('/Firefox\//i', $ua) => 'Firefox',
            (bool) preg_match('/Chrome\//i', $ua) => 'Chrome',
            (bool) preg_match('/Safari\//i', $ua) => 'Safari',
            default => null,
        };
    }
}
