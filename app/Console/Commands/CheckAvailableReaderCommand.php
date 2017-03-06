<?php
namespace App\Console\Commands;
use App\Http\Controllers\Wap\MultiGateController;
use App\Jobs\UpdateScanPalletJob;
use App\Libraries\Clients;
use App\Models\AsnDetailProcessing;
use App\Models\CartonProcessing;
use App\Models\Device;
use App\Models\RecProcessing;
use App\Models\RfidTags;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class CheckAvailableReaderCommand extends Command {

    const CONVEYOR = 2;
    const GATEWAY = 4;
    const RACK = 3;
    const TIME_REPEAT_CHECK_READER = 300;
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'CheckAvailableReader:check-available';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call: "php artisan CheckAvailableReader:check-available [hoursBefore(default:****)]"';

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
        while (true) {
            $rec = RecProcessing::where('status', RecProcessing::STATUS_RECEIVING)->get();
            $listReceivingGate = [];
            if (count($rec) > 0) {
                foreach ($rec as $item) {
                    $listReceivingGate[] = $item->rfid_reader_1;
                    $listReceivingGate[] = $item->rfid_reader_2;
                }
            }
            $query = Device::whereIn('device_type_id', [
                self::CONVEYOR,
                /*self::RACK,
                self::GATEWAY*/
            ])
                ->whereNotIn('device_id', $listReceivingGate)
                ->whereNotNull('host_ip');
            $query->update([
                'last_status' => Device::ACTIVE
            ]);
            $listReader = $query->get();
            foreach ($listReader as $reader) {
                self::checkConnect($reader);
            }
            sleep(self::TIME_REPEAT_CHECK_READER);
        }
    }

    public static function checkConnect($reader)
    {
        try {
            $socketCLI = socket_create(AF_INET, SOCK_STREAM, 0);
            socket_connect($socketCLI, $reader->host_ip, $reader->port);
            echo "Reader is available: " . $reader->host_ip . "\n";
            socket_close($socketCLI);
        } catch (\Exception $e) {
            //update status of reader to not available
            $reader->last_status = Device::INACTIVE;
            $reader->save();
            echo "Reader is not available: " . $reader->host_ip . "\n";
        }
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

    }
}
