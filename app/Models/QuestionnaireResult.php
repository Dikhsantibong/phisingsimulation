<?php

namespace App\Models;

use App\Enums\CompletionStatus;
use Database\Factories\QuestionnaireResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $respondent_id
 * @property string $tally_submission_id
 * @property CompletionStatus $completion_status
 * @property array<string, mixed>|null $knowledge_answers
 * @property array<string, mixed>|null $attitude_answers
 * @property array<string, mixed>|null $behavior_answers
 * @property Carbon|null $submitted_at
 * @property-read Respondent $respondent
 */
#[Fillable([
    'respondent_id', 'tally_submission_id', 'completion_status',
    'knowledge_answers', 'attitude_answers', 'behavior_answers', 'submitted_at',
])]
class QuestionnaireResult extends Model
{
    /** @use HasFactory<QuestionnaireResultFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completion_status' => CompletionStatus::class,
            'knowledge_answers' => 'array',
            'attitude_answers' => 'array',
            'behavior_answers' => 'array',
            'submitted_at' => 'datetime',
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
