<?php

namespace App\Models;

use App\Enums\RespondentStatus;
use App\Enums\SendChannel;
use App\Enums\SimulationTheme;
use Database\Factories\RespondentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $campaign_id
 * @property string $session_token
 * @property SimulationTheme|null $theme
 * @property string|null $class_group
 * @property string|null $name
 * @property string|null $email
 * @property string|null $whatsapp_number
 * @property SendChannel|null $send_channel
 * @property RespondentStatus $status
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SimulationCampaign|null $campaign
 * @property-read SimulationEvent|null $simulationEvent
 * @property-read QuestionnaireResult|null $questionnaireResult
 * @property-read Collection<int, ReminderLog> $reminderLogs
 */
#[Fillable(['campaign_id', 'session_token', 'theme', 'class_group', 'name', 'email', 'whatsapp_number', 'send_channel', 'status', 'expires_at'])]
class Respondent extends Model
{
    /** @use HasFactory<RespondentFactory> */
    use HasFactory, HasUuids;

    /**
     * The columns that should receive a generated UUID.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['session_token'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RespondentStatus::class,
            'theme' => SimulationTheme::class,
            'send_channel' => SendChannel::class,
            'expires_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SimulationCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SimulationCampaign::class, 'campaign_id');
    }

    /**
     * The theme actually shown to this respondent: the one picked for them at
     * creation time, falling back to the campaign's theme (for respondents
     * created before multi-theme support) and finally to the default theme.
     */
    public function effectiveTheme(): SimulationTheme
    {
        return $this->theme ?? $this->campaign?->theme ?? SimulationTheme::Elearning;
    }

    /**
     * @return HasOne<SimulationEvent, $this>
     */
    public function simulationEvent(): HasOne
    {
        return $this->hasOne(SimulationEvent::class);
    }

    /**
     * The respondent's most recent questionnaire submission. A respondent can
     * legitimately have several rows here (e.g. they submitted the Tally form
     * more than once, or a submission was re-imported under a different
     * tally_submission_id) — this always resolves to the latest one instead
     * of an arbitrary row, so scoring/export never silently picks a stale
     * or duplicate submission.
     *
     * @return HasOne<QuestionnaireResult, $this>
     */
    public function questionnaireResult(): HasOne
    {
        return $this->hasOne(QuestionnaireResult::class)->latestOfMany(['submitted_at', 'id']);
    }

    /**
     * @return HasMany<ReminderLog, $this>
     */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }
}
