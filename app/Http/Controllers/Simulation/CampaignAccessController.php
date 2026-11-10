<?php

namespace App\Http\Controllers\Simulation;

use App\Enums\CampaignDistributionMode;
use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\Respondent;
use App\Models\SimulationCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampaignAccessController extends Controller
{
    /**
     * Public entry point for a shareable campaign link.
     *
     * Creates one respondent row per first-time visitor (no email/WhatsApp
     * required) and resumes the same row on repeat visits via a cookie, so
     * concurrent visitors never share or overwrite each other's data.
     */
    public function show(Request $request, SimulationCampaign $campaign): RedirectResponse|Response
    {
        if (! $campaign->is_active
            || $campaign->distribution_mode !== CampaignDistributionMode::Link
            || ($campaign->expires_at && now()->greaterThan($campaign->expires_at))) {
            return Inertia::render('phishing/expired');
        }

        if (filled($campaign->access_code) && $request->query('code') !== $campaign->access_code) {
            return Inertia::render('phishing/campaign-access-code', [
                'invalid' => $request->filled('code'),
            ]);
        }

        $cookieName = "sim_tok_{$campaign->id}";
        $token = $request->cookie($cookieName);

        $respondent = $token
            ? Respondent::where('session_token', $token)->where('campaign_id', $campaign->id)->first()
            : null;

        if ($respondent === null) {
            $respondent = Respondent::create([
                'session_token' => Str::uuid(),
                'campaign_id' => $campaign->id,
                'theme' => $campaign->pickRandomTheme()->value,
                'status' => RespondentStatus::Sent,
            ]);
        }

        return redirect()
            ->route('simulation.access', ['respondent' => $respondent->session_token])
            ->cookie($cookieName, $respondent->session_token, 60 * 24 * 30, '/', null, null, true, false, 'lax');
    }
}
