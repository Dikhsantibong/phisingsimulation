<?php

namespace App\Http\Controllers\Researcher;

use App\Http\Controllers\Controller;
use App\Models\Respondent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    /**
     * Show the export page.
     */
    public function index(): Response
    {
        return Inertia::render('data-export', [
            'total' => Respondent::count(),
        ]);
    }

    /**
     * Stream the merged study dataset as a CSV ready for the Random Forest notebook.
     */
    public function download(Request $request): StreamedResponse
    {
        $anonymise = $request->boolean('anonymise');

        $headers = array_merge(
            ['session_token', 'class_group'],
            $anonymise ? [] : ['name', 'email', 'whatsapp_number'],
            [
                'status',
                'sent_at', 'first_access_at', 'response_at',
                'seconds_to_click', 'seconds_to_respond',
                'behavior_status', 'keystroke_detected',
                'device_type', 'os_name', 'browser_name', 'ip_hash',
                'reminder_count',
                'questionnaire_status', 'submitted_at',
                'knowledge_answers', 'attitude_answers', 'behavior_answers',
            ]
        );

        $filename = 'dataset-simulasi-phishing-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $anonymise) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens the file with correct encoding.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            Respondent::query()
                ->with(['simulationEvent', 'questionnaireResult'])
                ->withCount('reminderLogs')
                ->chunk(200, function ($respondents) use ($out, $anonymise) {
                    foreach ($respondents as $respondent) {
                        fputcsv($out, $this->row($respondent, $anonymise));
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Flatten one respondent (+ related data) into a CSV row.
     *
     * @return array<int, string|int|null>
     */
    private function row(Respondent $respondent, bool $anonymise): array
    {
        $event = $respondent->simulationEvent;
        $result = $respondent->questionnaireResult;

        $secondsToClick = ($event?->sent_at && $event?->first_access_at)
            ? $event->first_access_at->diffInSeconds($event->sent_at)
            : null;

        $secondsToRespond = ($event?->first_access_at && $event?->response_at)
            ? $event->response_at->diffInSeconds($event->first_access_at)
            : null;

        return array_merge(
            [$respondent->session_token, $respondent->class_group],
            $anonymise ? [] : [$respondent->name, $respondent->email, $respondent->whatsapp_number],
            [
                $respondent->status->value,
                $event?->sent_at?->toIso8601String(),
                $event?->first_access_at?->toIso8601String(),
                $event?->response_at?->toIso8601String(),
                $secondsToClick,
                $secondsToRespond,
                $event?->behavior_status?->value,
                $event ? (int) $event->keystroke_detected : null,
                $event?->device_type?->value,
                $event?->os_name,
                $event?->browser_name,
                $event?->ip_hash,
                $respondent->reminder_logs_count,
                $result?->completion_status?->value,
                $result?->submitted_at?->toIso8601String(),
                $result ? json_encode($result->knowledge_answers, JSON_UNESCAPED_UNICODE) : null,
                $result ? json_encode($result->attitude_answers, JSON_UNESCAPED_UNICODE) : null,
                $result ? json_encode($result->behavior_answers, JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }
}
