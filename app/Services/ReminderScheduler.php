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
        $threshold = now()->subHours((int) config('services.simulation.ignored_after_hours'));
        $created = 0;

        $respondents = Respondent::query()
            ->where('status', RespondentStatus::Sent)
            ->whereHas('simulationEvent', function ($query) use ($threshold) {
                $query->whereNull('first_access_at')->where('sent_at', '<=', $threshold);
            })
            ->get();

        foreach ($respondents as $respondent) {
            $created += (int) $this->queueReminder($respondent, ReminderType::SimulasiDiabaikan);
        }

        return $created;
    }

    private function remindUnfinishedQuestionnaires(): int
    {
        $threshold = now()->subHours((int) config('services.simulation.questionnaire_after_hours'));
        $created = 0;

        $respondents = Respondent::query()
            ->where('status', RespondentStatus::CompletedBehavior)
            ->whereDoesntHave('questionnaireResult')
            ->whereHas('simulationEvent', function ($query) use ($threshold) {
                $query->where('response_at', '<=', $threshold);
            })
            ->get();

        foreach ($respondents as $respondent) {
            $created += (int) $this->queueReminder($respondent, ReminderType::KuesionerBelumSelesai);
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
