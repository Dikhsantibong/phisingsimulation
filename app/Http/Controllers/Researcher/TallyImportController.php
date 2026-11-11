<?php

namespace App\Http\Controllers\Researcher;

use App\Enums\CompletionStatus;
use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\Respondent;
use App\Services\TallyAnswerCategoriser;
use App\Support\MinimalXlsxReader;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Inertia\Inertia;
use RuntimeException;
use Throwable;

class TallyImportController extends Controller
{
    /**
     * Import a submissions file exported directly from Tally — CSV or Excel
     * (fallback for when the live webhook can't reach this server, e.g.
     * local development or a broken production endpoint). Matches rows to
     * respondents by session_token and fills questionnaire_results the same
     * way the webhook would.
     */
    public function store(Request $request, TallyAnswerCategoriser $categoriser): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $file = $request->file('file');
        $isXlsx = Str::lower($file->getClientOriginalExtension()) === 'xlsx';

        try {
            $rows = $isXlsx
                ? MinimalXlsxReader::read($file->getRealPath())
                : $this->readCsv($file->getRealPath());
        } catch (RuntimeException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        if ($rows === [] || $rows[0] === []) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'File kosong atau formatnya tidak dikenali.']);

            return back();
        }

        $header = $rows[0];
        $header[0] = preg_replace('/^\x{FEFF}/u', '', (string) $header[0]); // strip a UTF-8 BOM, if present

        $sessionTokenColumn = $this->findColumn($header, ['session_token']);
        $submissionIdColumn = $this->findColumn($header, ['submission id', 'submission_id', 'response id', 'response_id']);
        $submittedAtColumn = $this->findColumn($header, ['submitted at', 'submitted_at']);

        if ($sessionTokenColumn === null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Tidak ditemukan kolom "session_token" di file ini.']);

            return back();
        }

        $matched = 0;
        $unmatched = 0;
        $rowCount = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $rowCount++;

            // A malformed row (extra/missing columns) must not crash the
            // import: pad short rows and truncate long ones to the header's
            // column count so array_combine always receives equal lengths.
            $row = array_slice(array_pad($rows[$i], count($header), null), 0, count($header));
            $assoc = array_combine($header, $row);

            $sessionToken = trim((string) ($assoc[$sessionTokenColumn] ?? ''));

            $respondent = $sessionToken !== ''
                ? Respondent::where('session_token', $sessionToken)->first()
                : null;

            if ($respondent === null) {
                $unmatched++;

                continue;
            }

            $submissionId = $submissionIdColumn !== null && filled($assoc[$submissionIdColumn] ?? null)
                ? (string) $assoc[$submissionIdColumn]
                : 'import-'.$respondent->session_token;

            $buckets = $categoriser->categoriseRow($assoc);

            $respondent->questionnaireResult()->updateOrCreate(
                ['tally_submission_id' => $submissionId],
                [
                    'completion_status' => CompletionStatus::Selesai,
                    'knowledge_answers' => $buckets['knowledge'],
                    'attitude_answers' => $buckets['attitude'],
                    'behavior_answers' => $buckets['behavior'],
                    'submitted_at' => $this->parseSubmittedAt($submittedAtColumn, $assoc),
                ]
            );

            $classGroup = $categoriser->extractClassGroup($assoc);

            $respondent->update([
                'status' => $respondent->simulationEvent()->exists()
                    ? RespondentStatus::Finished
                    : RespondentStatus::CompletedQuestionnaire,
                ...($classGroup !== null ? ['class_group' => $classGroup] : []),
            ]);

            $matched++;
        }

        Inertia::flash('toast', [
            'type' => $matched > 0 ? 'success' : 'error',
            'message' => "{$matched} responden dicocokkan dan diperbarui, {$unmatched} baris tidak cocok (dari {$rowCount} baris total).",
        ]);

        return back();
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Gagal membaca file CSV.');
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Use the submission's real "Submitted at" timestamp from the Tally
     * export when present — importing a file long after the fact must not
     * make every row look like it was submitted "just now", since that
     * timestamp is what later decides which of several submissions for the
     * same respondent is the most recent one.
     *
     * @param  array<string, mixed>  $row
     */
    private function parseSubmittedAt(?string $column, array $row): CarbonInterface
    {
        if ($column === null || blank($row[$column] ?? null)) {
            return now();
        }

        try {
            return Date::parse((string) $row[$column]);
        } catch (Throwable) {
            return now();
        }
    }

    /**
     * Find a header column by a case-insensitive substring match against
     * any of the given candidates.
     *
     * @param  array<int, string|null>  $header
     * @param  array<int, string>  $candidates
     */
    private function findColumn(array $header, array $candidates): ?string
    {
        foreach ($header as $column) {
            $normalised = Str::lower(trim((string) $column));

            foreach ($candidates as $candidate) {
                if (Str::contains($normalised, $candidate)) {
                    return (string) $column;
                }
            }
        }

        return null;
    }
}
