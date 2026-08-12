<?php

namespace App\Models;

use App\Enums\ReminderChannel;
use App\Enums\ReminderType;
use Database\Factories\ReminderLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $respondent_id
 * @property ReminderType $reminder_type
 * @property ReminderChannel $channel
 * @property Carbon $scheduled_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $followed_up_at
 * @property int $attempt_number
 * @property-read Respondent $respondent
 */
#[Fillable([
    'respondent_id', 'reminder_type', 'channel', 'scheduled_at', 'sent_at', 'followed_up_at', 'attempt_number',
])]
class ReminderLog extends Model
{
    /** @use HasFactory<ReminderLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reminder_type' => ReminderType::class,
            'channel' => ReminderChannel::class,
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'followed_up_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Respondent, $this>
     */
    public function respondent(): BelongsTo
    {
        return $this->belongsTo(Respondent::class);
    }
}
