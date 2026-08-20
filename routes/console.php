<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// NOTE: Reminders no longer depend on a server cron. They are triggered by the
// ProcessDueReminders middleware on ordinary web traffic (see bootstrap/app.php).
// The `simulation:process-reminders` command still exists for manual runs.
