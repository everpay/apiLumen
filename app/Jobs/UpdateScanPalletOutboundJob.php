<?php

namespace App\Jobs;

use App\Console\Commands\ProcessScanCartonCommand;
use App\Libraries\Clients;
use App\Libraries\Helpers\SocketCLI\ConnectRFID;
use App\Libraries\MyHelper;
use App\Libraries\UrlWmsOutBound;
use App\Models\LogEvent;
use App\Models\PalletProcessing;
use App\Models\PalletProcessingFactory;
use App\Models\Position;
use App\Models\RfidTags;
use App\Models\RobotProcessing;
use App\Models\WpDetailProcessing;
use App\Models\WpProcessing;
use Illuminate\Http\Request;

class UpdateScanPalletOutboundJob extends Job
{
    protected $infoGate;
    protected $dataParse;

    /**
     * Create a new job instance.
     *
     * @param $dataWms
     * @param $dataCarton
     */
    public function __construct($infoGate, $dataParse)
    {
        $this->infoGate = $infoGate;
        $this->dataParse = $dataParse;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //self::updateWavePickWMS();
    }

    public static function updateWavePickWMS($sock, $infoGate, $dataParse)
    {
        $dataSend = RfidTags::filterDataScanGateWay($dataParse, RfidTags::FORKLIFT);
        if ($dataSend['pallet']['pallet-rfid'] != '') {
            $wavePickID = PalletProcessingFactory::getWavePickByPalletRFID($dataSend['pallet']['pallet-rfid']);
            if ($wavePickID == '') {
                Clients::writeLog([
                    'action' => 'get wave pick ID from pallet data outbound',
                    'data' => [
                        'arrData' => $dataSend
                    ]
                ]);
                Clients::setAlertReader($sock);
            } else {
                $dataParam = [
                    'code' => [
                        'ctns' => $dataSend['pallet']['ctn-rfid']
                    ]
                ];
                $token = Clients::ConnectGetTokenWmsScan();
                $request = new Request();
                $request->headers->set('Authorization', 'Bearer ' . $token);
                $url = UrlWmsOutBound::getUrlUpdateWavePick($infoGate['whs_id'], $wavePickID);
                Clients::writeLog([
                    'action' => 'data before send to WMS at gateway outbound',
                    'data' => [
                        'url' => $url,
                        'pallet' => $dataSend['pallet']['pallet-rfid'],
                        'wavepickID' => $wavePickID,
                        'dataSend' => $dataParam
                    ]
                ]);
                LogEvent::saveLog(
                    LogEvent::SCAN_PALLET_OUTBOUND,
                    null,
                    null,
                    ['ctn_rfids' => json_encode($dataSend['pallet']['ctn-rfid'])]
                );
                $dataResponse = Clients::ConnectWmsData('PUT', $url, $dataParam, $request);
                $arrData = json_decode($dataResponse, true);
                Clients::writeLog([
                    'action' => 'data after send to WMS at gateway outbound',
                    'data' => [
                        'arrData' => $dataResponse
                    ]
                ]);
                if (array_key_exists('status', $arrData)) {
                    if ($arrData['status'] == false) {
                        Clients::setAlertReader($sock);
                    } else {
                        if (array_key_exists('data', $arrData)) {
                            if (array_key_exists('picked', $arrData['data']) && $arrData['data']['picked'] == true) {
                                self::updateWavePickAndDetail($wavePickID);
                            }
                        }
                    }
                }
            }
        } else {
            Clients::writeLog([
                'action' => 'get wave pick ID from pallet data outbound',
                'data' => [
                    'arrData' => $dataSend
                ]
            ]);
            Clients::setAlertReader($sock);
        }
    }

    /**
     * update wave pick and wave pick detail when receive response from WMS
     *
     * @return void
     */
    public static function updateWavePickAndDetail($wavePickID)
    {
        $query = WpProcessing::where('wave_id', $wavePickID)
            ->where('status', WpProcessing::STATUS_PICKING);
        $wp = $query->first();
        $query->update([
            'status' => WpProcessing::STATUS_PICKED
        ]);
        WpDetailProcessing::where('wp_processing_id', $wp->wp_processing_id)
            ->where('status', WpProcessing::STATUS_PICKING)
            ->update([
                'status' => WpProcessing::STATUS_PICKED
            ]);
    }


    /*
     * this function just to test update pallet to WMS
     */
    public function testUpdatePalletWMS()
    {
        /*$dir = str_replace("\\","/",base_path()."/storage/logs/data-pallet-send-wms.txt");
        $file = fopen($dir,"a");
        $str = json_encode($this->infoGate, JSON_FORCE_OBJECT).PHP_EOL;
        $str .= json_encode($this->dataParse, JSON_FORCE_OBJECT).PHP_EOL;
        $str .= "---------------------------------".PHP_EOL;
        fwrite($file,"Data send to WMS  => ".PHP_EOL.$str);
        fclose($file);*/
        $this->infoGate['whs_id'] = 1;

        $dataSend = [
            "pallet"=> [
                "pallet-rfid"=>"123456",
                "ctn-rfid"=> [
                    "0"=>"00000000000000000044092",
                    "1"=>"00000000000000000049391"
                ]
            ]
        ];

        $token = Clients::ConnectGetTokenWmsScan();
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $url = MyHelper::getUrlPallet($this->infoGate['whs_id']);
        Clients::ConnectWmsData('POST', $url, $dataSend, $request);
        //update pallet rfid => get pick position ID
        $robotProcess = RobotProcessing::where('status', 'Picked')->first();
        //get position follow drop position
        if ($robotProcess) {
            $pos = Position::where('position_id', $robotProcess->drop_position_id)->first();
            $pos->rfid = $dataSend['pallet']['pallet-rfid'];
            $pos->save();
        }
    }
}
