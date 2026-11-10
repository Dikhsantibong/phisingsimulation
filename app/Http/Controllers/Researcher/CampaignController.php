<?php

namespace App\Http\Controllers\Researcher;

use App\Enums\CampaignDistributionMode;
use App\Enums\SimulationTheme;
use App\Http\Controllers\Controller;
use App\Models\SimulationCampaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    /**
     * List campaigns the researcher can assign to respondents or share as a public link.
     */
    public function index(Request $request): Response
    {
        $campaigns = SimulationCampaign::query()
            ->withCount('respondents')
            ->latest()
            ->get()
            ->map(function (SimulationCampaign $campaign) {
                $isLink = $campaign->distribution_mode === CampaignDistributionMode::Link;
                $publicUrl = $isLink
                    ? route('campaign.access', array_filter([
                        'campaign' => $campaign->slug,
                        'code' => $campaign->access_code,
                    ]))
                    : null;

                return [
                    'id' => $campaign->id,
                    'slug' => $campaign->slug,
                    'title' => $campaign->title,
                    'theme' => $campaign->theme->value,
                    'themes' => collect($campaign->themeOptions())->map(fn ($t) => $t->value)->all(),
                    'theme_label' => $campaign->themeLabel(),
                    'is_multi_theme' => count($campaign->themeOptions()) > 1,
                    'distribution_mode' => $campaign->distribution_mode->value,
                    'is_active' => $campaign->is_active,
                    'expires_at' => $campaign->expires_at?->toIso8601String(),
                    'access_code' => $campaign->access_code,
                    'respondents_count' => $campaign->respondents_count,
                    'public_url' => $publicUrl,
                    'broadcast_message' => $publicUrl !== null ? $campaign->broadcastMessage($publicUrl) : null,
                ];
            });

        return Inertia::render('campaigns/index', [
            'campaigns' => $campaigns,
            'themes' => collect(SimulationTheme::cases())->map(fn ($t) => ['value' => $t->value, 'label' => $t->label()]),
            'distributionModes' => collect(CampaignDistributionMode::cases())->map(fn ($m) => ['value' => $m->value, 'label' => $m->label()]),
        ]);
    }

    /**
     * Create a new campaign (link-based or targeted).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'themes' => ['required', 'array', 'min:1'],
            'themes.*' => [Rule::enum(SimulationTheme::class)],
            'distribution_mode' => ['required', Rule::enum(CampaignDistributionMode::class)],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:simulation_campaigns,slug'],
            'access_code' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $slug = $data['slug'] ?? Str::slug(SimulationTheme::from($data['themes'][0])->slugHint()).'-'.Str::lower(Str::random(6));

        SimulationCampaign::create([
            'title' => $data['title'],
            'theme' => $data['themes'][0],
            'themes' => $data['themes'],
            'distribution_mode' => $data['distribution_mode'],
            'slug' => $slug,
            'access_code' => $data['access_code'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kampanye berhasil dibuat.']);

        return back();
    }

    /**
     * Toggle a campaign's active state.
     */
    public function toggleActive(SimulationCampaign $campaign): RedirectResponse
    {
        $campaign->update(['is_active' => ! $campaign->is_active]);

        return back();
    }

    /**
     * Delete a campaign (respondents are kept, their campaign_id is nulled).
     */
    public function destroy(SimulationCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kampanye dihapus.']);

        return back();
    }
}
