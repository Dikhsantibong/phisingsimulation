<?php

namespace Tests\Feature;

use App\Enums\BehaviorStatus;
use App\Models\QuestionnaireResult;
use App\Models\Respondent;
use App\Models\SimulationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataExportScoredTest extends TestCase
{
    use RefreshDatabase;

    public function test_scored_export_computes_the_worked_example(): void
    {
        $user = User::factory()->create();
        $respondent = Respondent::factory()->create();

        SimulationEvent::factory()->for($respondent)->create([
            'behavior_status' => BehaviorStatus::Netral,
        ]);

        QuestionnaireResult::factory()->for($respondent)->create([
            'submitted_at' => '2026-11-11 02:55:49',
            'knowledge_answers' => [
                'K1' => 'Upaya penipuan untuk mencuri data pribadi dengan menyamar sebagai pihak terpercaya', // correct
                'K2' => 'URL yang menyerupai situs asli tetapi berbeda sedikit', // correct
                'K3' => 'wrong',
                'K4' => 'Phishing melalui pesan SMS atau WhatsApp', // correct
                'K5' => 'Memeriksa alamat URL tujuan', // correct
                'K6' => 'Menambahkan lapisan keamanan kedua selain password', // correct
                'K7' => 'wrong',
                'K8' => 'Memeriksa kebenaran informasi melalui kanal resmi sekolah', // correct
            ],
            'attitude_answers' => ['A1' => 4, 'A2' => 4, 'A3' => 4, 'A4' => 3, 'A5' => 3, 'A6' => 3, 'A7' => 4, 'A8' => 3],
            'behavior_answers' => ['B1' => 3, 'B2' => 3, 'B3' => 3, 'B4' => 3, 'B5' => 3, 'B6' => 3, 'B7' => 3, 'B8' => 3],
        ]);

        $response = $this->actingAs($user)->get(route('export.download-scored'));
        $content = $response->streamedContent();

        $this->assertStringContainsString('total_knowledge', $content);
        $this->assertStringContainsString('tingkat_kesadaran', $content);
        $this->assertStringContainsString('first_access_at', $content);
        $this->assertStringContainsString('seconds_to_click', $content);
        $this->assertStringContainsString('mengetik_form', $content);
        $this->assertStringContainsString('device_type', $content);
        $this->assertStringContainsString('no_responden', $content);
        $this->assertStringContainsString(sprintf('SID-%03d', $respondent->id), $content);
        $this->assertStringContainsString('submitted_at', $content);
        $this->assertStringContainsString('2026-11-11T02:55:49', $content);

        $header = explode("\n", $content)[0];
        $this->assertStringContainsString('kelas', $header);
        $this->assertStringNotContainsString('class_group', $header);

        $header = explode("\n", $content)[0];
        $this->assertStringNotContainsString('sent_at', $header);

        // 75,70,60,60 -> 66.25 -> Sedang
        $this->assertStringContainsString('75', $content);
        $this->assertStringContainsString('66.25', $content);
        $this->assertStringContainsString('Sedang', $content);
    }

    public function test_scored_export_requires_authentication(): void
    {
        $this->get(route('export.download-scored'))->assertRedirect(route('login'));
    }
}
