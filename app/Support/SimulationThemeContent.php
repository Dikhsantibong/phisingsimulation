<?php

namespace App\Support;

use App\Enums\SimulationTheme;

/**
 * Single source of pretext copy for every channel (web portal, email, WhatsApp).
 *
 * Adding a new theme = add one case to App\Enums\SimulationTheme and one
 * entry here. No controller, Blade view, or React page needs to change.
 */
class SimulationThemeContent
{
    /**
     * @return array<string, string>
     */
    public static function for(SimulationTheme $theme): array
    {
        return match ($theme) {
            SimulationTheme::Elearning => [
                'org_name' => 'Pusat Layanan Pembelajaran Digital',
                'portal_title' => 'Sistem Pembelajaran Digital',
                'portal_subtitle' => 'Masuk untuk memverifikasi identitas Anda',
                'id_field_label' => 'Email atau NIS',
                'lead' => 'Sistem mendeteksi aktivitas login tidak biasa pada akun pembelajaran digital Anda dari perangkat yang belum dikenal.',
                'warning_note' => 'Jika Anda tidak melakukan aktivitas ini, tetap lakukan verifikasi agar akun Anda tidak dinonaktifkan dalam 24 jam.',
                'cta_primary_label' => 'Verifikasi Akun Sekarang',
                'cta_secondary_label' => 'Tolak Aktivitas Ini',
                'email_subject' => 'Peringatan Keamanan: Aktivitas Tidak Biasa pada Akun Pembelajaran Digital Anda',
                'scenario_summary' => 'email meniru Pusat Layanan Pembelajaran Digital dengan peringatan login tidak biasa.',
            ],
            SimulationTheme::Tka => [
                'org_name' => 'Portal Tes Kemampuan Akademik (TKA)',
                'portal_title' => 'Portal Tes Kemampuan Akademik',
                'portal_subtitle' => 'Masuk untuk memverifikasi akun peserta Anda',
                'id_field_label' => 'Email atau Nomor Peserta',
                'lead' => 'Sistem mendeteksi aktivitas login tidak biasa pada akun TKA Anda dari perangkat yang belum dikenal.',
                'warning_note' => 'Akun yang tidak diverifikasi berisiko tidak dapat mengikuti jadwal TKA. Segera verifikasi sebelum 24 jam ke depan.',
                'cta_primary_label' => 'Verifikasi Akun Sekarang',
                'cta_secondary_label' => 'Tolak Aktivitas Ini',
                'email_subject' => 'Peringatan Keamanan: Aktivitas Tidak Biasa pada Akun TKA Anda',
                'scenario_summary' => 'email meniru Portal Tes Kemampuan Akademik dengan peringatan akun bermasalah menjelang jadwal tes.',
            ],
            SimulationTheme::PengumumanNilaiTugas => [
                'org_name' => 'Sistem Informasi Akademik',
                'portal_title' => 'Sistem Informasi Akademik',
                'portal_subtitle' => 'Masuk untuk melihat pengumuman nilai/tugas Anda',
                'id_field_label' => 'Email atau NIS',
                'lead' => 'Nilai/tugas terbaru Anda sudah tersedia, namun akses ke akun Anda memerlukan verifikasi ulang karena perubahan sistem.',
                'warning_note' => 'Jika tidak diverifikasi, akses ke nilai/tugas Anda dapat tertunda. Segera verifikasi dalam 24 jam.',
                'cta_primary_label' => 'Lihat Nilai/Tugas',
                'cta_secondary_label' => 'Tolak Aktivitas Ini',
                'email_subject' => 'Pengumuman Nilai/Tugas Terbaru - Verifikasi Akun Diperlukan',
                'scenario_summary' => 'email meniru pengumuman nilai/tugas yang mendesak Anda login ulang untuk "melihat hasil".',
            ],
        };
    }
}
