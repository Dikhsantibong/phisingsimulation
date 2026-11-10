<?php

namespace App\Enums;

enum SimulationTheme: string
{
    case Elearning = 'elearning';
    case Tka = 'tka';
    case PengumumanNilaiTugas = 'pengumuman_nilai_tugas';

    public function label(): string
    {
        return match ($this) {
            self::Elearning => 'Sistem Pembelajaran Digital',
            self::Tka => 'Portal Tes Kemampuan Akademik (TKA)',
            self::PengumumanNilaiTugas => 'Pengumuman Nilai/Tugas',
        };
    }

    /**
     * A believable URL path segment for this theme's pretext — used as the
     * default campaign slug so a shared link reads like a plausible portal
     * path (e.g. "verifikasi-akun-tka-x7k2p9"), not research jargon.
     */
    public function slugHint(): string
    {
        return match ($this) {
            self::Elearning => 'verifikasi-akun-pembelajaran',
            self::Tka => 'verifikasi-akun-tka',
            self::PengumumanNilaiTugas => 'cek-nilai-tugas',
        };
    }
}
