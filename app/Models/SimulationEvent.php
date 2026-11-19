<?php

namespace App\Models;

use App\Enums\BehaviorStatus;
use App\Enums\DeviceType;
use Carbon\CarbonImmutable;
use Database\Factories\SimulationEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $respondent_id
 * @property Carbon|CarbonImmutable|null $sent_at
 * @property Carbon|CarbonImmutable|null $first_access_at
 * @property Carbon|CarbonImmutable|null $response_at
 * @property BehaviorStatus $behavior_status
 * @property string|null $last_action
 * @property bool $keystroke_detected
 * @property DeviceType|null $device_type
 * @property string|null $os_name
 * @property string|null $browser_name
 * @property string|null $ip_hash
 * @property-read Respondent $respondent
 */
#[Fillable([
    'respondent_id', 'sent_at', 'first_access_at', 'response_at', 'behavior_status', 'last_action',
    'keystroke_detected', 'device_type', 'os_name', 'browser_name', 'ip_hash',
])]
class SimulationEvent extends Model
{
    /** @use HasFactory<SimulationEventFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'first_access_at' => 'datetime',
            'response_at' => 'datetime',
            'behavior_status' => BehaviorStatus::class,
            'keystroke_detected' => 'boolean',
            'device_type' => DeviceType::class,
        ];
    }

    /**
     * @return BelongsTo<Respondent, $this>
     */
    public function respondent(): BelongsTo
    {
        return $this->belongsTo(Respondent::class);
    }

    /**
     * Human-readable final action, derived from the raw action recorded at
     * response time (works the same for link-shared and email/WA-sent
     * respondents, since both flow through the same SimulationRecorder).
     * Shared by the data export and the "jenis_tindakan" hidden field sent
     * to the Tally questionnaire.
     */
    public function jenisTindakanLabel(): string
    {
        return match ($this->last_action) {
            'submit' => 'Submit Form (Verifikasi)',
            'reject' => 'Tolak di Portal (Batal)',
            'report' => 'Laporkan Mencurigakan (dari Portal)',
            'reject_email' => 'Tolak Langsung dari Email/Pesan',
            default => match (true) {
                $this->behavior_status === BehaviorStatus::TidakMerespons => 'Tidak Merespons',
                $this->first_access_at !== null => 'Membuka Portal (Belum Lanjut)',
                default => 'Belum Ada Aktivitas',
            },
        };
    }
}
