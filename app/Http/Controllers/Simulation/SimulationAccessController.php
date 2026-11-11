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

class SimulationAccessController extends Controller
{
    /**
     * Handle a click on the simulation link: record access and show the fake portal.
     */
    public function show(Request $request, Respondent $respondent, SimulationRecorder $recorder): RedirectResponse|Response
    {
        if ($respondent->status === RespondentStatus::CompletedQuestionnaire || $respondent->status === RespondentStatus::Finished) {
            return redirect()->route('simulation.completed', ['respondent' => $respondent->session_token]);
        } elseif ($respondent->status === RespondentStatus::CompletedBehavior) {
            return redirect()->route('simulation.reveal', ['respondent' => $respondent->session_token]);
        }

        $recorder->recordAccess($respondent, $request);

        if ($request->query('action') === 'reject') {
            // Distinct from the portal's own "Laporkan sebagai Mencurigakan"
            // action ('report') — this one never even opened the portal.
            $recorder->recordBehavior($respondent, 'reject_email', false);

            return redirect()->route('simulation.reveal', ['respondent' => $respondent->session_token]);
        }

        return Inertia::render('phishing/portal', [
            'token' => $respondent->session_token,
            'content' => SimulationThemeContent::for($respondent->effectiveTheme()),
        ]);
    }
}
