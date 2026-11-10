<?php

namespace Tests\Unit;

use App\Enums\SimulationTheme;
use App\Support\SimulationThemeContent;
use Tests\TestCase;

class SimulationThemeContentTest extends TestCase
{
    /**
     * Every theme must define the full set of keys every channel (web,
     * email, WhatsApp) relies on, so a newly added theme can't silently
     * ship with missing copy.
     */
    public function test_every_theme_has_the_full_set_of_content_keys(): void
    {
        $requiredKeys = [
            'org_name',
            'portal_title',
            'portal_subtitle',
            'id_field_label',
            'lead',
            'warning_note',
            'cta_primary_label',
            'cta_secondary_label',
            'email_subject',
            'scenario_summary',
        ];

        foreach (SimulationTheme::cases() as $theme) {
            $content = SimulationThemeContent::for($theme);

            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $content, "Theme {$theme->value} is missing content key '{$key}'");
                $this->assertNotSame('', trim((string) $content[$key]), "Theme {$theme->value} has an empty value for '{$key}'");
            }
        }
    }

    /**
     * Every theme must have a believable, alpha-dash-safe slug hint (used
     * as the default campaign link path), and no two themes should collide.
     */
    public function test_every_theme_has_a_unique_url_safe_slug_hint(): void
    {
        $hints = collect(SimulationTheme::cases())->map(fn (SimulationTheme $t) => $t->slugHint());

        $this->assertSame($hints->count(), $hints->unique()->count(), 'Two themes share the same slug hint.');

        $hints->each(function (string $hint) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $hint, "Slug hint '{$hint}' is not alpha-dash safe.");
        });
    }
}
