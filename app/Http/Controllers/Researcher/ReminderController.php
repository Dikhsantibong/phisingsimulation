<?php

namespace App\Http\Controllers\Researcher;

use App\Http\Controllers\Controller;
use App\Models\ReminderLog;
use Illuminate\Http\RedirectResponse;
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
            ->with('respondent')
            ->latest('scheduled_at')
            ->paginate(20)
            ->through(fn (ReminderLog $reminder) => [
                'id' => $reminder->id,
                'token' => $reminder->respondent->session_token,
                'respondent' => $reminder->respondent->name ?? $reminder->respondent->email,
                'class_group' => $reminder->respondent->class_group,
                'reminder_type' => $reminder->reminder_type->label(),
                'channel' => $reminder->channel->label(),
                'attempt_number' => $reminder->attempt_number,
                'scheduled_at' => $reminder->scheduled_at->toIso8601String(),
                'sent_at' => $reminder->sent_at?->toIso8601String(),
                'followed_up_at' => $reminder->followed_up_at?->toIso8601String(),
            ]);

        return Inertia::render('reminders/index', [
            'reminders' => $reminders,
        ]);
    }

    /**
     * Mark a reminder as manually followed up by the researcher.
     */
    public function markFollowedUp(ReminderLog $reminder): RedirectResponse
    {
        $reminder->update(['followed_up_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ditandai sudah di-follow-up.']);

        return back();
    }
}
