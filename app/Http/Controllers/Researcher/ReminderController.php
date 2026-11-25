<?php

namespace App\Http\Controllers\Researcher;

use App\Enums\ReminderType;
use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\ReminderLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            // "Belum Akses Simulasi" and "Sempat Akses Portal" reminders are
            // each a snapshot of a moment that has since passed if the
            // respondent went on to actually open the link / act on the
            // portal — stale once overtaken by events, so they must not
            // keep showing (and notifying the researcher about) a state
            // that is no longer true.
            ->where(function ($query) {
                $query->whereNotIn('reminder_type', [ReminderType::SimulasiDiabaikan, ReminderType::PortalDiabaikan])
                    ->orWhere(function ($q) {
                        $q->where('reminder_type', ReminderType::SimulasiDiabaikan)
                            ->whereHas('respondent.simulationEvent', function ($sq) {
                                $sq->whereNull('first_access_at');
                            });
                    })
                    ->orWhere(function ($q) {
                        $q->where('reminder_type', ReminderType::PortalDiabaikan)
                            ->whereHas('respondent.simulationEvent', function ($sq) {
                                $sq->whereNull('response_at');
                            });
                    });
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

    /**
     * Delete a reminder entry (just the log row — the respondent and their
     * data are untouched). If the condition that triggered it is still
     * true, the next scheduler scan may queue it again.
     */
    public function destroy(ReminderLog $reminder): RedirectResponse
    {
        $reminder->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Reminder berhasil dihapus.',
        ]);

        return back();
    }

    /**
     * Delete several reminder entries at once.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = ReminderLog::whereIn('id', $validated['ids'])->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$deleted} reminder berhasil dihapus.",
        ]);

        return back();
    }
}
