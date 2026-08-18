<?php

namespace App\Http\Controllers\Simulation;

use App\Http\Controllers\Controller;
use App\Models\Respondent;
use App\Services\SimulationRecorder;
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
        $recorder->recordAccess($respondent, $request);

        if ($request->query('action') === 'reject') {
            $recorder->recordBehavior($respondent, 'report', false);
            return redirect()->route('simulation.reveal', ['respondent' => $respondent->session_token]);
        }

        return Inertia::render('phishing/portal', [
            'token' => $respondent->session_token,
        ]);
    }
}
