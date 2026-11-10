<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Sorts a Tally question (by its key and/or label) into the Knowledge /
 * Attitude / Behavior bucket it belongs to. Shared by the live webhook
 * (TallyWebhookController) and the CSV import fallback, so both apply the
 * exact same convention.
 *
 * Convention: key starts with k/a/b immediately followed by a digit or an
 * underscore (K1, K_1, ...), or the key/label contains a full keyword
 * (pengetahuan/knowledge, sikap/attitude, perilaku/behavior).
 */
class TallyAnswerCategoriser
{
    /**
     * @return 'knowledge'|'attitude'|'behavior'|null
     */
    public function categoriseKey(string $key, string $label = ''): ?string
    {
        $normalisedKey = Str::lower($key);
        $normalisedLabel = Str::lower($label);

        return match (true) {
            preg_match('/^k[_\d]/', $normalisedKey) === 1 || Str::contains($normalisedLabel, ['knowledge', 'pengetahuan']) => 'knowledge',
            preg_match('/^a[_\d]/', $normalisedKey) === 1 || Str::contains($normalisedLabel, ['attitude', 'sikap']) => 'attitude',
            preg_match('/^b[_\d]/', $normalisedKey) === 1 || Str::contains($normalisedLabel, ['behavior', 'behaviour', 'perilaku']) => 'behavior',
            default => null,
        };
    }

    /**
     * Whether a column identifies the submission/session rather than being an answer.
     */
    public function isMeta(string $key): bool
    {
        $normalised = Str::lower(trim($key));

        return Str::contains($normalised, 'session_token')
            || $normalised === 'token'
            || Str::contains($normalised, ['submission id', 'respondent id', 'submitted at']);
    }

    /**
     * Bucket a flat row of column => value pairs (e.g. one CSV row) into
     * knowledge/attitude/behavior, same as the live webhook does for a
     * Tally field list. Unkeyed/unmatched columns are kept, not dropped,
     * under behavior['_uncategorized'].
     *
     * @param  array<string, mixed>  $row
     * @return array{knowledge: array<string, mixed>, attitude: array<string, mixed>, behavior: array<string, mixed>}
     */
    public function categoriseRow(array $row): array
    {
        $buckets = ['knowledge' => [], 'attitude' => [], 'behavior' => []];

        foreach ($row as $key => $value) {
            $key = (string) $key;

            if ($this->isMeta($key) || $value === null || $value === '') {
                continue;
            }

            $bucket = $this->categoriseKey($key);

            if ($bucket === null) {
                $buckets['behavior']['_uncategorized'][$key] = $value;

                continue;
            }

            $buckets[$bucket][$key] = $value;
        }

        return $buckets;
    }

    /**
     * Flatten one KAB bucket (e.g. a questionnaire result's
     * knowledge_answers) into a flat key => value map, unwrapping
     * `_uncategorized` so every question — keyed or not — is a top-level
     * entry.
     *
     * @param  array<string, mixed>|null  $bucket
     * @return array<string, mixed>
     */
    public function flattenBucket(?array $bucket): array
    {
        if ($bucket === null) {
            return [];
        }

        $flat = [];

        foreach ($bucket as $key => $value) {
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
     * Find the respondent's class from a submission row/field list (the
     * "Kelas" question in the questionnaire), case-insensitively. Returns
     * the first non-blank match — a blank hidden "kelas" field (never
     * populated by the system) is skipped in favour of the real question.
     *
     * @param  array<string, mixed>  $row
     */
    public function extractClassGroup(array $row): ?string
    {
        foreach ($row as $key => $value) {
            if (Str::lower(trim((string) $key)) === 'kelas' && filled($value)) {
                return trim((string) (is_array($value) ? ($value[0] ?? '') : $value)) ?: null;
            }
        }

        return null;
    }
}
