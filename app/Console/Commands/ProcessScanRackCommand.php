<?php
namespace App\Console\Commands;
use App\Http\Controllers\Socket\SocketRfidController;
use App\Http\Controllers\Wap\MultiGateController;
use App\Jobs\UpdateScanPalletJob;
use App\Libraries\Clients;
use App\Libraries\Helpers\SocketCLI\ConnectRFID;
use App\Libraries\MyHelper;
use App\Libraries\UrlWmsOutBound;
use App\Models\Device;
use App\Models\DeviceFactory;
use App\Models\PalletProcessing;
use App\Models\Position;
use App\Models\RfidTags;
use App\Models\RobotProcessing;
use App\Models\UserRole;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Http\Controllers\Wap\ScanerController;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;
class ProcessScanRackCommand extends Command {

    const ACTION_SEND_DATA_SOCKET = 'senddata';
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ChildProcess:scan-rack {rfid_reader_id} {processor_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call: "php artisan ChildProcess:scan-rack [hoursBefore(default:****)]"';

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
            'rfid_reader_id' => $this->argument('rfid_reader_id'),
            'processor_id' => $this->argument('processor_id')
        ];
        $checkUser = self::checkIsUserOutbound($infoGate['processor_id']);
        if ($checkUser) {
            self::scanRFIDOutbound($infoGate);
        } else {
            self::scanRFID($infoGate);
        }
    }

    /*
     * Receive info of gate and start forklift for outbound
     */
    public static function scanRFIDOutbound($infoGate)
    {
        $infoConnect = Device::getDetailDeviceById($infoGate['rfid_reader_id']);
        $hostip = $port = '';
        if (count($infoConnect) > 0) {
            $hostip = $infoConnect['host_ip'];
            $port = $infoConnect['port'];
        }
        $sock = ConnectRFID::createSocket($hostip, $port);
        $check = ConnectRFID::requestConnection($sock);
        if ($check) {
            $resStartOperation = ConnectRFID::sendCommand($sock, ConnectRFID::CMD_START_OPERATION);
            if ($resStartOperation == '0') {
                $dataCheck = [];
                while (true) {
                    $resData = ConnectRFID::receiveData($sock);
                    if ($resData) {
                        $dataDecode = ConnectRFID::decodePackage($resData, true);
                        $dataParse = SocketRfidController::parseData($dataDecode['data'], ConnectRFID::CMD_REQUEST_TAG_ID);
                        if ($dataCheck !== $dataParse) {
                            self::handleDataScanRackOutbound($infoGate, $dataParse, $dataCheck);
                            $dataCheck = $dataParse;
                        }
                    }
                }
            }
            ConnectRFID::sendCommand($sock, ConnectRFID::CMD_DISCONNECT);
        }
        ConnectRFID::disconnect($sock);
    }

    public static function handleDataScanRackOutbound($infoGate, $dataParse, $dataBefore)
    {
        Clients::writeLog([
            'action' => 'data received from rack',
            'data' => [
                'dataBefore' => $dataBefore,
                'dataRep' => $dataParse
            ]
        ]);
        $dataFilter = self::filterDataPalletRack($dataParse);
        if ($dataFilter['rack'] != '') {
            $dataSend = [
                'action' => 'ScanRackOutbound',
                'result' => $dataFilter
            ];
            Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
            $dataFilterBefore = self::filterDataPalletRack($dataBefore);
            if ($dataFilterBefore['pallet'] != '' && $dataFilterBefore['rack'] == '' && $dataFilter['pallet'] != '') {
                $whsID = Warehouse::getWarehouseId();
                $token = Clients::ConnectGetTokenWmsScan();
                $request = new Request();
                $request->headers->set('Authorization', 'Bearer ' . $token);
                $url = UrlWmsOutBound::getUrlPutBackPalletOnRack($whsID);
                $dataPutWMS = [
                    'loc_rfid' => $dataFilter['rack'],
                    'pallet_rfid' => $dataFilter['pallet']
                ];
                Clients::writeLog([
                    'action' => 'URL and data before call API put back pallet on rack',
                    'data' => [
                        'url' => $url,
                        'dataPutWMS' => $dataPutWMS
                    ]
                ]);
                $res = Clients::ConnectWmsData('PUT', $url, $dataPutWMS, $request);
                Clients::writeLog([
                    'action' => 'response when call API put back pallet on rack',
                    'data' => [
                        'response' => $res
                    ]
                ]);
            }
        } else {
            if ($dataFilter['pallet'] != '') {
                //forklift pick pallet on rack => call WMS api to move pallet from rack
                $whsID = Warehouse::getWarehouseId();
                $token = Clients::ConnectGetTokenWmsScan();
                $request = new Request();
                $request->headers->set('Authorization', 'Bearer ' . $token);
                $url = UrlWmsOutBound::getUrlMovePalletOnRack($whsID, $dataFilter['pallet']);
                Clients::writeLog([
                    'action' => 'URL call API pallet move from RACK',
                    'data' => [
                        'url' => $url
                    ]
                ]);
                $res = Clients::ConnectWmsData('PUT', $url, [], $request);
                Clients::writeLog([
                    'action' => 'response when call API pallet move from RACK',
                    'data' => [
                        'response' => $res
                    ]
                ]);
            }
            $dataSend = [
                'action' => 'ScanRackOutbound',
                'result' => []
            ];
            Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
        }
    }

    public static function filterDataPalletRack($dataParse)
    {
        $rackCode = '';
        $palletCode = '';
        $patternPallet =  RfidTags::getPatternCode();
        $patternRack =  RfidTags::getPatternCode(RfidTags::RACK);
        foreach ($dataParse as $item) {
            if (substr($item, 0, 8) == $patternRack) {
                $rackCode = $item;
            } elseif (substr($item, 0, 8) == $patternPallet) {
                $palletCode = $item;
            }
        }
        return [
            'rack' => $rackCode,
            'pallet' => $palletCode
        ];
    }

    /*
     * check forklift user at outbound or inbound
     */
    public static function checkIsUserOutbound($idUser)
    {
        $query = UserRole::where('user_id', $idUser)
            ->leftJoin('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
            ->leftJoin('permission', 'permission.permission_id', '=', 'role_permission.permission_id')
            ->where('permission.code', 'OBP_PERM02')
            ->get();

        if (count($query) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Receive info of gate and start gate
     */
    public static function scanRFID($infoGate)
    {
        $infoConnect = Device::getDetailDeviceById($infoGate['rfid_reader_id']);
        $hostip = $port = '';
        if (count($infoConnect) > 0) {
            $hostip = $infoConnect['host_ip'];
            $port = $infoConnect['port'];
        }
        $sock = ConnectRFID::createSocket($hostip, $port);
        $check = ConnectRFID::requestConnection($sock);
        if ($check) {
            $resStartOperation = ConnectRFID::sendCommand($sock, ConnectRFID::CMD_START_OPERATION);
            if ($resStartOperation == '0') {
                //$dir = str_replace("\\","/",base_path()."/storage/logs/test-twertw.txt");
                //$file = fopen($dir,"a");
                $statusData = -1;
                while (true) {
                    $resData = ConnectRFID::receiveData($sock);
                    if ($resData) {
                        $dataDecode = ConnectRFID::decodePackage($resData, true);
                        $dataParse = SocketRfidController::parseData($dataDecode['data'], ConnectRFID::CMD_REQUEST_TAG_ID);
                        // Call function to update process for rack
                        
                        $pallet=0;
                        $rack=0;
                        if(isset($dataParse[0]))
                        { 
                             foreach ($dataParse as $value) {
                                if(substr($value,0,8) =='FFFFFFFF')
                                {
                                    $pallet=1;
                                }
                                elseif(substr($value,0,8) =='DDDDDDDD')
                                {
                                    $rack=1;
                                };
                            };
                        }
                       
                        if($statusData!=($pallet+$rack))
                        {
                            $statusData=($pallet+$rack);
                            ScanerController::scanRack($dataParse,$infoGate['processor_id']);
                        }
                        
                        //$str = json_encode($dataParse);
                        //$str2 = json_encode($infoGate);
                        //fwrite($file,"dataParse  => ".PHP_EOL.$str.PHP_EOL."infoGate  => ".$str2.PHP_EOL);
                    }
                }
                //fclose($file);
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
        $infoConnect = Device::getDetailDeviceById($infoGate['rfid_reader_id']);
        $hostip = $port = '';
        if (count($infoConnect) > 0) {
            $hostip = $infoConnect['host_ip'];
            $port = $infoConnect['port'];
        }
        $dir = str_replace("\\","/",base_path()."/storage/logs/test-pallet-outbound-".$infoGate['rfid_reader_id'].".txt");
        $file = fopen($dir,"a");
        $i = 0;
        while(true){
            $str = "rfid_reader_id:".$infoGate['rfid_reader_id'].PHP_EOL;
            $str .= "processor_id:".$infoGate['processor_id'].PHP_EOL;
            $str .= "host_ip:".$hostip.PHP_EOL;
            $str .= "port:".$port.PHP_EOL;
            $str .= "---------------------------------------------";
            fwrite($file,"Gate ip  => ".$infoGate['rfid_reader_id'] ." time=> ".$i." info gate => ".PHP_EOL.$str.PHP_EOL);
            //dispatch((new UpdateScanPalletJob($infoGate, $dataParse))->onQueue('update_pallet_outbound'));
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
            ['rfid_reader_id', InputArgument::REQUIRED, 'rfid_reader_id'],
            ['processor_id', InputArgument::REQUIRED, 'processor_id']
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
