<?php

namespace App\Models;

use App\Enums\RespondentStatus;
use Database\Factories\RespondentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $session_token
 * @property string $class_group
 * @property string|null $name
 * @property string $email
 * @property string|null $whatsapp_number
 * @property RespondentStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SimulationEvent|null $simulationEvent
 * @property-read QuestionnaireResult|null $questionnaireResult
 * @property-read Collection<int, ReminderLog> $reminderLogs
 */
#[Fillable(['session_token', 'class_group', 'name', 'email', 'whatsapp_number', 'status'])]
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
        ];
    }

    /**
     * @return HasOne<SimulationEvent, $this>
     */
    public function simulationEvent(): HasOne
    {
        return $this->hasOne(SimulationEvent::class);
    }

    /**
     * @return HasOne<QuestionnaireResult, $this>
     */
    public function questionnaireResult(): HasOne
    {
        return $this->hasOne(QuestionnaireResult::class);
    }

    /**
     * @return HasMany<ReminderLog, $this>
     */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }
}
