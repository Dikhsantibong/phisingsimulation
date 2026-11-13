<?php

namespace Tests\Feature;

use App\Enums\SimulationTheme;
use App\Models\SimulationCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_campaign_can_be_created_with_a_single_theme(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('campaigns.store'), [
            'title' => 'Gelombang 1',
            'themes' => [SimulationTheme::Tka->value],
            'distribution_mode' => 'link',
        ])->assertRedirect();

        $campaign = SimulationCampaign::firstOrFail();
        $this->assertSame(SimulationTheme::Tka, $campaign->theme);
        $this->assertSame([SimulationTheme::Tka->value], $campaign->themes);
    }

    public function test_the_auto_generated_slug_follows_the_theme_not_the_internal_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('campaigns.store'), [
            'title' => 'Gelombang 1 - Kelas XII', // an internal/research-sounding title
            'themes' => [SimulationTheme::Tka->value],
            'distribution_mode' => 'link',
        ])->assertRedirect();

        $campaign = SimulationCampaign::firstOrFail();
        $this->assertStringStartsWith('verifikasi-akun-tka-', $campaign->slug);
        $this->assertStringNotContainsString('gelombang', $campaign->slug);
    }

    public function test_the_auto_generated_slug_uses_the_primary_theme_for_a_multi_theme_campaign(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('campaigns.store'), [
            'title' => 'Gelombang 1',
            'themes' => [SimulationTheme::PengumumanNilaiTugas->value, SimulationTheme::Tka->value],
            'distribution_mode' => 'link',
        ])->assertRedirect();

        $campaign = SimulationCampaign::firstOrFail();
        $this->assertStringStartsWith('cek-nilai-tugas-', $campaign->slug);
    }

    public function test_a_manually_supplied_slug_still_overrides_the_theme_default(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('campaigns.store'), [
            'title' => 'Gelombang 1',
            'themes' => [SimulationTheme::Tka->value],
            'distribution_mode' => 'link',
            'slug' => 'portal-ujian-sekolah',
        ])->assertRedirect();

        $campaign = SimulationCampaign::firstOrFail();
        $this->assertSame('portal-ujian-sekolah', $campaign->slug);
    }

    public function test_a_campaign_can_be_created_with_multiple_themes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('campaigns.store'), [
            'title' => 'Gelombang 1',
            'themes' => [SimulationTheme::Elearning->value, SimulationTheme::Tka->value],
            'distribution_mode' => 'link',
        ])->assertRedirect();

        $campaign = SimulationCampaign::firstOrFail();
        $this->assertCount(2, $campaign->themeOptions());
        $this->assertStringStartsWith('Acak: ', $campaign->themeLabel());
    }

    public function test_at_least_one_theme_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('campaigns.store'), [
            'title' => 'Gelombang 1',
            'themes' => [],
            'distribution_mode' => 'link',
        ])->assertSessionHasErrors('themes');

        $this->assertSame(0, SimulationCampaign::count());
    }

    public function test_index_exposes_a_ready_to_copy_broadcast_message_for_link_campaigns(): void
    {
        $user = User::factory()->create();
        SimulationCampaign::factory()->link()->create(['slug' => 'tes-abc123', 'theme' => SimulationTheme::Tka, 'themes' => [SimulationTheme::Tka->value]]);

        $response = $this->actingAs($user)->get(route('campaigns.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('campaigns.0.broadcast_message', fn ($message) => str_contains($message, 'tes-abc123')
                && str_contains($message, 'Sistem Tes Kemampuan Akademik (TKA)')));
    }

    public function test_index_has_no_broadcast_message_for_targeted_campaigns(): void
    {
        $user = User::factory()->create();
        SimulationCampaign::factory()->targeted()->create();

        $response = $this->actingAs($user)->get(route('campaigns.index'));

        $response->assertInertia(fn ($page) => $page->where('campaigns.0.broadcast_message', null));
    }

    public function test_broadcast_link_includes_the_access_code_when_one_is_set(): void
    {
        $user = User::factory()->create();
        SimulationCampaign::factory()->link()->create(['slug' => 'tes-kode', 'access_code' => 'rahasia123']);

        $response = $this->actingAs($user)->get(route('campaigns.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('campaigns.0.public_url', fn ($url) => str_contains($url, 'code=rahasia123'))
            ->where('campaigns.0.broadcast_message', fn ($message) => str_contains($message, 'code=rahasia123')));
    }
}
