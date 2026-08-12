<?php

namespace App\Console\Commands;

use App\Services\ReminderScheduler;
use Illuminate\Console\Command;

class ProcessSimulationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simulation:process-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Queue follow-up reminders for ignored simulations and unfinished questionnaires';

    /**
     * Execute the console command.
     */
    public function handle(ReminderScheduler $scheduler): int
    {
        $count = $scheduler->run();

        $this->info("Queued {$count} reminder(s).");

        return self::SUCCESS;
    }
}
