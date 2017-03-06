<?php
namespace App\Console\Commands;
use App\Http\Controllers\Socket\SocketRfidController;
use App\Http\Controllers\Wap\MultiGateController;
use App\Jobs\UpdateScanCartonJob;
use App\Jobs\UpdateScanJob;
use App\Libraries\Clients;
use App\Libraries\Helpers\SocketCLI\ConnectRFID;
use App\Libraries\MyHelper;
use App\Models\AsnDetailProcessing;
use App\Models\CartonProcessing;
use App\Models\Device;
use App\Models\DeviceFactory;
use App\Models\LogEvent;
use App\Models\RecProcessing;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class ProcessScanCartonCommand extends Command {

    const ACTION_SEND_DATA_SOCKET = 'senddata';
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'ChildProcess:scan-carton {asn_hdr_id} {asn_dtl_id} {item_id} {ctnr_id} {cus_id} {checker} {rfid_reader} {type_reader}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call: "php artisan ChildProcess:scan-carton [hoursBefore(default:****)]"';

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
        $warehouseId = Warehouse::getWarehouseId();
        $infoGate = [
            'asn_hdr_id' => $this->argument('asn_hdr_id'),
            'asn_dtl_id' => $this->argument('asn_dtl_id'),
            'item_id' => $this->argument('item_id'),
            'ctnr_id' => $this->argument('ctnr_id'),
            'whs_id' => $warehouseId,
            'cus_id' => $this->argument('cus_id'),
            'rfid_reader' => $this->argument('rfid_reader'),
            'type_reader' => $this->argument('type_reader'),
            'checker' => $this->argument('checker')
        ];
        //self::testMultiThread($infoGate);
        self::scanRFID($infoGate);
    }

    /*
     * Receive info of gate and start gate
     */
    public static function scanRFID($infoGate)
    {
        $infoConnect = Device::getDetailDeviceById($infoGate['rfid_reader']);
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
                if ($infoGate['type_reader'] == 'FIRST') {
                    self::handleReaderFirst($sock, $infoGate);
                } else {
                    self::handleReaderSecond($sock, $infoGate);
                }
            }
            ConnectRFID::sendCommand($sock, ConnectRFID::CMD_DISCONNECT);
        }
        ConnectRFID::disconnect($sock);
    }

    public static function handleReaderFirst($sock, $infoGate)
    {
        $sendError = false;
        while (true) {
            $resData = ConnectRFID::receiveData($sock);
            if ($resData) {
                $checkExpectedCartons = AsnDetailProcessing::where('asn_dtl_id', $infoGate['asn_dtl_id'])
                    ->where('item_id', $infoGate['item_id'])
                    ->first();
                $totalCarton = CartonProcessing::getTotalCarton($checkExpectedCartons->asn_detail_processing_id);
                $dataDecode = ConnectRFID::decodePackage($resData, true);
                $dataParse = SocketRfidController::parseData($dataDecode['data'], ConnectRFID::CMD_REQUEST_TAG_ID);
                LogEvent::saveLog(
                    LogEvent::SCAN_CARTON_ON_RF_READER_1,
                    null,
                    null,
                    ['ctn_rfid' => json_encode($dataParse)]
                );
                Clients::writeLog([
                    'action' => 'transfer scan before: conveyor 1',
                    'data' => [
                        'reader' => $infoGate['rfid_reader'],
                        'rfid_list' => $dataParse,
                        'count' => count($dataParse)
                    ]
                ]);
                if (count($dataParse) == 0) {
                    $dataSend = [
                        'action' => 'ScanCarton',
                        'item_id' => $infoGate['item_id'],
                        'checker' => $infoGate['checker'],
                        'rfid_list' => [],
                        'error_code' => 'NO_RFID',
                        'message' => 'The scanned carton has no RFID'
                    ];
                    Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
                } else {
                    $params = [
                        "asn_dtl_id" => $infoGate['asn_dtl_id'],
                        "item_id" => $infoGate['item_id'],
                        "ctns_rfid" => $dataParse
                    ];
                    $check = CartonProcessing::where('rfid', $dataParse[0])->first();
                    Clients::writeLog([
                        'action' => 'check rfid exist in db: conveyor 1',
                        'data' => [
                            'reader' => $infoGate['rfid_reader'],
                            'rfid_list' => $dataParse[0],
                            'count check' => count($check)
                        ]
                    ]);
                    if (count($check) == 0) {
                        //add carton to wap_db
                        CartonProcessing::addCartonProcessing($params, CartonProcessing::STATUS_RECEIVING);
                        //Call socket IO to send RFID code to front-end
                        $dataParseFinal = [];
                        foreach ($dataParse as $val) {
                            $dataParseFinal[] = [
                                'rfid' => $val,
                                'status' => CartonProcessing::STATUS_RECEIVING
                            ];
                        }
                        $dataSend = [
                            'action' => 'ScanCarton',
                            'item_id' => $infoGate['item_id'],
                            'checker' => $infoGate['checker'],
                            'rfid_list' => $dataParseFinal
                        ];
                        Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
                        Clients::writeLog([
                            'action' => 'transfer_data_via_socket_io: conveyor 1',
                            'data' => [
                                'reader' => $infoGate['rfid_reader'],
                                'action' => 'ScanCarton',
                                'item_id' => $infoGate['item_id'],
                                'checker' => $infoGate['checker'],
                                'rfid_list' => $dataParseFinal
                            ]
                        ]);
                        // Call API to update carton to WMS
                        dispatch((new UpdateScanCartonJob($infoGate, $dataParse))->onQueue('update_carton'));
                    } else {
                        Clients::writeLog([
                            'action' => 'rfid exist in db => send message error for FE: conveyor 1',
                            'data' => [
                                'reader' => $infoGate['rfid_reader'],
                                'rfid_list' => $dataParse[0],
                                'count check' => count($check)
                            ]
                        ]);
                        $dataSend = [
                            'action' => 'ScanCarton',
                            'item_id' => $infoGate['item_id'],
                            'checker' => $infoGate['checker'],
                            'rfid_list' => [],
                            'error_code' => 'EXISTED_CARTON',
                            'message' => 'The carton has been already scanned.'
                        ];
                        Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
                    }
                }
                if ($totalCarton >= $checkExpectedCartons->expected_cartons) {
                    //announce a message to frontend
                    if (!$sendError) {
                        $dataSend = [
                            'action' => 'ErrorLimitCarton',
                            'checker' => $infoGate['checker'],
                            'message' => 'The number of cartons is more than expecting'
                        ];
                        Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
                        $sendError = true;
                    }
                    //send command alert RED light to reader
                    Clients::setAlertReader($sock);
                }
            }
        }
    }

    public static function handleReaderSecond($sock, $infoGate)
    {
        //call api from WMS to double check and ensure that carton had updated to WMS_DB
        while (true) {
            $resData = ConnectRFID::receiveData($sock);
            if ($resData) {
                $dataDecode = ConnectRFID::decodePackage($resData, true);
                $dataParse = SocketRfidController::parseData($dataDecode['data'], ConnectRFID::CMD_REQUEST_TAG_ID);
                LogEvent::saveLog(
                    LogEvent::SCAN_CARTON_ON_RF_READER_2,
                    null,
                    null,
                    ['ctn_rfid' => json_encode($dataParse)]
                );
                $checkExpectedCartons = AsnDetailProcessing::where('asn_dtl_id', $infoGate['asn_dtl_id'])
                    ->where('item_id', $infoGate['item_id'])
                    ->first();
                $totalCarton = CartonProcessing::getTotalCarton($checkExpectedCartons->asn_detail_processing_id);
                Clients::writeLog([
                    'action' => 'total carton <=> number expected: conveyor 2',
                    'data' => [
                        'total carton' => $totalCarton,
                        'number expected' => $checkExpectedCartons->expected_cartons
                    ]
                ]);
                self::filterDataResponse($infoGate, $dataParse);
            }
        }
    }

    public static function filterDataResponse($infoGate, $dataParse)
    {
        $dataParseFinal = [];
        foreach ($dataParse as $code) {
            $check = CartonProcessing::where('rfid', $code)->first();
            if (count($check) == 0) {
                $dataParseFinal[] = [
                    'rfid' => $code,
                    'status' => CartonProcessing::STATUS_DAMAGED
                ];
                $params = [
                    "asn_dtl_id" => $infoGate['asn_dtl_id'],
                    "item_id" => $infoGate['item_id'],
                    "ctns_rfid" => [$code]
                ];
                //add carton to wap_db with status damaged
                CartonProcessing::addCartonProcessing($params, CartonProcessing::STATUS_DAMAGED);
            } else {
                if($check->status == CartonProcessing::STATUS_RECEIVED){
                    $dataParseFinal[] = [
                        'rfid' => $code,
                        'status' => CartonProcessing::STATUS_RECEIVED
                    ];
                }else{
                    $dataParseFinal[] = [
                        'rfid' => $code,
                        'status' => CartonProcessing::STATUS_DAMAGED
                    ];
                }

            }
        }
        $dataSend = [
            'action' => 'ScanCarton',
            'item_id' => $infoGate['item_id'],
            'checker' => $infoGate['checker'],
            'rfid_list' => $dataParseFinal
        ];
        Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
        Clients::writeLog([
            'action' => 'transfer_data_via_socket_io: conveyor 2',
            'data' => [
                'reader' => $infoGate['rfid_reader'],
                'action' => 'ScanCarton',
                'item_id' => $infoGate['item_id'],
                'checker' => $infoGate['checker'],
                'rfid_list' => $dataParseFinal
            ]
        ]);
    }

    /*
     * This function just use to test multi thread
     */
    public static function testMultiThread($infoGate)
    {
        $infoConnect = Device::getDetailDeviceById($infoGate['rfid_reader']);
        $hostip = $port = '';
        if (count($infoConnect) > 0) {
            $hostip = $infoConnect['host_ip'];
            $port = $infoConnect['port'];
        }
        $dir = str_replace("\\","/",base_path()."/storage/logs/test-carton-".$infoGate['rfid_reader'].".txt");
        $file = fopen($dir,"a");
        $i = 0;
        while(true){
            $str = "asn_hdr_id:".$infoGate['asn_hdr_id'].PHP_EOL;
            $str .= "asn_dtl_id:".$infoGate['asn_dtl_id'].PHP_EOL;
            $str .= "item_id:".$infoGate['item_id'].PHP_EOL;
            $str .= "ctnr_id:".$infoGate['ctnr_id'].PHP_EOL;
            $str .= "whs_id:".$infoGate['whs_id'].PHP_EOL;
            $str .= "cus_id:".$infoGate['cus_id'].PHP_EOL;
            $str .= "rfid_reader:".$infoGate['rfid_reader'].PHP_EOL;
            $str .= "type_reader:".$infoGate['type_reader'].PHP_EOL;
            $str .= "ip:".$hostip.PHP_EOL;
            $str .= "port:".$port.PHP_EOL;
            $str .= "checker:".$infoGate['checker'].PHP_EOL;
            $str .= "---------------------------------------------";
            fwrite($file,"Gate ip  => ".$infoGate['rfid_reader'] ." time=> ".$i." info gate => ".PHP_EOL.$str.PHP_EOL);
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
            ['asn_hdr_id', InputArgument::REQUIRED, 'asn_hdr_id'],
            ['asn_dtl_id', InputArgument::REQUIRED, 'asn_dtl_id'],
            ['item_id', InputArgument::REQUIRED, 'item_id'],
            ['ctnr_id', InputArgument::REQUIRED, 'ctnr_id'],
            ['cus_id', InputArgument::REQUIRED, 'cus_id'],
            ['rfid_reader', InputArgument::REQUIRED, 'rfid_reader'],
            ['type_reader', InputArgument::REQUIRED, 'type_reader'],
            ['checker', InputArgument::REQUIRED, 'checker']
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
