---
paths:
  - app/Http/Middleware/ProcessDueReminders.php
---

# Middleware

## Reminders run cron-less via web heartbeat
Reminders are NOT driven by a server cron. The ProcessDueReminders middleware (web group, runs in terminate() after the response) calls ReminderScheduler at most once per SIMULATION_REMINDER_INTERVAL_MINUTES via an atomic Cache::add guard. Jobs (email/WA) run inline because QUEUE_CONNECTION=sync. Do not re-add Schedule::command('simulation:process-reminders') to routes/console.php unless deploying with a real cron+worker (then also set SIMULATION_AUTO_REMINDERS=false to avoid double runs). Tests keep SIMULATION_AUTO_REMINDERS=false (phpunit.xml) so the heartbeat doesn't fire unexpectedly.
