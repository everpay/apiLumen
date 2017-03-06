<?php

namespace App\Console;

use App\Console\Commands\CheckAvailableReaderCommand;
use App\Console\Commands\ChildProcessCommand;
use App\Console\Commands\ControlMultiGateCommand;
use App\Console\Commands\FailedTableCommand;
use App\Console\Commands\PalletScanCommand;
use App\Console\Commands\ProcessScanCartonCommand;
use App\Console\Commands\ProcessScanPalletCommand;
use App\Console\Commands\ProcessScanRackCommand;
use App\Console\Commands\ProcessScanWavePickCommand;
use App\Console\Commands\UpdateCartonWMSCommand;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\CallRobotCommand;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use Laravelista\LumenVendorPublish\VendorPublishCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        VendorPublishCommand::class,
        ProcessScanCartonCommand::class,
        ProcessScanPalletCommand::class,
        ControlMultiGateCommand::class,
        FailedTableCommand::class,
        CallRobotCommand::class,
        ProcessScanRackCommand::class,
        CheckAvailableReaderCommand::class,
        ProcessScanWavePickCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('command:palletScan');
    }
}
