<?php

namespace App\Jobs;

use App\Libraries\Clients;
use App\Libraries\MyHelper;
use App\Models\AsnDetailProcessing;
use App\Models\CartonProcessing;
use Illuminate\Http\Request;

class UpdateScanCartonJob extends Job
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
        $params = [
            "asn_hdr_id" => $this->infoGate['asn_hdr_id'],
            "asn_dtl_id" => $this->infoGate['asn_dtl_id'],
            "item_id" => $this->infoGate['item_id'],
            "type" => 1,
            "ctnr_id" => $this->infoGate['ctnr_id'],
            "ctns_rfid" => $this->dataParse
        ];
        //update carton to WMS
        $token = Clients::ConnectGetTokenWmsScan();
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $url = MyHelper::getUrlUpdateMultiCarton($this->infoGate['whs_id'], $this->infoGate['cus_id']);
        $dataRes = Clients::ConnectWmsData('POST', $url, $params, $request);
        Clients::writeLog([
            'action' => 'get wave pick ID from pallet data outbound',
            'data' => [
                'arrData' => $dataRes
            ]
        ]);
        //check status update from WMS and update status again to wap_db
        $dataResArr = json_decode($dataRes, true);
        if (is_array($dataResArr) && array_key_exists('data', $dataResArr)) {
            if ($dataResArr['data']['status'] == true) {
                CartonProcessing::whereIn('rfid', $this->dataParse)->update(['status' => CartonProcessing::STATUS_RECEIVED]);
            }
        }
    }

    /*
     * this function just use to test
     */
    public function testUpdateWMS()
    {
        $params = [
            "asn_hdr_id" => "167",
            "asn_dtl_id" => "248",
            "item_id" => "117",
            "gate_code" => "G-0121212",
            "ctnr_id" => "47",
            "ctns_rfid" => [
                "0" => '0000000000000000004'.rand(1000, 9999)
            ]
        ];
        $this->infoGate['whs_id'] = 1;
        $this->infoGate['cus_id'] = 2;

        /*$dir = str_replace("\\","/",base_path()."/storage/logs/data-carton-send-wms.txt");
         $file = fopen($dir,"a");
         $str = json_encode($params, JSON_FORCE_OBJECT).PHP_EOL;
         $str .= "---------------------------------".PHP_EOL;
         fwrite($file,"Data send to WMS  => ".PHP_EOL.$str);
         fclose($file);*/

        $token = Clients::ConnectGetTokenWmsScan();
        $request = new Request();
        $request->headers->set('Authorization', 'Bearer ' . $token);
        $url = MyHelper::getUrlUpdateMultiCarton($this->infoGate['whs_id'], $this->infoGate['cus_id']);
        Clients::ConnectWmsData('POST', $url, $params, $request);
    }
}
