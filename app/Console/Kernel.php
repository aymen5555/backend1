<?php

namespace App\Console;

use App\Console\Commands\CleanupInvalidAbonnementsCommand;
use App\Console\Commands\SafeMigrateFreshCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('reservations:update-status')->everyFiveMinutes();
        $schedule->command('abonnements:update-status')->daily();
        $schedule->command('activites:update-status')->everyFiveMinutes();
    }

    protected function bootstrapping(): void
    {
        Artisan::starting(function ($artisan) {
            $migrator = $artisan->getLaravel()->make('migrator');
            $artisan->add(new SafeMigrateFreshCommand($migrator));
            $artisan->add(new CleanupInvalidAbonnementsCommand());
        });
    }
}
