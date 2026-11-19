<?php

namespace App\Jobs;

use App\Enums\ReminderType;
use App\Models\ReminderLog;
use App\Services\FonnteService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendReminderNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ReminderLog $reminder) {}

    /**
     * Notify the researcher (via WhatsApp) to follow up on a respondent.
     *
     * The message is sent to the researcher's own number — never to the
     * respondent — so the researcher performs the follow-up manually.
     */
    public function handle(FonnteService $fonnte): void
    {
        $reminder = $this->reminder->fresh(['respondent']);

        if ($reminder === null || $reminder->sent_at !== null) {
            return;
        }

        $respondent = $reminder->respondent;
        $name = $respondent->name ?? $respondent->email;
        $phone = $respondent->whatsapp_number ?? '-';

        $message = match ($reminder->reminder_type) {
            ReminderType::SimulasiDiabaikan => sprintf(
                "⚠️ [%s]\nResponden %s (kelas %s) belum pernah membuka link simulasi sama sekali sejak dikirim, dan sudah melewati batas waktu respon.\nNo. WA responden: %s",
                $reminder->reminder_type->label(),
                $name,
                $respondent->class_group,
                $phone,
            ),
            ReminderType::KuesionerBelumSelesai => sprintf(
                "⚠️ [%s]\nResponden %s (kelas %s) sudah menyelesaikan simulasi phishing, namun belum mulai mengisi kuesioner. Mohon di-follow-up agar tidak lupa.\nNo. WA responden: %s",
                $reminder->reminder_type->label(),
                $name,
                $respondent->class_group,
                $phone,
            ),
            ReminderType::BatasWaktuTerlewat => sprintf(
                "🔴 [%s]\nResponden %s (kelas %s) sudah melewati batas waktu respon yang ditetapkan, dan kuesioner MASIH belum diisi. Mohon segera di-follow-up.\nNo. WA responden: %s",
                $reminder->reminder_type->label(),
                $name,
                $respondent->class_group,
                $phone,
            ),
        };

        if ($fonnte->notifyResearcher($message)) {
            $reminder->update(['sent_at' => now()]);
        }
    }
}
