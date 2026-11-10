<?php

namespace Tests\Unit;

use App\Enums\SimulationTheme;
use App\Models\SimulationCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationCampaignThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_single_theme_campaign_always_picks_that_theme(): void
    {
        $campaign = SimulationCampaign::factory()->create(['themes' => [SimulationTheme::Tka->value]]);

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(SimulationTheme::Tka, $campaign->pickRandomTheme());
        }

        $this->assertSame('Portal Tes Kemampuan Akademik (TKA)', $campaign->themeLabel());
    }

    public function test_a_campaign_created_before_multi_theme_support_falls_back_to_its_single_theme_column(): void
    {
        $campaign = SimulationCampaign::factory()->create(['theme' => SimulationTheme::PengumumanNilaiTugas, 'themes' => null]);

        $this->assertSame([SimulationTheme::PengumumanNilaiTugas], $campaign->themeOptions());
        $this->assertSame(SimulationTheme::PengumumanNilaiTugas, $campaign->pickRandomTheme());
    }

    public function test_a_multi_theme_campaign_draws_from_every_configured_theme(): void
    {
        $campaign = SimulationCampaign::factory()->multiTheme()->create();

        $picked = collect(range(1, 100))->map(fn () => $campaign->pickRandomTheme()->value)->unique();

        $this->assertGreaterThan(1, $picked->count(), 'Expected pickRandomTheme to draw more than one distinct theme over 100 picks.');
        $this->assertStringStartsWith('Acak: ', $campaign->themeLabel());
    }

    public function test_broadcast_message_embeds_the_link_and_the_themes_pretext_copy(): void
    {
        $campaign = SimulationCampaign::factory()->create(['theme' => SimulationTheme::Tka, 'themes' => [SimulationTheme::Tka->value]]);

        $message = $campaign->broadcastMessage('https://simcyber.site/c/tes-abc123');

        $this->assertStringContainsString('Portal Tes Kemampuan Akademik (TKA)', $message);
        $this->assertStringContainsString('https://simcyber.site/c/tes-abc123', $message);
    }
}
