<?php

namespace App\Http\Controllers\Wap;

use App\Jobs\UpdateScanJob;
use App\Libraries\UrlWmsOutBound;
use App\Models\Device;
use App\Models\DeviceFactory;
use App\Models\Position;
use App\Models\RfidTags;
use App\Models\RfidTagsFactory;
use App\Models\RobotProcessing;
use App\Models\User;
use App\Libraries\MyHelper;
use App\Libraries\Clients;
use Hash;
use JWTAuth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Mockery\CountValidator\Exception;
use App\Http\Controllers\Socket\SocketRfidController;
use App\Http\Controllers\Wap\AsnController;
use App\Models\PalletProcessing;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class ScanerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * send cartons to wms one by one
     * @param type $wshId
     * @return type
     */
    public function updateCartonOutbound(Request $request, $whsId)
    {
        try {
            $infoConnect = Device::getDetailDevice('RFID_CG_01');
            if (isset($whsId)) {
                //$dataCarton = SocketRfidController::requestTagID($infoConnect['host_ip'], $infoConnect['port']);
                $dataCarton = [
                    'status' => true,
                    'data' => ['123456']
                ];
                if ($dataCarton) {
                    if (array_key_exists('status', $dataCarton)) {
                        $url = UrlWmsOutBound::getUrlScanCarton($whsId, $dataCarton['data'][0]);
                        $data = Clients::ConnectWmsData('GET', $url, [],$request);
                        if ($data) {
                            $dataArr = json_decode($data, true);
                            return [
                                'status' => true,
                                'data' => $dataArr['data']
                            ];
                        } else {
                            return [
                                'status' => false,
                                'data' => [
                                    'code' => $dataCarton['data'][0],
                                    'message' => 'Can not find RFID code!'
                                ]
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }


    /**
     * Scan container when user click "Scan Container" button
     * @param type $whsId
     * @return type
     */
    public static function scanContainer(){
        try {
            $infoConnect = Device::getDetailDevice('RFID_CG_01');
            //$dataContainer = SocketRfidController::requestTagID($infoConnect['host_ip'], $infoConnect['port']);
            $dataContainer = [
                'status' => true,
                'data' => ['000000000000007']
            ];
            if (array_key_exists('status', $dataContainer)) {
                if ($dataContainer['status']) {
                    return [
                        'status' => true,
                        'data' => [
                            'code' => $dataContainer['data'][0]
                        ]
                    ];
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * Scan rack
     * @param type $whsId
     * @return type
     */
    
    public static function scanRac($whsId){
        try {
            if (isset($whsId)) {
                $infoConnect = Device::getDetailDevice('RFID_FG_01');
                $dataRac = SocketRfidController::requestTagID($infoConnect['host_ip'], $infoConnect['port']);
                if (array_key_exists('status', $dataRac)) {
                    if ($dataRac['status']) {
                        $dataRep = Position::where('rfid', $dataRac['data'][0])->first();
                        if ($dataRep) {
                            return [
                                'status' => true,
                                'data' => [
                                    'code' => $dataRep->code,
                                    'rfid' => $dataRep->rfid
                                ]
                            ];
                        } else {
                            return [
                                'status' => false,
                                'data' => [
                                    'message' => 'Can not find Rack code!'
                                ]
                            ];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }
    public static function transferDataToFE($result)
    {
        $ipServerNodejs = env('IP_SERVER_NODEJS', '');
        $postNodejs = env('PORT_NODEJS', '');
        $client = new Client(new Version1X($ipServerNodejs . ':' . $postNodejs));
        $client->initialize();
        $client->emit('senddata', [
            'dataRes' => [
                'action' => 'ScanRack',
                'result' => $result
            ]
        ]);
        $client->close();
    }
    public static function scanRack($test,$processorId){
        $token = Clients::ConnectGetTokenWmsScan();                    
        $request = new Request();                    
        $request->headers->set('Authorization', 'Bearer ' . $token);
        try {
//               $test = [
//                    'DDDDDDDD0000000000000003',
//                    'FFFFFFFF0000000000000003',
//                    'AFSDFASDF13124124123412',
//                    '1241234ASGRY412341234125'
//                ];
                $pallet ='';
                $rack = '';
                $whsId=1;
//                $processorId=4;
//                dd();
                
                foreach ($test as $value) {
                    if(substr($value,0,8) =='FFFFFFFF')
                    {
                        $pallet=$value;
                    }
                    elseif(substr($value,0,8) =='DDDDDDDD')
                    {
                        $rack=$value;
                    };
                };
                if($rack != ''&&$pallet!='')
                {
                PalletProcessing::updateStatus($processorId, $pallet,PalletProcessing::STATUS_PICKING);


                    
                     $result = [
                        'pallet'=>$pallet,
                        'rack'=>$rack
                    ];
                    ScanerController::transferDataToFE($result);

                }
                elseif($pallet!='')
                {
//                    dd($pallet);
                    PalletProcessing::updateStatus($processorId, $pallet,PalletProcessing::STATUS_PICKING);
                    $result = [
                        'pallet'=>$pallet
                    ];
                     ScanerController::transferDataToFE($result);
                }
                else
                {
                    $result = [];
                    ScanerController::transferDataToFE($result);
                    if(PalletProcessing::getStatus($processorId)== PalletProcessing::STATUS_PICKING)
                    {
                        PalletProcessing::updateOnlyStatus($processorId,PalletProcessing::STATUS_DROPPED);
                    }
                    
                }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

}
