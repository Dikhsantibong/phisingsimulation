<?php

namespace Tests\Feature;

use App\Models\Respondent;
use App\Models\SimulationCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_visit_creates_a_respondent_and_sets_a_resume_cookie(): void
    {
        $campaign = SimulationCampaign::factory()->link()->create();

        $this->assertSame(0, Respondent::count());

        $response = $this->get(route('campaign.access', ['campaign' => $campaign->slug]));

        $this->assertSame(1, Respondent::count());
        $respondent = Respondent::first();

        $response->assertRedirect(route('simulation.access', ['respondent' => $respondent->session_token]));
        $response->assertCookie("sim_tok_{$campaign->id}", $respondent->session_token);
    }

    public function test_repeat_visit_with_the_same_cookie_resumes_instead_of_duplicating(): void
    {
        $campaign = SimulationCampaign::factory()->link()->create();

        $first = $this->get(route('campaign.access', ['campaign' => $campaign->slug]));
        $respondent = Respondent::first();

        $this->assertSame(1, Respondent::count());

        $second = $this->withCookie("sim_tok_{$campaign->id}", $respondent->session_token)
            ->get(route('campaign.access', ['campaign' => $campaign->slug]));

        $this->assertSame(1, Respondent::count());
        $second->assertRedirect(route('simulation.access', ['respondent' => $respondent->session_token]));
    }

    public function test_many_concurrent_first_time_visitors_each_get_their_own_unique_respondent(): void
    {
        $campaign = SimulationCampaign::factory()->link()->create();

        for ($i = 0; $i < 50; $i++) {
            $this->get(route('campaign.access', ['campaign' => $campaign->slug]));
        }

        $this->assertSame(50, Respondent::count());
        $this->assertSame(50, Respondent::distinct('session_token')->count('session_token'));
    }

    public function test_inactive_campaign_does_not_create_a_respondent(): void
    {
        $campaign = SimulationCampaign::factory()->link()->inactive()->create();

        $this->get(route('campaign.access', ['campaign' => $campaign->slug]))->assertOk();

        $this->assertSame(0, Respondent::count());
    }

    public function test_expired_campaign_does_not_create_a_respondent(): void
    {
        $campaign = SimulationCampaign::factory()->link()->create(['expires_at' => now()->subDay()]);

        $this->get(route('campaign.access', ['campaign' => $campaign->slug]))->assertOk();

        $this->assertSame(0, Respondent::count());
    }

    public function test_access_code_is_required_before_a_respondent_is_created(): void
    {
        $campaign = SimulationCampaign::factory()->link()->create(['access_code' => 'secret123']);

        $this->get(route('campaign.access', ['campaign' => $campaign->slug]))->assertOk();
        $this->assertSame(0, Respondent::count());

        $this->get(route('campaign.access', ['campaign' => $campaign->slug]).'?code=secret123')->assertRedirect();
        $this->assertSame(1, Respondent::count());
    }

    public function test_a_targeted_campaigns_public_link_does_not_create_a_respondent(): void
    {
        $campaign = SimulationCampaign::factory()->targeted()->create();

        $this->get(route('campaign.access', ['campaign' => $campaign->slug]))->assertOk();

        $this->assertSame(0, Respondent::count());
    }

    public function test_first_visit_assigns_a_theme_from_the_campaigns_configured_themes(): void
    {
        $campaign = SimulationCampaign::factory()->link()->create();

        $this->get(route('campaign.access', ['campaign' => $campaign->slug]));

        $respondent = Respondent::first();
        $this->assertNotNull($respondent->theme);
        $this->assertSame($campaign->theme, $respondent->theme);
    }

    public function test_respondents_of_a_multi_theme_campaign_get_a_mix_of_themes(): void
    {
        $campaign = SimulationCampaign::factory()->link()->multiTheme()->create();

        for ($i = 0; $i < 30; $i++) {
            $this->get(route('campaign.access', ['campaign' => $campaign->slug]).'?code=force-new-'.$i);
        }

        // access_code isn't set on this campaign, so the bogus ?code= query
        // param above is ignored and each request without a resume cookie
        // still creates a brand-new respondent.
        $themesSeen = Respondent::pluck('theme')->unique();

        $this->assertSame(30, Respondent::count());
        $this->assertGreaterThan(1, $themesSeen->count(), 'Expected more than one distinct theme across 30 respondents.');
    }
}
