<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\CompletionStatus;
use App\Enums\RespondentStatus;
use App\Http\Controllers\Controller;
use App\Models\Respondent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TallyWebhookController extends Controller
{
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

        $respondent->update([
            'status' => $respondent->simulationEvent()->exists()
                ? RespondentStatus::Finished
                : RespondentStatus::CompletedQuestionnaire,
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
            $key = Str::lower((string) ($field['key'] ?? $field['label'] ?? ''));

            if (Str::contains($key, 'session_token') || $key === 'token') {
                return is_string($field['value'] ?? null) ? $field['value'] : null;
            }
        }

        return null;
    }

    /**
     * Sort Tally fields into KAB buckets using key/label keyword prefixes.
     *
     * Convention: prefix field keys with k_/a_/b_ (or include the words
     * pengetahuan/knowledge, sikap/attitude, perilaku/behavior). Anything that
     * cannot be categorised is preserved under behavior['_uncategorized'].
     *
     * @param  array<int, array<string, mixed>>  $fields
     * @return array{knowledge: array<string, mixed>, attitude: array<string, mixed>, behavior: array<string, mixed>}
     */
    private function categoriseFields(array $fields): array
    {
        $buckets = ['knowledge' => [], 'attitude' => [], 'behavior' => []];

        foreach ($fields as $field) {
            $key = (string) ($field['key'] ?? $field['label'] ?? '');
            $normalised = Str::lower($key);
            $value = $field['value'] ?? null;

            if (Str::contains($normalised, 'session_token') || $normalised === 'token') {
                continue;
            }

            $bucket = match (true) {
                Str::startsWith($normalised, 'k_') || Str::contains($normalised, ['knowledge', 'pengetahuan']) => 'knowledge',
                Str::startsWith($normalised, 'a_') || Str::contains($normalised, ['attitude', 'sikap']) => 'attitude',
                Str::startsWith($normalised, 'b_') || Str::contains($normalised, ['behavior', 'behaviour', 'perilaku']) => 'behavior',
                default => null,
            };

            if ($bucket === null) {
                $buckets['behavior']['_uncategorized'][$key] = $value;

                continue;
            }

            $buckets[$bucket][$key] = $value;
        }

        return $buckets;
    }
}
