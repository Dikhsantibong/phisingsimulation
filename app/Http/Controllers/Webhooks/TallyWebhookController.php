<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\CompletionStatus;
use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\Respondent;
use App\Services\TallyAnswerCategoriser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TallyWebhookController extends Controller
{
    public function __construct(private readonly TallyAnswerCategoriser $categoriser) {}

    /**
     * Receive a Tally form submission and link it back to a respondent.
     *
     * Idempotent on the Tally submission id, matched to a respondent via the
     * `session_token` hidden field carried through from the reveal redirect.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->signatureIsValid($request)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $data = (array) $request->input('data', []);
        $fields = (array) ($data['fields'] ?? []);

        $submissionId = $data['submissionId'] ?? $data['responseId'] ?? null;
        $sessionToken = $this->extractSessionToken($fields);

        if (blank($submissionId) || blank($sessionToken)) {
            return response()->json(['message' => 'Missing submission id or session token.'], 422);
        }

        $respondent = Respondent::where('session_token', $sessionToken)->first();

        if ($respondent === null) {
            return response()->json(['message' => 'Unknown respondent.'], 404);
        }

        $buckets = $this->categoriseFields($fields);

        $respondent->questionnaireResult()->updateOrCreate(
            ['tally_submission_id' => $submissionId],
            [
                'completion_status' => CompletionStatus::Selesai,
                'knowledge_answers' => $buckets['knowledge'],
                'attitude_answers' => $buckets['attitude'],
                'behavior_answers' => $buckets['behavior'],
                'submitted_at' => now(),
            ]
        );

        $classGroup = $this->categoriser->extractClassGroup(array_merge(
            $this->categoriser->flattenBucket($buckets['knowledge']),
            $this->categoriser->flattenBucket($buckets['attitude']),
            $this->categoriser->flattenBucket($buckets['behavior']),
        ));

        $respondent->update([
            'status' => $respondent->simulationEvent()->exists()
                ? RespondentStatus::Finished
                : RespondentStatus::CompletedQuestionnaire,
            ...($classGroup !== null ? ['class_group' => $classGroup] : []),
        ]);

        return response()->json(['message' => 'ok']);
    }

    /**
     * Verify the Tally HMAC signature when a signing secret is configured.
     */
    private function signatureIsValid(Request $request): bool
    {
        $secret = config('services.simulation.tally_signing_secret');

        if (blank($secret)) {
            return true;
        }

        $signature = $request->header('tally-signature');

        if (blank($signature)) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));

        return hash_equals($expected, $signature);
    }

    /**
     * Find the session token from Tally's hidden fields.
     *
     * @param  array<int, array<string, mixed>>  $fields
     */
    private function extractSessionToken(array $fields): ?string
    {
        foreach ($fields as $field) {
            $key = Str::lower((string) ($field['key'] ?? ''));
            $label = Str::lower((string) ($field['label'] ?? ''));

            if (Str::contains($key, 'session_token') || $key === 'token' || Str::contains($label, 'session_token') || $label === 'token') {
                return is_string($field['value'] ?? null) ? $field['value'] : null;
            }
        }

        return null;
    }

    /**
     * Sort Tally fields into KAB buckets using the short key the researcher
     * set per question in Tally (e.g. "K1", "A_2", "B3").
     *
     * Convention: key starts with k/a/b immediately followed by a digit or an
     * underscore (K1, K_1, ...), or the key/label contains a full keyword
     * (pengetahuan/knowledge, sikap/attitude, perilaku/behavior). Anything
     * that cannot be categorised is preserved under behavior['_uncategorized'].
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array{knowledge: array<string, mixed>, attitude: array<string, mixed>, behavior: array<string, mixed>}
     */
    private function categoriseFields(array $fields): array
    {
        $buckets = ['knowledge' => [], 'attitude' => [], 'behavior' => []];

        foreach ($fields as $field) {
            $keyText = (string) ($field['key'] ?? '');
            $labelText = (string) ($field['label'] ?? '');

            // The short key researchers set in Tally (e.g. "K1") is the
            // column name we want in the dataset; the label is usually the
            // full (long) question text and only used as a fallback.
            $storageKey = $keyText !== '' ? $keyText : $labelText;

            $value = $this->resolveFieldValue($field);

            if ($this->categoriser->isMeta($keyText) || $this->categoriser->isMeta($labelText)) {
                continue;
            }

            $bucket = $this->categoriser->categoriseKey($keyText, $labelText);

            if ($bucket === null) {
                $buckets['behavior']['_uncategorized'][$storageKey] = $value;

                continue;
            }

            $buckets[$bucket][$storageKey] = $value;
        }

        return $buckets;
    }

    /**
     * Resolve a field's answer to a human-readable value.
     *
     * Multiple-choice/dropdown fields send `value` as the selected option's
     * internal id (or an array of ids), not its text — the readable label
     * lives in the field's own `options` list (`[{id, text}, ...]`). Rating
     * and open-text fields have no `options` and are returned as-is.
     *
     * @param  array<string, mixed>  $field
     */
    private function resolveFieldValue(array $field): mixed
    {
        $value = $field['value'] ?? null;
        $options = (array) ($field['options'] ?? []);

        if ($options === [] || $value === null) {
            return $value;
        }

        $labelsById = collect($options)->mapWithKeys(fn (array $option) => [
            (string) ($option['id'] ?? '') => $option['text'] ?? $option['label'] ?? null,
        ]);

        if (is_array($value)) {
            $resolved = collect($value)
                ->map(fn ($id) => $labelsById[(string) $id] ?? $id)
                ->all();

            // A single-select question still arrives as a one-element array;
            // store it as a plain string so it's directly gradeable/comparable.
            return count($resolved) === 1 ? $resolved[0] : $resolved;
        }

        return $labelsById[(string) $value] ?? $value;
    }
}
