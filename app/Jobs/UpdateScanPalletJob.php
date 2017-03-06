<?php

namespace App\Jobs;

use App\Libraries\Clients;
use App\Libraries\Helpers\SocketCLI\ConnectRFID;
use App\Libraries\MyHelper;
use App\Models\AsnDetailProcessing;
use App\Models\AsnProcessing;
use App\Models\LogEvent;
use App\Models\Position;
use App\Models\RecProcessing;
use App\Models\RfidTags;
use App\Models\RobotProcessing;
use Illuminate\Http\Request;

class UpdateScanPalletJob extends Job
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
        //self::updateCartonToWMS($this->infoGate, $this->dataParse);
    }

    public static function updateCartonToWMS($sock, $infoGate, $dataParse)
    {
        Clients::writeLog([
            'action' => 'data received gateway inbound',
            'data' => [
                'dataSend' => $dataParse
            ]
        ]);
        $dataSend = RfidTags::filterDataScanGateWay($dataParse);
        if ($dataSend != null && isset($dataSend['pallet']['pallet-rfid']) && $dataSend['pallet']['pallet-rfid'] != "") {
            $token = Clients::ConnectGetTokenWmsScan();
            $request = new Request();
            $request->headers->set('Authorization', 'Bearer ' . $token);
            $url = MyHelper::getUrlPallet($infoGate['whs_id']);
            $dataResponse = Clients::ConnectWmsData('PUT', $url, $dataSend, $request);
            Clients::writeLog([
                'action' => 'data before send to WMS at gateway inbound',
                'data' => [
                    'url' => $url,
                    'dataSend' => $dataSend
                ]
            ]);
            LogEvent::saveLog(
                LogEvent::SCAN_PALLET_INBOUND,
                null,
                null,
                ['pallet_rfid' => $dataSend['pallet']['pallet-rfid'], 'ctn_rfids' => json_encode($dataSend['pallet']['ctn-rfid'])]
            );
            //check asn had created good receipt? => update database_wap
            $arrData = json_decode($dataResponse, true);
            Clients::writeLog([
                'action' => 'data after send to WMS at gateway inbound',
                'data' => [
                    'arrData' => $dataResponse
                ]
            ]);
            if ($arrData['status'] == true) {
                if ($arrData['gr-status'] == true) {
                    if (array_key_exists('data', $arrData)) {
                        if (array_key_exists('detail', $arrData['data'])) {
                            self::checkGoodReceiptUpdateDB($arrData['data']['detail']['asn_hdr_id']);
                        }
                    }
                } else {
                    Clients::setAlertReader($sock);
                }
            } else {
                Clients::setAlertReader($sock);
            }
        }

    }

    /*
     * check good receipt for each asn to update status for db_wap
     */
    public static function checkGoodReceiptUpdateDB($asnID)
    {
        $queryAsn = AsnProcessing::where('asn_id', $asnID)->get();
        if (count($queryAsn) > 0) {
            foreach ($queryAsn as $item) {
                $item->status = AsnProcessing::STATUS_RECEIVED;
                $item->save();
                AsnDetailProcessing::where('asn_processing_id', $item->asn_processing_id)
                    ->update(['status' => AsnDetailProcessing::STATUS_RECEIVED]);
                $listAsnDetail = AsnDetailProcessing::where('asn_processing_id', $item->asn_processing_id)
                    ->get();
                if (count($listAsnDetail) > 0) {
                    $arrAsnIdDetail = $listAsnDetail->toArray();
                    $listId = [];
                    foreach ($arrAsnIdDetail as $val) {
                        $listId[] = $val;
                    }
                    RecProcessing::whereIn('asn_detail_processing_id', $listId)
                        ->update(['status' => RecProcessing::STATUS_RECEIVED]);
                }
            }
        }
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
            "pallet" => [
                "pallet-rfid" => "123456",
                "ctn-rfid" => [
                    "0" => "00000000000000000044092",
                    "1" => "00000000000000000049391"
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
