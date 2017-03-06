<?php

namespace App\Http\Controllers\Wap;

use App\Jobs\CallRobot;
use App\Libraries\UrlWmsOutBound;
use App\Models\Device;
use App\Models\LogEvent;
use App\Models\PalletProcessing;
use App\Models\RobotProcessing;
use App\Models\User;
use App\Models\Location;
use App\Models\Position;
use App\Libraries\MyHelper;
use App\Libraries\Clients;
use App\Models\WpDetailProcessing;
use App\Models\WpProcessing;
use Hash;
use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Mockery\CountValidator\Exception;
use App\Http\Controllers\Socket\SocketRfidController;
use App\Http\Controllers\Wap\ServerController;
use Illuminate\Http\Request;

class WaveController extends Controller
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
     * @param Request $request
     * @param $wshId
     * @return array|string
     */
    public function wavePickList(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlWarePick($wshId);
                $dataWms = ['whs_id' => $wshId];
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function getEmptyLocationPA(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getEmptyLocationPA($wshId);
                $dataWms = ['whs_id' => $wshId];
//                dd($url);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function getOrderList(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $params = Input::all();

                if (isset($params['limit'])) {
                    $limit = $params['limit'];
                } else {
                    $limit = '';
                }
                if (isset($params['page'])) {
                    $page = $params['page'];
                } else {
                    $page = '';
                }

                $url = UrlWmsOutBound::getOrderList($wshId, $limit, $page);
                $dataWms = ['whs_id' => $wshId, 'limit' => $limit, 'page' => $page];
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function wavePickDetail(Request $request, $whsId, $wvDtlId)
    {
        try {
            if (isset($whsId)) {
                $url = UrlWmsOutBound::getWavePickDetail($whsId, $wvDtlId);

                $dataWms = ['whs_id' => $whsId, 'wv_dtl_id' => $wvDtlId];
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function getOrderId(Request $request, $whsId, $orderId)
    {
        try {
            if (isset($whsId)) {
                $url = UrlWmsOutBound::getOrderId($whsId, $orderId);
                $dataWms = ['whs_id' => $whsId, 'order_id' => $orderId];
//                 dd($url);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function getDetailSku(Request $request, $wshId, $orderDtlId)
    {
        try {
            if (isset($wshId) && isset($orderDtlId)) {
                $url = UrlWmsOutBound::getDetailSku($wshId, $orderDtlId);
                $dataWms = ['whs_id' => $wshId, 'order_dtl_id' => $orderDtlId];
//                 dd($url);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function assignFullCarton(Request $request, $wshId, $orderDtlId)
    {
        $params = Input::all();
        try {
            if (isset($wshId) && isset($orderDtlId)) {
                $url = UrlWmsOutBound::assignFullCarton($wshId, $orderDtlId);
//                 dd($url);
                $dataWms = ['whs_id' => $wshId, 'order_dtl_id' => $orderDtlId, 'ctn_rfid' => $params['ctn_rfid']];
                $data = Clients::ConnectWmsData('PUT', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function assignFullCartons(Request $request, $wshId, $orderDtlId)
    {
        $params = Input::all();
        try {
            if (isset($wshId) && isset($orderDtlId)) {
                $url = UrlWmsOutBound::assignFullCartons($wshId, $orderDtlId);
                $data = Clients::ConnectWmsDataJson('PUT', $url, $params, $request);
                $dataWms = json_decode($data, true);
                if (isset($dataWms['status']) && $dataWms['status'] == false) {
                    return MyHelper::response(false, $dataWms['data']['message'], 200);
                } else {
                    return MyHelper::response(true, $dataWms['data'], 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function assignFullCartonRfid(Request $request, $wshId, $ctnRfid)
    {
        $params = Input::all();
        try {
            if (isset($wshId) && isset($ctnRfid)) {
                $url = UrlWmsOutBound::assignFullCarton($wshId, $ctnRfid);
//                 dd($url);
                $dataWms = ['whs_id' => $wshId, 'ctn_rfid' => $ctnRfid];
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function pickPiece(Request $request, $wshId, $orderDtlId)
    {
        $params = Input::all();
        try {
            if (isset($wshId) && isset($orderDtlId)) {
                $url = UrlWmsOutBound::pickPiece($wshId, $orderDtlId);

                $dataWms = ['whs_id' => $wshId, 'order_dtl_id' => $orderDtlId, 'ctn_rfid' => $params['ctn_rfid'], 'qty' => $params['qty']];
                $data = Clients::ConnectWmsData('PUT', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function getOrderShipping(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getOrderShipping($wshId);
                $dataWms = ['whs_id' => $wshId];
//                 dd($url);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function locationPutAwayList(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::locationPutAwayList($wshId);
                $dataWms = ['whs_id' => $wshId];
//                 dd($url);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function locationPickingList(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::locationPickingList($wshId);
                $dataWms = ['whs_id' => $wshId];
//                 dd($url);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $wshId
     * @param $wvDtlId
     * @return array|string
     */
    public function pickWaveList(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlWavePickList($wshId);
                $dataWms = ['whs_id' => $wshId];
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    public function wavePickSkuLocation(Request $request, $wshId, $wvDtlId)
    {
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlWarePickSkuLocation($wshId, $wvDtlId);
                $dataWms = ['whs_id' => $wshId, 'wv_dtl_id' => $wvDtlId];
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $wshId
     * @param $wvDtlId
     * @return array|string
     */
    public function wavePickSkuPickPallet(Request $request, $wshId, $wvDtlId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlWavePickSkuPickPallet($wshId, $wvDtlId);
                $data = Clients::ConnectWmsDataJson('PUT', $url, $params, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $whs_id
     * @param $rfid
     * @return array|string
     */
    public function scanCarton(Request $request, $whs_id, $rfid)
    {
        $wshId = $whs_id;
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlScanCarton($wshId, $rfid);
//                dd($url);
                $dataWms = ['whs_id' => $wshId, 'rfid' => $rfid];
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $whsId
     * @param $odrId
     * @return array|string
     */

    public function assignCarton(Request $request, $whsId, $odrId)
    {
        $params = Input::all();
        try {
            if (isset($whsId)) {
                $url = UrlWmsOutBound::getUrlAssignCarton($whsId, $odrId);
                $data = Clients::ConnectWmsDataJson('PUT', $url, $params, $request);
                $dataWms = json_decode($data);
                $dataRes = $dataWms->data;
                $userId = AsnController::getUserId($request);
                if (isset($dataRes->status) && !$dataRes->status) {
                    LogEvent::saveLog(
                        LogEvent::ASSIGN_CARTON_TO_ORDER,
                        $userId,
                        null,
                        ['order_num' => $dataRes->message,"errms" =>$dataRes->message ]
                    );
                    return MyHelper::response(false, $dataRes->message, 200);
                } else {
                    LogEvent::saveLog(
                        LogEvent::ASSIGN_CARTON_TO_ORDER,
                        $userId,
                        null,
                        ['order_num' => $dataWms->data,'ctn_rfid' => json_encode($params), "errms" =>null]
                    );
                    return MyHelper::response(true, $dataWms->data, 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $whsId
     * @param $odrId
     * @return array|string
     */

    public function getAssignCarton(Request $request, $whsId, $odrId)
    {
        $params = Input::all();
        try {
            if (isset($whsId)) {
                $url = UrlWmsOutBound::getUrlAssignCarton($whsId, $odrId);
                $data = Clients::ConnectWmsData('GET', $url, $params, $request);
                $dataWms = json_decode($data);
                $userId = AsnController::getUserId($request);
                if (isset($dataWms->status) && !$dataWms->status) {
                    LogEvent::saveLog(
                        LogEvent::ASSIGN_CARTON_TO_ORDER,
                        $userId,
                        null,
                        ['order_num' => $dataWms->message,'ctn_rfid' => json_encode($params),"errms" =>$dataWms->message ]
                    );
                    return MyHelper::response(false, $dataWms->message, 200);
                } else {
                    LogEvent::saveLog(
                        LogEvent::ASSIGN_CARTON_TO_ORDER,
                        $userId,
                        null,
                        ['order_num' => $dataWms->odr_num,'ctn_rfid' =>$dataWms->cartons, "errms" =>null]
                    );
                    return MyHelper::response(true, $dataWms, 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function pickFullCarton(Request $request, $wshId, $wvDtlId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlPickFullCarton($wshId, $wvDtlId);
                $data = Clients::ConnectWmsDataJson('PUT', $url, $params, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @return array
     */
    public function saveWavePick(Request $request)
    {
        $params = Input::all();
        try {
            $token = $request->header('Authorization');
            $user = Clients::jwtDecode($token);
            $userName = $user['username'];
            $userId = User::getUser($userName);
            $pallet = PalletProcessing::getPalletProcessingId($userId['user_id']);
            if($pallet){
                PalletProcessing::updateOnlyStatus($userId['user_id'],PalletProcessing::STATUS_PICKING);
            }
            $data = array_add($params, 'user_id', $userId['user_id']);
            $wpData = WpProcessing::getWpId($params['wave_id']);
            $userId = AsnController::getUserId($request);
            if (!$wpData) {
                WpProcessing::createWp($data);
                $wp = WpProcessing::where('wave_id', $params['wave_id'])->first();
                $detailData = array_add($params, 'wp_processing_id', $wp->wp_processing_id);
                WpDetailProcessing::createWpDetail($detailData);
                LogEvent::saveLog(
                    LogEvent::PROCESS_WAVE_PICK,
                    $userId,
                    null,
                    ['wv_num' =>$params['wv_num'],'sku_num' =>$params['sku_num']]
                );
                return MyHelper::response(true, trans('messages.success'), 200);
            } else {
                $wpDataDetail = WpDetailProcessing::getWpDetailId($params['wave_detail_id']);
                $detailData = array_add($params, 'wp_processing_id', $wpData->wp_processing_id);
                if (!$wpDataDetail) {
                    WpDetailProcessing::createWpDetail($detailData);
                }
                LogEvent::saveLog(
                    LogEvent::PROCESS_WAVE_PICK,
                    $userId,
                    null,
                    ['wv_num' =>$params['wv_num'],'sku_num' =>$params['sku_num']]
                );
                return MyHelper::response(true, trans('messages.success'), 200);
            }

        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function AssignPackedCartonsToPallet(Request $request, $wshId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlAssignPackedCartonsToPallet($wshId);
                $data = Clients::ConnectWmsDataJson('PUT', $url, $params, $request);
                $dataWms = json_decode($data, true);
                $userId = AsnController::getUserId($request);
                if (isset($dataWms['status']) && $dataWms['status'] == false) {
                    LogEvent::saveLog(
                        LogEvent::CALL_WMS_FAILD,
                        $userId,
                        null,
                        ['pallet_num' =>$params['pallet'],'ctn_num' => json_encode($params['pallet']) ,'errms' =>  $dataWms['message']]
                    );
                    return MyHelper::response(false, $dataWms['message'], 200);
                } else {
                    LogEvent::saveLog(
                        LogEvent::CALL_WMS_FAILD,
                        $userId,
                        null,
                        ['pallet_num' =>$params['pallet'],'ctn_num' => json_encode($params['pallet']) ,'errms' =>  null]
                    );
                    return MyHelper::response(true, $dataWms['data'], 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function PutDropPalletOnShippingLane(Request $request, $wshId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlPutDropPalletOnShippingLane($wshId);
                $data = Clients::ConnectWmsDataJson('PUT', $url, $params, $request);
                $dataWms = json_decode($data, true);
                return MyHelper::response(true, $dataWms, 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function getActiveLocation(Request $request, $wshId, $wvDtlId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlGetActiveLocation($wshId, $wvDtlId);
                $data = Clients::ConnectWmsDataJson('POST', $url, $params, $request);
                $dataWms = json_decode($data, true);
                if (isset($dataWms['status']) && $dataWms['status'] == false) {
                    return MyHelper::response(false, $dataWms['message'], 200);
                } else {
                    return MyHelper::response(true, $dataWms['data'], 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    public function getMoreSuggestLocation(Request $request, $wshId, $wvDtlId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlGetMoreSuggestLocation($wshId, $wvDtlId);
                $data = Clients::ConnectWmsDataJson('POST', $url, $params, $request);
                $dataWms = json_decode($data, true);
                if (isset($dataWms['status']) && $dataWms['status'] == false) {
                    return MyHelper::response(false, $dataWms['message'], 200);
                } else {
                    return MyHelper::response(true, $dataWms['data'], 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    public function getCartonsOfaPallet(Request $request, $wshId, $wvDtlId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlGetCartonsOfaPallet($wshId, $wvDtlId);
                $data = Clients::ConnectWmsData('GET', $url, $params, $request);
                $dataWms = json_decode($data, true);

                if (isset($dataWms['status']) && $dataWms['status'] == false) {
                    return MyHelper::response(false, $dataWms['message'], 200);
                } else {
                    return MyHelper::response(true, $dataWms['data'], 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    public function getNextSku(Request $request, $wshId, $wvId, $wvDtlId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $url = UrlWmsOutBound::getUrlGetNextSku($wshId, $wvId, $wvDtlId);
                $data = Clients::ConnectWmsData('GET', $url, $params, $request);
                $dataWms = json_decode($data, true);
                if (isset($dataWms['status']) && $dataWms['status'] == false) {
                    return MyHelper::response(false, $dataWms['data']['message'], 200);
                } else {
                    return MyHelper::response(true, $dataWms['data'], 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }


}

