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
        $data = $request->validated();
        $rows = $data['respondents'];

        $expiresAt = null;
        if (!empty($data['time_limit_value'])) {
            $value = (int) $data['time_limit_value'];
            $unit = $data['time_limit_unit'] ?? 'minutes';
            $expiresAt = $unit === 'hours' ? now()->addHours($value) : now()->addMinutes($value);
        }

        $respondents = DB::transaction(function () use ($rows, $expiresAt) {
            return collect($rows)->map(fn (array $row) => Respondent::create([
                'name' => $row['name'] ?? null,
                'class_group' => $row['class_group'] ?? 'Default',
                'email' => $row['email'],
                'whatsapp_number' => $row['whatsapp_number'] ?? null,
                'expires_at' => $expiresAt,
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
