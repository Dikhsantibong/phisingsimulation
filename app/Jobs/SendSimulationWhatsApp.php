<?php

namespace App\Jobs;

use App\Enums\RespondentStatus;
use App\Models\Respondent;
use App\Services\FonnteService;
use App\Support\SimulationThemeContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class SendSimulationWhatsApp implements ShouldQueue
{
    use Queueable;

    public function __construct(public Respondent $respondent) {}

    /**
     * Send the simulated phishing WhatsApp message and record the send timestamp.
     */
    public function handle(FonnteService $fonnte): void
    {
        $respondent = $this->respondent->fresh();

        if ($respondent === null || $respondent->status !== RespondentStatus::Pending) {
            return;
        }

        $content = SimulationThemeContent::for($respondent->effectiveTheme());
        $accessUrl = route('simulation.access', ['respondent' => $respondent->session_token]);

        $message = "*{$content['org_name']}*\n\n"
            .'Halo'.($respondent->name ? " {$respondent->name}" : '').",\n\n"
            ."{$content['lead']}\n\n"
            ."{$content['warning_note']}\n\n"
            ."👉 {$accessUrl}";

        if (! $fonnte->send((string) $respondent->whatsapp_number, $message)) {
            throw new RuntimeException("Failed to send simulation WhatsApp message to respondent {$respondent->id}.");
        }

        $respondent->simulationEvent()->updateOrCreate([], ['sent_at' => now()]);
        $respondent->update(['status' => RespondentStatus::Sent]);
    }
}
