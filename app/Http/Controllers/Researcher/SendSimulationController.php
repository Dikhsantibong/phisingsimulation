<?php

namespace App\Http\Controllers\Researcher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Researcher\SendSimulationRequest;
use App\Jobs\SendSimulationEmail;
use App\Models\Respondent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SendSimulationController extends Controller
{
    /**
     * Show the "send simulation" panel.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('send-simulation', [
            'hasResearchKey' => filled($request->user()->research_key_hash),
        ]);
    }

    /**
     * Create respondents and queue the simulated phishing emails.
     */
    public function store(SendSimulationRequest $request): RedirectResponse
    {
        $rows = $request->validated()['respondents'];

        $respondents = DB::transaction(function () use ($rows) {
            return collect($rows)->map(fn (array $row) => Respondent::create([
                'name' => $row['name'] ?? null,
                'class_group' => $row['class_group'],
                'email' => $row['email'],
                'whatsapp_number' => $row['whatsapp_number'] ?? null,
            ]));
        });

        $respondents->each(fn (Respondent $respondent) => SendSimulationEmail::dispatch($respondent));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$respondents->count()} simulasi dijadwalkan untuk dikirim.",
        ]);

        return to_route('respondents.index');
    }
}
