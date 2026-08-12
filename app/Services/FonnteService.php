<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client for the Fonnte WhatsApp gateway.
 *
 * Reminders are sent to the researcher's own number so they can follow up
 * with respondents manually — the system never messages students directly.
 */
class FonnteService
{
    public function __construct(
        private readonly ?string $token = null,
        private readonly ?string $endpoint = null,
        private readonly ?string $researcherNumber = null,
    ) {}

    /**
     * Send a WhatsApp message. Returns true when the gateway accepted it.
     */
    public function send(string $target, string $message): bool
    {
        $token = $this->token ?? config('services.fonnte.token');
        $endpoint = $this->endpoint ?? config('services.fonnte.endpoint');

        if (blank($token)) {
            Log::warning('Fonnte token not configured; skipping WhatsApp reminder.', ['target' => $target]);

            return false;
        }

        $response = Http::withHeaders(['Authorization' => $token])
            ->asForm()
            ->post($endpoint, [
                'target' => $target,
                'message' => $message,
            ]);

        if ($response->failed()) {
            Log::error('Fonnte send failed.', ['status' => $response->status(), 'body' => $response->body()]);

            return false;
        }

        return true;
    }

    /**
     * Send a reminder to the researcher's configured number.
     */
    public function notifyResearcher(string $message): bool
    {
        $number = $this->researcherNumber ?? config('services.fonnte.researcher_number');

        if (blank($number)) {
            Log::warning('Fonnte researcher number not configured; skipping reminder.');

            return false;
        }

        return $this->send($number, $message);
    }
}
