<?php

namespace App\Http\Controllers\Researcher;

use App\Enums\CampaignDistributionMode;
use App\Enums\SendChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Researcher\SendSimulationRequest;
use App\Jobs\SendSimulationEmail;
use App\Jobs\SendSimulationWhatsApp;
use App\Models\Respondent;
use App\Models\SimulationCampaign;
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
            'campaigns' => SimulationCampaign::query()
                ->where('distribution_mode', CampaignDistributionMode::Targeted)
                ->where('is_active', true)
                ->get()
                ->map(fn (SimulationCampaign $campaign) => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'theme_label' => $campaign->themeLabel(),
                ]),
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
        if (! empty($data['time_limit_value'])) {
            $value = (int) $data['time_limit_value'];
            $unit = $data['time_limit_unit'] ?? 'minutes';
            $expiresAt = $unit === 'hours' ? now()->addHours($value) : now()->addMinutes($value);
        }

        $campaign = SimulationCampaign::findOrFail((int) $data['campaign_id']);

        $respondents = DB::transaction(function () use ($rows, $expiresAt, $campaign) {
            /** @var array<int, array{name?: string, class_group?: string, channel: string, email?: string, whatsapp_number?: string}> $rows */
            return collect($rows)->map(fn (array $row) => Respondent::create([
                'campaign_id' => $campaign->id,
                'theme' => $campaign->pickRandomTheme()->value,
                'name' => $row['name'] ?? null,
                'class_group' => $row['class_group'] ?? 'Default',
                'email' => $row['email'] ?? null,
                'whatsapp_number' => $row['whatsapp_number'] ?? null,
                'send_channel' => $row['channel'],
                'expires_at' => $expiresAt,
            ]));
        });

        $respondents->each(function (Respondent $respondent) {
            match ($respondent->send_channel) {
                SendChannel::WhatsApp => SendSimulationWhatsApp::dispatch($respondent),
                default => SendSimulationEmail::dispatch($respondent),
            };
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$respondents->count()} simulasi dijadwalkan untuk dikirim.",
        ]);

        return to_route('respondents.index');
    }
}
