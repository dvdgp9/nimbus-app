<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Sync calendars every 15 minutes
        $schedule->command('nimbus:sync-calendars')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/calendar-sync.log'));

        // Send appointment reminders every 15 minutes (48h before appointment)
        $schedule->command('nimbus:send-reminders --hours=48')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/reminders.log'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Plain-text fallback renderer, bypasses Phiki when the server's
        // syntax highlighter is broken. Add ?plain=1 to any URL in debug mode.
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (config('app.debug') && $request->query('plain') === '1') {
                $out = get_class($e) . "\n\n"
                    . $e->getMessage() . "\n\n"
                    . 'at ' . $e->getFile() . ':' . $e->getLine() . "\n\n"
                    . $e->getTraceAsString();
                $prev = $e->getPrevious();
                while ($prev) {
                    $out .= "\n\n--- caused by ---\n"
                        . get_class($prev) . "\n"
                        . $prev->getMessage() . "\n"
                        . 'at ' . $prev->getFile() . ':' . $prev->getLine();
                    $prev = $prev->getPrevious();
                }
                return response('<pre style="font:12px/1.5 ui-monospace,monospace;padding:24px;white-space:pre-wrap;word-break:break-word;">'
                    . htmlspecialchars($out, ENT_QUOTES, 'UTF-8')
                    . '</pre>', 500);
            }
        });
    })->create();
