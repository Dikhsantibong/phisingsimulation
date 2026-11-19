<?php

namespace App\Http\Controllers\Researcher;

use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\ReminderLog;
use Inertia\Inertia;
use Inertia\Response;

class ReminderController extends Controller
{
    /**
     * Show the reminder schedule / queue table.
     */
    public function index(): Response
    {
        $reminders = ReminderLog::query()
            ->whereHas('respondent', function ($query) {
                $query->whereNotIn('status', [
                    RespondentStatus::Finished,
                    RespondentStatus::CompletedQuestionnaire,
                ]);
            })
            ->with('respondent')
            ->latest('scheduled_at')
            ->paginate(20)
            ->through(fn (ReminderLog $reminder) => [
                'id' => $reminder->id,
                'token' => $reminder->respondent->session_token,
                'respondent' => $reminder->respondent->name ?? $reminder->respondent->email,
                'class_group' => $reminder->respondent->class_group,
                'reminder_type' => $reminder->reminder_type->value,
                'reminder_type_label' => $reminder->reminder_type->label(),
                'phone_number' => $reminder->respondent->whatsapp_number ?? '-',
                'scheduled_at' => $reminder->scheduled_at->toIso8601String(),
                'sent_at' => $reminder->sent_at?->toIso8601String(),
                'followed_up_at' => $reminder->followed_up_at?->toIso8601String(),
            ]);

        return Inertia::render('reminders/index', [
            'reminders' => $reminders,
        ]);
    }
}
