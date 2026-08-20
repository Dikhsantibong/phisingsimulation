<?php

namespace App\Http\Middleware;

use App\Services\ReminderScheduler;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cron-less reminder "heartbeat".
 *
 * Replaces the scheduled `simulation:process-reminders` command: instead of a
 * server cron, the reminder scan is driven by ordinary web traffic. Work runs
 * in {@see terminate()} — after the response is already sent to the browser —
 * so it never adds latency to the user's request.
 *
 * A cache guard (Cache::add is atomic) ensures the scan runs at most once per
 * configured interval, no matter how many requests arrive concurrently.
 */
class ProcessDueReminders
{
    private const CACHE_KEY = 'simulation:reminders:last_run';

    public function __construct(private readonly ReminderScheduler $scheduler) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Run the reminder scan after the response has been dispatched.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! config('services.simulation.auto_reminders_enabled', true)) {
            return;
        }

        $intervalMinutes = max(1, (int) config('services.simulation.reminder_run_interval_minutes', 1));

        // Cache::add only succeeds for the first request in each interval window,
        // so the scan fires once per window regardless of traffic volume.
        if (! Cache::add(self::CACHE_KEY, now()->toDateTimeString(), $intervalMinutes * 60)) {
            return;
        }

        try {
            $this->scheduler->run();
        } catch (\Throwable $e) {
            Log::error('Auto reminder processing failed.', ['exception' => $e]);
        }
    }
}
