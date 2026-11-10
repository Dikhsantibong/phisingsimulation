<?php

use App\Http\Controllers\Researcher\CampaignController;
use App\Http\Controllers\Researcher\DashboardController;
use App\Http\Controllers\Researcher\DataExportController;
use App\Http\Controllers\Researcher\ReminderController;
use App\Http\Controllers\Researcher\ResearchKeyController;
use App\Http\Controllers\Researcher\RespondentController;
use App\Http\Controllers\Researcher\SendSimulationController;
use App\Http\Controllers\Researcher\SendWAReminderController;
use App\Http\Controllers\Researcher\TallyImportController;
use App\Http\Controllers\Simulation\CampaignAccessController;
use App\Http\Controllers\Simulation\PortalBehaviorController;
use App\Http\Controllers\Simulation\SimulationAccessController;
use App\Http\Controllers\Webhooks\TallyWebhookController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| Public campaign link (respondent self-registers on first visit)
|--------------------------------------------------------------------------
| Higher throttle than /s/... below: this is the entry point that absorbs
| the initial burst when a shared link goes out to many respondents at once.
*/
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/c/{campaign:slug}', [CampaignAccessController::class, 'show'])
        ->name('campaign.access');
});

/*
|--------------------------------------------------------------------------
| Public simulation flow (respondent-facing)
|--------------------------------------------------------------------------
| Rate limited to blunt automated crawlers pre-filling fake data.
*/
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/s/{respondent:session_token}', [SimulationAccessController::class, 'show'])
        ->name('simulation.access');

    Route::post('/s/{respondent:session_token}/behavior', [PortalBehaviorController::class, 'store'])
        ->name('simulation.behavior');

    Route::get('/s/{respondent:session_token}/questionnaire', [PortalBehaviorController::class, 'questionnaire'])
        ->name('simulation.questionnaire');

    Route::get('/s/{respondent:session_token}/reveal', [PortalBehaviorController::class, 'reveal'])
        ->name('simulation.reveal');

    Route::get('/s/{respondent:session_token}/completed', [PortalBehaviorController::class, 'completed'])
        ->name('simulation.completed');
});

/*
|--------------------------------------------------------------------------
| Tally questionnaire webhook (server-to-server, CSRF-excluded)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/tally', TallyWebhookController::class)->name('webhooks.tally');

/*
|--------------------------------------------------------------------------
| Researcher dashboard (authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('respondents', [RespondentController::class, 'index'])->name('respondents.index');
    Route::delete('respondents/{respondent}', [RespondentController::class, 'destroy'])->name('respondents.destroy');

    Route::get('send-simulation', [SendSimulationController::class, 'create'])->name('send-simulation.create');
    Route::post('send-simulation', [SendSimulationController::class, 'store'])->name('send-simulation.store');
    Route::put('research-key', [ResearchKeyController::class, 'update'])->name('research-key.update');

    Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::put('campaigns/{campaign}/toggle-active', [CampaignController::class, 'toggleActive'])->name('campaigns.toggle-active');
    Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

    Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::put('reminders/{reminder}/followed-up', [ReminderController::class, 'markFollowedUp'])->name('reminders.followed-up');
    Route::post('reminders/{reminder}/wa', SendWAReminderController::class)->name('reminders.wa');

    Route::get('export', [DataExportController::class, 'index'])->name('export.index');
    Route::get('export/download', [DataExportController::class, 'download'])->name('export.download');
    Route::get('export/download-scored', [DataExportController::class, 'downloadScored'])->name('export.download-scored');
    Route::post('export/import-tally', [TallyImportController::class, 'store'])->name('export.import-tally');
});

require __DIR__.'/settings.php';
