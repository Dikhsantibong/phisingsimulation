<?php

namespace App\Http\Controllers\Researcher;

use App\Enums\BehaviorStatus;
use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\QuestionnaireResult;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use App\Services\TallyAnswerCategoriser;
use App\Support\AwarenessScorer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    public function __construct(private readonly TallyAnswerCategoriser $categoriser) {}

    /**
     * Show the export page.
     */
    public function index(): Response
    {
        return Inertia::render('data-export', [
            'total' => Respondent::count(),
            // Finished the simulation but no questionnaire answers yet — most
            // likely the Tally webhook hasn't reached this server for them;
            // surfaced so scores don't go quietly missing from the export.
            'pendingQuestionnaire' => Respondent::whereIn('status', [
                RespondentStatus::CompletedBehavior,
            ])->whereDoesntHave('questionnaireResult')->count(),
        ]);
    }

    /**
     * Stream the raw merged dataset as a CSV: every system-tracked field plus
     * one column per questionnaire question, ready for manual/automatic
     * per-question or per-category review.
     */
    public function download(Request $request): StreamedResponse
    {
        $anonymise = $request->boolean('anonymise');
        $questionColumns = $this->questionColumns();

        $headers = array_merge(
            ['no_responden', 'session_token', 'campaign', 'theme', 'kelas'],
            $anonymise ? [] : ['name', 'email', 'whatsapp_number'],
            [
                'status',
                'first_access_at', 'response_at',
                'seconds_to_click', 'seconds_to_respond',
                'behavior_status', 'jenis_tindakan', 'mengetik_form',
                'device_type', 'os_name', 'browser_name', 'ip_hash',
                'reminder_count',
                'questionnaire_status', 'submitted_at',
            ],
            $questionColumns
        );

        $filename = 'dataset-simulasi-phishing-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $anonymise, $questionColumns) {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            // UTF-8 BOM so Excel opens the file with correct encoding.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            Respondent::query()
                ->with(['campaign', 'simulationEvent', 'questionnaireResult'])
                ->withCount('reminderLogs')
                ->chunk(200, function ($respondents) use ($out, $anonymise, $questionColumns) {
                    foreach ($respondents as $respondent) {
                        fputcsv($out, $this->row($respondent, $anonymise, $questionColumns));
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Stream the scored dataset: system fields unchanged, but the
     * questionnaire + simulation answers are replaced with the computed
     * Total Knowledge / Attitude / Behavior / Simulasi Score and the final
     * Tingkat Kesadaran classification (per "Rumus perhitungan KAB dan
     * simulasi").
     */
    public function downloadScored(Request $request): StreamedResponse
    {
        $anonymise = $request->boolean('anonymise');

        $headers = array_merge(
            ['no_responden', 'session_token', 'campaign', 'theme', 'kelas'],
            $anonymise ? [] : ['name', 'email', 'whatsapp_number'],
            [
                'status',
                'first_access_at', 'response_at',
                'seconds_to_click', 'seconds_to_respond',
                'behavior_status', 'jenis_tindakan', 'mengetik_form',
                'device_type', 'os_name', 'browser_name',
                'total_knowledge', 'total_attitude', 'total_behavior', 'simulasi_score',
                'tingkat_kesadaran_score', 'tingkat_kesadaran',
            ]
        );

        $filename = 'dataset-skor-kesadaran-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $anonymise) {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            Respondent::query()
                ->with(['campaign', 'simulationEvent', 'questionnaireResult'])
                ->chunk(200, function ($respondents) use ($out, $anonymise) {
                    foreach ($respondents as $respondent) {
                        fputcsv($out, $this->scoredRow($respondent, $anonymise));
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @return array<int, string|int|float|null>
     */
    private function scoredRow(Respondent $respondent, bool $anonymise): array
    {
        $event = $respondent->simulationEvent;
        $result = $respondent->questionnaireResult;

        $answers = $result
            ? array_merge(
                $this->flattenAnswers($result->knowledge_answers),
                $this->flattenAnswers($result->attitude_answers),
                $this->flattenAnswers($result->behavior_answers),
            )
            : [];

        $knowledge = AwarenessScorer::knowledgeScore($answers);
        $attitude = AwarenessScorer::attitudeScore($answers);
        $behavior = AwarenessScorer::behaviorQuestionnaireScore($answers);
        $simulation = AwarenessScorer::simulationScore($event?->behavior_status);
        $total = AwarenessScorer::awarenessTotal($knowledge, $attitude, $behavior, $simulation);

        $secondsToClick = ($event && $event->sent_at && $event->first_access_at)
            ? $event->first_access_at->diffInSeconds($event->sent_at)
            : null;

        $secondsToRespond = ($event && $event->first_access_at && $event->response_at)
            ? $event->response_at->diffInSeconds($event->first_access_at)
            : null;

        return array_merge(
            [$this->noResponden($respondent), $respondent->session_token, $respondent->campaign?->title, $respondent->effectiveTheme()->value, $this->kelasFor($respondent, $answers)],
            $anonymise ? [] : [$respondent->name, $respondent->email, $respondent->whatsapp_number],
            [
                $respondent->status->value,
                $event?->first_access_at?->toIso8601String(),
                $event?->response_at?->toIso8601String(),
                $secondsToClick,
                $secondsToRespond,
                $event?->behavior_status?->value,
                $this->jenisTindakanLabel($event),
                $event ? (int) $event->keystroke_detected : null,
                $event?->device_type?->value,
                $event?->os_name,
                $event?->browser_name,
                $knowledge,
                $attitude,
                $behavior,
                $simulation,
                $total,
                AwarenessScorer::awarenessLevel($total),
            ]
        );
    }

    /**
     * Every distinct question key (e.g. "K1", "A1", "B1") seen across all
     * questionnaire submissions so far, in a stable, human-friendly order —
     * each becomes its own CSV column instead of one merged JSON blob.
     *
     * @return array<int, string>
     */
    private function questionColumns(): array
    {
        $keys = collect();

        QuestionnaireResult::query()
            ->select(['knowledge_answers', 'attitude_answers', 'behavior_answers'])
            ->each(function (QuestionnaireResult $result) use ($keys) {
                $keys->push(...array_keys($this->flattenAnswers($result->knowledge_answers)));
                $keys->push(...array_keys($this->flattenAnswers($result->attitude_answers)));
                $keys->push(...array_keys($this->flattenAnswers($result->behavior_answers)));
            });

        $unique = $keys->unique()->values()->all();
        natsort($unique);

        return array_values($unique);
    }

    /**
     * Flatten one KAB bucket into a flat key => value map, unwrapping the
     * `_uncategorized` sub-array (unkeyed Tally fields) so every question
     * — keyed or not — ends up as its own column.
     *
     * @param  array<string, mixed>|null  $answers
     * @return array<string, mixed>
     */
    private function flattenAnswers(?array $answers): array
    {
        if ($answers === null) {
            return [];
        }

        $flat = [];

        foreach ($answers as $key => $value) {
            if ($key === '_uncategorized' && is_array($value)) {
                foreach ($value as $innerKey => $innerValue) {
                    $flat[$innerKey] = $innerValue;
                }

                continue;
            }

            $flat[$key] = $value;
        }

        return $flat;
    }

    /**
     * Short, stable display label for a respondent (e.g. "SID-007") — based
     * on the database id, so it always points to the same respondent across
     * exports, unlike a position-in-file row number. This is purely a
     * display convenience: the real `session_token` column (used in URLs)
     * stays a full random UUID, so links stay unguessable.
     */
    private function noResponden(Respondent $respondent): string
    {
        return sprintf('SID-%03d', $respondent->id);
    }

    /**
     * The respondent's class: prefer the "Kelas" question answered in Tally
     * (the researcher reports this is more reliable than the portal's own
     * class field), falling back to `class_group` for respondents sent
     * manually with a known class but no questionnaire answer yet.
     *
     * @param  array<string, mixed>  $answers
     */
    private function kelasFor(Respondent $respondent, array $answers): ?string
    {
        return $this->categoriser->extractClassGroup($answers) ?? $respondent->class_group;
    }

    /**
     * Human-readable final action, derived from the raw action recorded at
     * response time (works the same for link-shared and email/WA-sent
     * respondents, since both flow through the same SimulationRecorder).
     */
    private function jenisTindakanLabel(?SimulationEvent $event): string
    {
        if ($event === null) {
            return 'Belum Ada Aktivitas';
        }

        return match ($event->last_action) {
            'submit' => 'Submit Form (Verifikasi)',
            'reject' => 'Tolak di Portal (Batal)',
            'report' => 'Tolak Langsung dari Email/Pesan',
            default => match (true) {
                $event->behavior_status === BehaviorStatus::TidakMerespons => 'Tidak Merespons',
                $event->first_access_at !== null => 'Membuka Portal (Belum Lanjut)',
                default => 'Belum Ada Aktivitas',
            },
        };
    }

    /**
     * Flatten one respondent (+ related data) into a CSV row.
     *
     * @param  array<int, string>  $questionColumns
     * @return array<int, string|int|float|bool|false|null>
     */
    private function row(Respondent $respondent, bool $anonymise, array $questionColumns): array
    {
        $event = $respondent->simulationEvent;
        $result = $respondent->questionnaireResult;

        $secondsToClick = ($event && $event->sent_at && $event->first_access_at)
            ? $event->first_access_at->diffInSeconds($event->sent_at)
            : null;

        $secondsToRespond = ($event && $event->first_access_at && $event->response_at)
            ? $event->response_at->diffInSeconds($event->first_access_at)
            : null;

        $answers = $result
            ? array_merge(
                $this->flattenAnswers($result->knowledge_answers),
                $this->flattenAnswers($result->attitude_answers),
                $this->flattenAnswers($result->behavior_answers),
            )
            : [];

        return array_merge(
            [$this->noResponden($respondent), $respondent->session_token, $respondent->campaign?->title, $respondent->effectiveTheme()->value, $this->kelasFor($respondent, $answers)],
            $anonymise ? [] : [$respondent->name, $respondent->email, $respondent->whatsapp_number],
            [
                $respondent->status->value,
                $event?->first_access_at?->toIso8601String(),
                $event?->response_at?->toIso8601String(),
                $secondsToClick,
                $secondsToRespond,
                $event?->behavior_status?->value,
                $this->jenisTindakanLabel($event),
                $event ? (int) $event->keystroke_detected : null,
                $event?->device_type?->value,
                $event?->os_name,
                $event?->browser_name,
                $event?->ip_hash,
                $respondent->reminder_logs_count,
                $result?->completion_status?->value,
                $result?->submitted_at?->toIso8601String(),
            ],
            array_map(fn (string $column) => $this->formatAnswerValue($answers[$column] ?? null), $questionColumns)
        );
    }

    /**
     * Render one answer value as a single CSV cell (multi-select answers
     * arrive as an array of option texts and are joined into one string).
     */
    private function formatAnswerValue(mixed $value): string|int|float|bool|null
    {
        if (is_array($value)) {
            return implode(', ', array_map('strval', $value));
        }

        return $value;
    }
}
