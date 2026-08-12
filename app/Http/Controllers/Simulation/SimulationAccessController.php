<?php

namespace App\Http\Controllers\Simulation;

use App\Http\Controllers\Controller;
use App\Models\Respondent;
use App\Services\SimulationRecorder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SimulationAccessController extends Controller
{
    /**
     * Handle a click on the simulation link: record access and show the fake portal.
     */
    public function show(Request $request, Respondent $respondent, SimulationRecorder $recorder): Response
    {
        $recorder->recordAccess($respondent, $request);

        return Inertia::render('phishing/portal', [
            'token' => $respondent->session_token,
        ]);
    }
}
