<?php

namespace App\Jobs;

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

        $message = sprintf(
            "[Pengingat Riset Simulasi Phishing]\nResponden %s (kelas %s) perlu di-follow-up.\nAlasan: %s.\nNo. WA responden: %s",
            $respondent->name ?? $respondent->email,
            $respondent->class_group,
            $reminder->reminder_type->label(),
            $respondent->whatsapp_number ?? '-',
        );

        if ($fonnte->notifyResearcher($message)) {
            $reminder->update(['sent_at' => now()]);
        }
    }
}
