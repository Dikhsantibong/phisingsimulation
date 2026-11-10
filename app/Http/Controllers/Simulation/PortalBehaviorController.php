<?php

namespace App\Http\Controllers\Simulation;

use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\Respondent;
use App\Services\SimulationRecorder;
use App\Support\SimulationThemeContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PortalBehaviorController extends Controller
{
    /**
     * Record the respondent's final action on the fake portal.
     *
     * The request intentionally only carries a coarse action and a boolean
     * keystroke flag — never the email/password values typed into the form.
     */
    public function store(Request $request, Respondent $respondent, SimulationRecorder $recorder): RedirectResponse|\Illuminate\Http\Response
    {
        $validated = $request->validate([
            'action' => ['required', 'in:submit,report,reject'],
            'keystroke_detected' => ['required', 'boolean'],
            'kelas' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($validated['kelas'])) {
            $respondent->update(['class_group' => $validated['kelas']]);
        }

        $event = $recorder->recordBehavior($respondent, $validated['action'], (bool) $validated['keystroke_detected']);

        return to_route('simulation.reveal', ['respondent' => $respondent->session_token]);
    }

    /**
     * Redirect directly to the external questionnaire.
     */
    public function questionnaire(Respondent $respondent): SymfonyResponse
    {
        if ($respondent->status === RespondentStatus::CompletedQuestionnaire || $respondent->status === RespondentStatus::Finished) {
            return to_route('simulation.completed', ['respondent' => $respondent->session_token]);
        }

        $tallyUrl = config('services.simulation.tally_url');

        if ($tallyUrl) {
            $url = rtrim($tallyUrl, '?').'?session_token='.$respondent->session_token;

            return Inertia::location($url);
        }

        // Fallback if no Tally URL is configured
        return to_route('simulation.reveal', ['respondent' => $respondent->session_token]);
    }

    /**
     * Show the debrief / reveal page (mandatory before the questionnaire).
     */
    public function reveal(Request $request, Respondent $respondent): Response|RedirectResponse
    {
        $isCompleted = $request->query('completed') === 'true';

        // Jika mereka memuat ulang link tanpa parameter completed=true,
        // dan status sudah selesai secara keseluruhan, lempar ke halaman completed.
        if (! $isCompleted && in_array($respondent->status, [RespondentStatus::CompletedQuestionnaire, RespondentStatus::Finished])) {
            return to_route('simulation.completed', ['respondent' => $respondent->session_token]);
        }
        $tallyUrl = config('services.simulation.tally_url');

        return Inertia::render('phishing/reveal', [
            'token' => $respondent->session_token,
            'behavior_status' => $respondent->simulationEvent?->behavior_status?->value,
            'keystroke_detected' => (bool) $respondent->simulationEvent?->keystroke_detected,
            'questionnaireUrl' => $tallyUrl
                ? rtrim($tallyUrl, '?').'?session_token='.$respondent->session_token
                : null,
            'isCompleted' => $isCompleted,
            'content' => SimulationThemeContent::for($respondent->effectiveTheme()),
        ]);
    }

    /**
     * Show the completion screen for respondents who have finished both simulation and questionnaire.
     */
    public function completed(Respondent $respondent): Response|RedirectResponse
    {
        if (! in_array($respondent->status, [RespondentStatus::CompletedQuestionnaire, RespondentStatus::Finished])) {
            return to_route('simulation.access', ['respondent' => $respondent->session_token]);
        }

        return Inertia::render('phishing/completed', [
            'token' => $respondent->session_token,
            'behavior_status' => $respondent->simulationEvent?->behavior_status?->value,
            'keystroke_detected' => (bool) $respondent->simulationEvent?->keystroke_detected,
        ]);
    }
}
