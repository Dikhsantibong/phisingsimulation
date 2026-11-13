<?php

namespace Tests\Feature;

use App\Services\FonnteService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteServiceTest extends TestCase
{
    /**
     * Fonnte replies with HTTP 200 and a JSON body for a genuinely accepted
     * message: {"status": true, ...}.
     */
    public function test_send_returns_true_when_fonnte_accepts_the_message(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response([
                'status' => true,
                'detail' => 'Success! message in queue',
                'id' => ['80367170'],
                'target' => ['628123456789'],
            ], 200),
        ]);

        $fonnte = new FonnteService('fake-token', 'https://api.fonnte.com/send');

        $this->assertTrue($fonnte->send('628123456789', 'Halo'));
    }

    /**
     * Fonnte still replies with HTTP 200 even when it rejects the message
     * (e.g. invalid token, insufficient quota, bad target) — the real
     * outcome only shows up in the JSON body's `status` field. Treating any
     * 2xx response as success would silently mark these as sent.
     */
    public function test_send_returns_false_when_fonnte_rejects_the_message_despite_http_200(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response([
                'status' => false,
                'reason' => 'insufficient quota',
            ], 200),
        ]);

        $fonnte = new FonnteService('fake-token', 'https://api.fonnte.com/send');

        $this->assertFalse($fonnte->send('628123456789', 'Halo'));
    }

    /**
     * One documented Fonnte error case (invalid token) uses the differently
     * cased key "Status" instead of "status" — must still be recognised as
     * a failure.
     */
    public function test_send_returns_false_for_the_capitalised_status_key_variant(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response([
                'Status' => false,
                'reason' => 'token invalid',
            ], 200),
        ]);

        $fonnte = new FonnteService('fake-token', 'https://api.fonnte.com/send');

        $this->assertFalse($fonnte->send('628123456789', 'Halo'));
    }

    public function test_send_returns_false_on_an_http_error_response(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $fonnte = new FonnteService('fake-token', 'https://api.fonnte.com/send');

        $this->assertFalse($fonnte->send('628123456789', 'Halo'));
    }

    public function test_send_returns_false_when_no_token_is_configured(): void
    {
        Http::fake();

        $fonnte = new FonnteService(null, 'https://api.fonnte.com/send');
        config(['services.fonnte.token' => null]);

        $this->assertFalse($fonnte->send('628123456789', 'Halo'));
        Http::assertNothingSent();
    }
}
