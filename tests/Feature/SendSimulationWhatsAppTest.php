<?php

namespace Tests\Feature;

use App\Enums\RespondentStatus;
use App\Enums\SendChannel;
use App\Jobs\SendSimulationWhatsApp;
use App\Models\Respondent;
use App\Services\FonnteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendSimulationWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_via_fonnte_and_marks_the_respondent_sent(): void
    {
        $respondent = Respondent::factory()->create([
            'status' => RespondentStatus::Pending,
            'send_channel' => SendChannel::WhatsApp,
            'whatsapp_number' => '628123456789',
            'email' => null,
        ]);

        $fonnte = Mockery::mock(FonnteService::class);
        $fonnte->shouldReceive('send')
            ->once()
            ->with('628123456789', Mockery::type('string'))
            ->andReturn(true);

        app()->instance(FonnteService::class, $fonnte);

        (new SendSimulationWhatsApp($respondent))->handle($fonnte);

        $this->assertSame(RespondentStatus::Sent, $respondent->fresh()->status);
        $this->assertNotNull($respondent->simulationEvent->fresh()->sent_at);
    }

    public function test_it_throws_when_fonnte_fails_so_the_job_can_retry(): void
    {
        $respondent = Respondent::factory()->create([
            'status' => RespondentStatus::Pending,
            'send_channel' => SendChannel::WhatsApp,
            'whatsapp_number' => '628123456789',
            'email' => null,
        ]);

        $fonnte = Mockery::mock(FonnteService::class);
        $fonnte->shouldReceive('send')->once()->andReturn(false);

        $this->expectException(\RuntimeException::class);

        (new SendSimulationWhatsApp($respondent))->handle($fonnte);
    }

    public function test_it_does_nothing_if_the_respondent_is_no_longer_pending(): void
    {
        $respondent = Respondent::factory()->create([
            'status' => RespondentStatus::Sent,
            'whatsapp_number' => '628123456789',
        ]);

        $fonnte = Mockery::mock(FonnteService::class);
        $fonnte->shouldNotReceive('send');

        (new SendSimulationWhatsApp($respondent))->handle($fonnte);
    }
}
