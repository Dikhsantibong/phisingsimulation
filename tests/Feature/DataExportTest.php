<?php

namespace Tests\Feature;

use App\Models\QuestionnaireResult;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_download_streams_csv_with_headers(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create(['email' => 'siswa@example.com']);
        SimulationEvent::factory()->risky()->for($respondent)->create();
        QuestionnaireResult::factory()->for($respondent)->create();

        $response = $this->actingAs($user)->get(route('export.download'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('session_token', $content);
        $this->assertStringContainsString('behavior_status', $content);
        $this->assertStringContainsString('siswa@example.com', $content);
    }

    public function test_anonymised_export_excludes_pii(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create(['email' => 'rahasia@example.com']);
        SimulationEvent::factory()->for($respondent)->create();

        $response = $this->actingAs($user)->get(route('export.download', ['anonymise' => 1]));
        $content = $response->streamedContent();

        $this->assertStringNotContainsString('rahasia@example.com', $content);
        $this->assertStringNotContainsString(',email,', $content);
        $this->assertStringContainsString($respondent->session_token, $content);
    }

    public function test_export_requires_authentication(): void
    {
        $this->get(route('export.download'))->assertRedirect(route('login'));
    }
}
