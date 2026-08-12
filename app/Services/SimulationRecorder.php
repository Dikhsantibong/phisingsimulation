<?php

namespace App\Services;

use App\Enums\BehaviorStatus;
use App\Enums\RespondentStatus;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use Illuminate\Http\Request;

/**
 * Records respondent interactions with the simulated portal.
 *
 * Privacy guarantees enforced here:
 *  - the raw User-Agent is parsed to coarse features and then discarded;
 *  - the IP address is hashed immediately and never stored in the clear;
 *  - only a boolean "did they type" flag is recorded, never typed content.
 */
class SimulationRecorder
{
    public function __construct(private readonly UserAgentParser $userAgents) {}

    /**
     * Record the first click on the simulation link (idempotent on first access).
     */
    public function recordAccess(Respondent $respondent, Request $request): SimulationEvent
    {
        $event = $respondent->simulationEvent()->firstOrNew([]);

        $ua = $this->userAgents->parse($request->userAgent());

        $event->fill([
            'device_type' => $ua['device_type'],
            'os_name' => $ua['os_name'],
            'browser_name' => $ua['browser_name'],
            'ip_hash' => $this->hashIp($request->ip()),
        ]);

        // Only stamp the first access time once, to preserve timing accuracy.
        if ($event->first_access_at === null) {
            $event->first_access_at = now();
        }

        $event->save();

        if ($respondent->status === RespondentStatus::Sent || $respondent->status === RespondentStatus::Pending) {
            $respondent->update(['status' => RespondentStatus::Clicked]);
        }

        return $event;
    }

    /**
     * Record the final response from the fake portal.
     *
     * @param  'submit'|'report'  $action  What the respondent did.
     * @param  bool  $keystrokeDetected  Whether any text input occurred (never the text itself).
     */
    public function recordBehavior(Respondent $respondent, string $action, bool $keystrokeDetected): SimulationEvent
    {
        $behavior = match (true) {
            $action === 'submit' => BehaviorStatus::Berisiko,
            $action === 'report' && $keystrokeDetected => BehaviorStatus::Netral,
            default => BehaviorStatus::Waspada,
        };

        // Submitting the credential form always implies keystrokes occurred.
        $keystrokeDetected = $keystrokeDetected || $action === 'submit';

        $event = $respondent->simulationEvent()->firstOrNew([]);
        $event->fill([
            'behavior_status' => $behavior,
            'keystroke_detected' => $keystrokeDetected,
            'response_at' => now(),
        ]);

        if ($event->first_access_at === null) {
            $event->first_access_at = now();
        }

        $event->save();

        $respondent->update(['status' => RespondentStatus::CompletedBehavior]);

        return $event;
    }

    /**
     * Hash an IP address with the app key as salt so it is never stored raw.
     */
    private function hashIp(?string $ip): ?string
    {
        if (blank($ip)) {
            return null;
        }

        return hash('sha256', $ip.'|'.config('app.key'));
    }
}
