<?php
namespace App\Console\Commands;
use App\Http\Controllers\Socket\SocketRfidController;
use App\Http\Controllers\Wap\MultiGateController;
use App\Jobs\UpdateScanPalletJob;
use App\Libraries\Clients;
use App\Libraries\Helpers\SocketCLI\ConnectRFID;
use App\Libraries\MyHelper;
use App\Models\Position;
use App\Models\RfidTags;
use App\Models\RobotProcessing;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ProcessScanPalletCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ChildProcess:scan-pallet {whs_id} {host_ip} {port}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call: "php artisan ChildProcess:scan-pallet [hoursBefore(default:****)]"';

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
        $infoGate = [
            'whs_id' => $this->argument('whs_id'),
            'host_ip' => $this->argument('host_ip'),
            'port' => $this->argument('port')
        ];
        self::scanRFID($infoGate);
    }

    /*
     * Receive info of gate and start gate
     */
    public static function scanRFID($infoGate)
    {
        $hostip = $infoGate['host_ip'];
        $port = $infoGate['port'];
        $sock = ConnectRFID::createSocket($hostip, $port);
        $check = ConnectRFID::requestConnection($sock);
        if ($check) {
            $resStartOperation = ConnectRFID::sendCommand($sock, ConnectRFID::CMD_START_OPERATION);
            if ($resStartOperation == '0') {
                while (true) {
                    $resData = ConnectRFID::receiveData($sock);
                    if ($resData) {
                        $dataDecode = ConnectRFID::decodePackage($resData, true);
                        $dataParse = SocketRfidController::parseData($dataDecode['data'], ConnectRFID::CMD_REQUEST_TAG_ID);
                        // Call API to update pallet to WMS
                        UpdateScanPalletJob::updateCartonToWMS($sock, $infoGate, $dataParse);
                    }
                }
            }
            ConnectRFID::sendCommand($sock, ConnectRFID::CMD_DISCONNECT);
        }
        ConnectRFID::disconnect($sock);
    }

    /*
     * This function just use to test multi thread
     */
    public static function testMultiThread($infoGate)
    {
        $dataParse = ["0000000000000","111111111111"];
        $dir = str_replace("\\","/",base_path()."/storage/logs/test-pallet-".$infoGate['host_ip'].".txt");
        $file = fopen($dir,"a");
        $i = 0;
        while(true){
            $str = "whs_id:".$infoGate['whs_id'].PHP_EOL;
            $str .= "host_ip:".$infoGate['host_ip'].PHP_EOL;
            $str .= "port:".$infoGate['port'].PHP_EOL;
            $str .= "path:".$dir.PHP_EOL;
            $str .= "---------------------------------------------";
            fwrite($file,"Gate ip  => ".$infoGate['host_ip'] ." time=> ".$i." info gate => ".PHP_EOL.$str.PHP_EOL);
            //dispatch((new UpdateScanPalletJob($infoGate, $dataParse))->onQueue('update_pallet_inbound'));
            sleep(3);
            $i++;
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['whs_id', InputArgument::REQUIRED, 'whs_id'],
            ['host_ip', InputArgument::REQUIRED, 'host_ip'],
            ['port', InputArgument::REQUIRED, 'port']
        ];
    }
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [

        ];
    }
}
