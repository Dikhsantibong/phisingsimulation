<?php

namespace App\Http\Controllers\Researcher;

use App\Enums\ReminderType;
use App\Http\Controllers\Controller;
use App\Models\ReminderLog;
use App\Services\FonnteService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class SendWAReminderController extends Controller
{
    /**
     * Send a WhatsApp message to the respondent based on the reminder type.
     */
    public function __invoke(ReminderLog $reminder, FonnteService $fonnte): RedirectResponse
    {
        $respondent = $reminder->respondent;

        if (blank($respondent->whatsapp_number)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Gagal: Responden tidak memiliki nomor WhatsApp.']);

            return back();
        }

        $message = '';
        if ($reminder->reminder_type === ReminderType::SimulasiDiabaikan) {
            // Extend the deadline by 24 hours so they can still access the simulation email link
            if ($respondent->expires_at && now()->greaterThan($respondent->expires_at)) {
                $respondent->update(['expires_at' => now()->addHours(24)]);
            }

            $url = route('simulation.access', ['respondent' => $respondent->session_token]);

            // Build the simulation message identical to the email (but plain text for WA)
            $message = "*Pusat Layanan Pembelajaran*\n\n"
                     .'Halo'.($respondent->name ? " {$respondent->name}" : '').",\n\n"
                     .'Kami dari tim keamanan ingin memberitahukan bahwa terdapat upaya percobaan login atau '
                     ."aktivitas mencurigakan pada akun pembelajaran digital Anda.\n\n"
                     .'Tautan simulasi di bawah ini telah kami perpanjang waktu aksesnya. '
                     ."Mohon segera masuk dan amankan data Anda.\n\n"
                     ."👉 {$url}\n\n";
        } else {
            // Questionnaire reminder
            $url = route('simulation.reveal', ['respondent' => $respondent->session_token]);
            $tallyUrl = config('services.simulation.tally_url');
            // If they want to just show the reveal page again, or provide direct tally URL:
            $formUrl = $tallyUrl ? $tallyUrl.'?respondent='.$respondent->session_token : $url;

            $message = "*Riset Edukasi Phishing*\n\n"
                     .'Halo'.($respondent->name ? " {$respondent->name}" : '').",\n\n"
                     .'Terima kasih telah berpartisipasi. Anda masih belum menyelesaikan kuesioner evaluasi. '
                     ."Mohon luangkan waktu beberapa menit untuk mengisinya pada tautan berikut guna menyelesaikan proses pendataan:\n\n"
                     ."👉 {$formUrl}\n\n"
                     .'Partisipasi Anda sangat berarti.';
        }

        if ($fonnte->send($respondent->whatsapp_number, $message)) {
            // Mark the reminder as fulfilled via WA
            $reminder->update([
                'followed_up_at' => now(),
                'sent_at' => now(),
            ]);
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Berhasil mengirim pesan WhatsApp ke responden.']);
        } else {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Gagal mengirim WA. Periksa integrasi Fonnte (token) di sistem Anda.']);
        }

        return back();
    }
}
