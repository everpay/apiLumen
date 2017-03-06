<?php
namespace App\Console\Commands;
use App\Http\Controllers\Wap\MultiGateController;
use App\Jobs\UpdateScanCartonJob;
use App\Jobs\UpdateScanPalletJob;
use App\Jobs\UpdateScanPalletOutboundJob;
use App\Models\AsnDetailProcessing;
use App\Models\CartonProcessing;
use App\Models\PalletProcessing;
use App\Models\RfidTags;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class ControlMultiGateCommand extends Command {

    const CONVEYOR = 'conveyor';
    const PALLET = 'pallet';
    const RACK = 'rack';
    const PALLET_OUTBOUND = 'pallet-outbound';
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'MultiGate:multi-gate {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call: "php artisan MultiGate:multi-gate [hoursBefore(default:****)]"';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        //type gate: conveyor or pallet
        $typeGate = $this->option('type');
        MultiGateController::controlGate($typeGate);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {

    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            'type', InputOption::VALUE_REQUIRED, 'Conveyor'
        ];
    }
}
