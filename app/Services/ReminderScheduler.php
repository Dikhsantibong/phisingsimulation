<?php

namespace App\Services;

use App\Enums\BehaviorStatus;
use App\Enums\ReminderChannel;
use App\Enums\ReminderType;
use App\Enums\RespondentStatus;
use App\Jobs\SendReminderNotification;
use App\Models\Respondent;

/**
 * Creates and dispatches follow-up reminders based on study timing thresholds.
 *
 * Reminders notify the researcher (see {@see SendReminderNotification}); a
 * respondent is marked "tidak_merespons" once the reminder cap is reached.
 */
class ReminderScheduler
{
    /**
     * Scan all respondents and queue any reminders that are now due.
     *
     * @return int Number of reminders created.
     */
    public function run(): int
    {
        return $this->remindIgnoredSimulations()
            + $this->remindUnfinishedQuestionnaires();
    }

    private function remindIgnoredSimulations(): int
    {
        $defaultThreshold = now()->subHours((int) config('services.simulation.ignored_after_hours'));
        $created = 0;

        $respondents = Respondent::query()
            ->where('status', RespondentStatus::Sent)
            ->where(function ($q) use ($defaultThreshold) {
                $q->where(function ($sq) {
                    $sq->whereNotNull('expires_at')
                        ->where('expires_at', '<=', now());
                })
                    ->orWhere(function ($sq) use ($defaultThreshold) {
                        $sq->whereNull('expires_at')
                            ->whereHas('simulationEvent', function ($ssq) use ($defaultThreshold) {
                                $ssq->where('sent_at', '<=', $defaultThreshold);
                            });
                    });
            })
            ->whereHas('simulationEvent', function ($query) {
                $query->whereNull('first_access_at');
            })
            ->get();

        foreach ($respondents as $respondent) {
            $created += (int) $this->queueReminder($respondent, ReminderType::SimulasiDiabaikan);
        }

        return $created;
    }

    private function remindUnfinishedQuestionnaires(): int
    {
        $defaultThreshold = now()->subHours((int) config('services.simulation.questionnaire_after_hours'));
        $created = 0;

        $respondents = Respondent::query()
            ->where('status', RespondentStatus::CompletedBehavior)
            ->whereDoesntHave('questionnaireResult')
            ->whereHas('simulationEvent', function ($q) {
                $q->whereNotNull('response_at');
            })
            ->get();

        foreach ($respondents as $respondent) {
            $event = $respondent->simulationEvent;
            if (! $event || ! $event->response_at) {
                continue;
            }

            $shouldQueue = false;

            if ($respondent->expires_at && $event->sent_at) {
                // Time limit was configured. Get original duration in minutes
                $originalDuration = $event->sent_at->diffInMinutes($respondent->expires_at);

                if ($originalDuration >= 60) {
                    // Jika batas waktu responnya sekam ke atas, set 10 menit sesudah beraksi di portal
                    $thresholdTime = clone $event->response_at;
                    $thresholdTime->addMinutes(10);
                } else {
                    // Jika di bawah sejam, ikuti batas waktu respon
                    $thresholdTime = clone $event->response_at;
                    $thresholdTime->addMinutes($originalDuration);
                }

                if (now()->greaterThanOrEqualTo($thresholdTime)) {
                    $shouldQueue = true;
                }
            } else {
                // No time limit configured, use default questionnaire threshold
                if ($event->response_at->lessThanOrEqualTo($defaultThreshold)) {
                    $shouldQueue = true;
                }
            }

            if ($shouldQueue) {
                $created += (int) $this->queueReminder($respondent, ReminderType::KuesionerBelumSelesai);
            }
        }

        return $created;
    }

    /**
     * Queue a single reminder for a respondent, respecting the per-type cap.
     */
    private function queueReminder(Respondent $respondent, ReminderType $type): bool
    {
        $max = (int) config('services.simulation.max_reminders');

        $attempts = $respondent->reminderLogs()
            ->where('reminder_type', $type)
            ->count();

        if ($attempts >= $max) {
            // Reminder cap reached: freeze the outcome as "tidak_merespons"
            // when the respondent never engaged with the simulation.
            if ($type === ReminderType::SimulasiDiabaikan) {
                $respondent->simulationEvent?->update([
                    'behavior_status' => BehaviorStatus::TidakMerespons,
                ]);
            }

            return false;
        }

        $reminder = $respondent->reminderLogs()->create([
            'reminder_type' => $type,
            'channel' => ReminderChannel::WhatsApp,
            'scheduled_at' => now(),
            'attempt_number' => $attempts + 1,
        ]);

        SendReminderNotification::dispatch($reminder);

        return true;
    }
}
