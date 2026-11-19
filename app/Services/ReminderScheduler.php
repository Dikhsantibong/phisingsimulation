<?php

namespace App\Services;

use App\Enums\BehaviorStatus;
use App\Enums\ReminderChannel;
use App\Enums\ReminderType;
use App\Enums\RespondentStatus;
use App\Jobs\SendReminderNotification;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Creates and dispatches follow-up reminders based on study timing thresholds.
 *
 * Reminders notify the researcher (see {@see SendReminderNotification}), each
 * exactly once per respondent per type — there is no repeat/resend loop here.
 * The researcher can still manually resend a message from the Pengingat page.
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
        return $this->remindNeverAccessed()
            + $this->remindQuestionnaireNotStarted()
            + $this->remindDeadlinePassed();
    }

    /**
     * One-shot notice: the respondent never opened the simulation link at
     * all before their deadline (or the default threshold, if no per-send
     * time limit was configured).
     */
    private function remindNeverAccessed(): int
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
            if ($this->queueOnce($respondent, ReminderType::SimulasiDiabaikan)) {
                $created++;

                // Never accessed before the deadline: freeze the outcome
                // immediately, there is nothing left to wait for.
                $respondent->simulationEvent?->update([
                    'behavior_status' => BehaviorStatus::TidakMerespons,
                ]);
            }
        }

        return $created;
    }

    /**
     * One-shot early heads-up: the respondent finished the portal
     * (submit/tolak/lapor) but hasn't touched the questionnaire yet, and
     * enough time has passed since that it's unlikely they'll come straight
     * back to it on their own.
     */
    private function remindQuestionnaireNotStarted(): int
    {
        $defaultThreshold = now()->subHours((int) config('services.simulation.questionnaire_after_hours'));
        $created = 0;

        $respondents = $this->unfinishedQuestionnaireCandidates();

        foreach ($respondents as $respondent) {
            $event = $respondent->simulationEvent;

            if ($event === null || ! $this->earlyNoticeIsDue($respondent, $event, $defaultThreshold)) {
                continue;
            }

            if ($this->queueOnce($respondent, ReminderType::KuesionerBelumSelesai)) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * One-shot final notice: the original response deadline the respondent
     * was given has actually passed and the questionnaire is still not
     * done. Only applies to respondents who were sent with a time limit —
     * there is no "original deadline" to pass otherwise.
     */
    private function remindDeadlinePassed(): int
    {
        $created = 0;

        $respondents = $this->unfinishedQuestionnaireCandidates()
            ->filter(fn (Respondent $respondent) => $respondent->expires_at !== null
                && now()->greaterThanOrEqualTo($respondent->expires_at));

        foreach ($respondents as $respondent) {
            if ($this->queueOnce($respondent, ReminderType::BatasWaktuTerlewat)) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Respondents who finished the portal but have no questionnaire answer
     * yet — the shared candidate pool for both questionnaire reminder
     * stages above.
     *
     * @return Collection<int, Respondent>
     */
    private function unfinishedQuestionnaireCandidates(): Collection
    {
        return Respondent::query()
            ->where('status', RespondentStatus::CompletedBehavior)
            ->whereDoesntHave('questionnaireResult')
            ->whereHas('simulationEvent', function ($q) {
                $q->whereNotNull('response_at');
            })
            ->get()
            ->filter(fn (Respondent $respondent) => $respondent->simulationEvent?->response_at !== null);
    }

    /**
     * Whether enough time has passed since the respondent finished the
     * portal to send the early "they probably won't come back on their own"
     * notice: their own time limit (capped at 10 minutes once it's an hour
     * or longer) if one was set, otherwise the default threshold.
     */
    private function earlyNoticeIsDue(Respondent $respondent, SimulationEvent $event, CarbonInterface $defaultThreshold): bool
    {
        if ($event->response_at === null) {
            return false;
        }

        if ($respondent->expires_at && $event->sent_at) {
            // Original duration in minutes, from created_at rather than
            // sent_at so queue delay never shrinks it.
            $originalDuration = $respondent->created_at->diffInMinutes($respondent->expires_at);
            $bufferMinutes = $originalDuration >= 60 ? 10 : $originalDuration;

            return now()->greaterThanOrEqualTo((clone $event->response_at)->addMinutes($bufferMinutes));
        }

        return $event->response_at->lessThanOrEqualTo($defaultThreshold);
    }

    /**
     * Queue a reminder for a respondent, but only the first time for this
     * type — every notice here is fire-once, never repeated.
     */
    private function queueOnce(Respondent $respondent, ReminderType $type): bool
    {
        $alreadyNotified = $respondent->reminderLogs()
            ->where('reminder_type', $type)
            ->exists();

        if ($alreadyNotified) {
            return false;
        }

        $reminder = $respondent->reminderLogs()->create([
            'reminder_type' => $type,
            'channel' => ReminderChannel::WhatsApp,
            'scheduled_at' => now(),
            'attempt_number' => 1,
        ]);

        SendReminderNotification::dispatch($reminder);

        return true;
    }
}
