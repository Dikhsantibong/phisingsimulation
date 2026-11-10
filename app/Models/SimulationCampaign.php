<?php

namespace App\Models;

use App\Enums\CampaignDistributionMode;
use App\Enums\SimulationTheme;
use App\Support\SimulationThemeContent;
use Database\Factories\SimulationCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property SimulationTheme $theme
 * @property array<int, string>|null $themes
 * @property CampaignDistributionMode $distribution_mode
 * @property bool $is_active
 * @property Carbon|null $expires_at
 * @property string|null $access_code
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $creator
 * @property-read Collection<int, Respondent> $respondents
 */
#[Fillable(['slug', 'title', 'theme', 'themes', 'distribution_mode', 'is_active', 'expires_at', 'access_code', 'created_by'])]
class SimulationCampaign extends Model
{
    /** @use HasFactory<SimulationCampaignFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'theme' => SimulationTheme::class,
            'themes' => 'array',
            'distribution_mode' => CampaignDistributionMode::class,
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Respondent, $this>
     */
    public function respondents(): HasMany
    {
        return $this->hasMany(Respondent::class, 'campaign_id');
    }

    /**
     * The themes a respondent of this campaign may be shown. Falls back to the
     * single `theme` column for campaigns created before multi-theme support.
     *
     * @return array<int, SimulationTheme>
     */
    public function themeOptions(): array
    {
        if (! empty($this->themes)) {
            return array_map(fn (string $value) => SimulationTheme::from($value), $this->themes);
        }

        return [$this->theme];
    }

    /**
     * Pick one theme for a newly created respondent. With a single configured
     * theme this is deterministic; with several, it is a random draw so that
     * respondents of the same campaign don't all see the same pretext.
     */
    public function pickRandomTheme(): SimulationTheme
    {
        return Arr::random($this->themeOptions());
    }

    /**
     * Human-readable summary of the campaign's theme(s) for display.
     */
    public function themeLabel(): string
    {
        $options = $this->themeOptions();

        if (count($options) <= 1) {
            return $options[0]->label();
        }

        return 'Acak: '.collect($options)->map(fn (SimulationTheme $theme) => $theme->label())->implode(', ');
    }

    /**
     * Ready-to-paste WhatsApp/broadcast message for sharing this campaign's
     * public link — same pretext wording as the targeted email/WA send, so
     * the researcher doesn't have to write the copy themselves.
     *
     * With several configured themes, each visitor is still randomised
     * individually when they click; this preview just shows one example
     * (the campaign's primary theme) since the broadcast text is shared by
     * everyone who receives it.
     */
    public function broadcastMessage(string $publicUrl): string
    {
        $content = SimulationThemeContent::for($this->theme);

        return "*{$content['org_name']}*\n\n"
            .'Halo,'."\n\n"
            ."{$content['lead']}\n\n"
            ."{$content['warning_note']}\n\n"
            ."👉 {$publicUrl}";
    }
}
