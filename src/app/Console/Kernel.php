<?php

namespace App\Console;

use App\Jobs\ExtractExifJob;
use App\Jobs\GeneratePostJob;
use App\Jobs\ImportMediaJob;
use App\Services\ExtractExifService;
use App\Services\ImportMediaService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new ExtractExifJob(new ExtractExifService()), 'media')
            ->hourly();

        $schedule->job(new ImportMediaJob(new ImportMediaService()), 'ingestor')
            ->twiceDaily(11, 17);

        $schedule->job(new GeneratePostJob(), 'default')
            ->cron("15 11,15,19,23 * * *");
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
