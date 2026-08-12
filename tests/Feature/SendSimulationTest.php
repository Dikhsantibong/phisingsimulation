<?php

namespace Tests\Feature;

use App\Enums\RespondentStatus;
use App\Mail\SimulationPhishingMail;
use App\Models\Respondent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_research_key_creates_respondents_and_sends_email(): void
    {
        Mail::fake();
        $user = User::factory()->withResearchKey('kunci-riset')->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'kunci-riset',
            'respondents' => [
                ['name' => 'Budi', 'class_group' => 'XII IPA 1', 'email' => 'budi@example.com', 'whatsapp_number' => '628123'],
            ],
        ])->assertRedirect(route('respondents.index'));

        $respondent = Respondent::where('email', 'budi@example.com')->first();
        $this->assertNotNull($respondent);
        $this->assertSame(RespondentStatus::Sent, $respondent->fresh()->status);
        Mail::assertSent(SimulationPhishingMail::class);
    }

    public function test_invalid_research_key_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->withResearchKey('kunci-riset')->create();

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'salah',
            'respondents' => [
                ['class_group' => 'XII IPA 1', 'email' => 'budi@example.com'],
            ],
        ])->assertSessionHasErrors('research_key');

        $this->assertSame(0, Respondent::count());
        Mail::assertNothingSent();
    }

    public function test_send_requires_a_configured_research_key(): void
    {
        $user = User::factory()->create(); // no research key set

        $this->actingAs($user)->post(route('send-simulation.store'), [
            'research_key' => 'apapun',
            'respondents' => [
                ['class_group' => 'XII IPA 1', 'email' => 'budi@example.com'],
            ],
        ])->assertSessionHasErrors('research_key');
    }

    public function test_research_key_can_be_set(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('research-key.update'), [
            'research_key' => 'kunci-baru',
            'research_key_confirmation' => 'kunci-baru',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('kunci-baru', $user->fresh()->research_key_hash));
    }

    public function test_guests_cannot_send_simulations(): void
    {
        $this->post(route('send-simulation.store'), [])->assertRedirect(route('login'));
    }
}
