<?php

namespace App\Enums;

enum ReminderType: string
{
    /** Respondent never opened the simulation link at all before the deadline. */
    case SimulasiDiabaikan = 'simulasi_diabaikan';

    /** Respondent finished the portal (submit/tolak/lapor) but hasn't started the questionnaire yet — early heads-up. */
    case KuesionerBelumSelesai = 'kuesioner_belum_selesai';

    /** The original response deadline has passed and the questionnaire is still not done — final notice. */
    case BatasWaktuTerlewat = 'batas_waktu_terlewat';

    public function label(): string
    {
        return match ($this) {
            self::SimulasiDiabaikan => 'Belum Akses Simulasi',
            self::KuesionerBelumSelesai => 'Simulasi Selesai, Kuesioner Belum Diisi',
            self::BatasWaktuTerlewat => 'Batas Waktu Terlewat - Kuesioner Belum Diisi',
        };
    }
}
