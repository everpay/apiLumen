<?php

namespace App\Http\Controllers\Wap;

use App\Http\Controllers\Auth\AuthController;
use App\Jobs\CallRobot;
use App\Jobs\UpdateAsnsJob;
use App\Jobs\UpdateScanPalletJob;
use App\Models\CartonProcessing;
use App\Models\Device;
use App\Models\LogEvent;
use App\Models\RecProcessing;
use App\Models\RobotProcessing;
use App\Models\User;
use App\Models\Location;
use App\Models\Position;
use App\Models\AsnDetailProcessing;
use App\Models\AsnProcessing;
use App\Libraries\MyHelper;
use App\Libraries\Clients;
use App\Models\Warehouse;
use Hash;
use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Mockery\CountValidator\Exception;
use App\Http\Controllers\Socket\SocketRfidController;
use App\Http\Controllers\Wap\ServerController;
use Illuminate\Http\Request;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class AsnController extends Controller
{
    const ACTION_SEND_DATA_SOCKET = 'senddata';

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
     * @param $whsId
     * @return array|string
     */
    public function asnList(Request $request, $whsId)
    {
        $params = Input::all();

        if (isset($params['ctnr_id'])) {
            $ctnrID = $params['ctnr_id'];
        } else {
            $ctnrID = '';
        }
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
        if (isset($params['cus_id'])) {
            $cusId = $params['cus_id'];
        } else {
            $cusId = '';
        }
        $url = MyHelper::getUrlAsn($whsId, $cusId, $ctnrID, $limit, $page);
        $dataWms = ['whs_id' => $whsId, 'cus_id' => $cusId, 'ctnr_id' => $ctnrID];
        $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
        return $data;
    }

    /**
     * @param Request $request
     * @param $whsId
     * @return array|string
     */
    public function asnListDetail(Request $request, $whsId)
    {
        $params = Input::all();

        if (isset($params['ctnr_id'])) {
            $ctnrID = $params['ctnr_id'];
        } else {
            $ctnrID = '';
        }

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
        if (isset($params['cus_id'])) {
            $cusId = $params['cus_id'];
        } else {
            $cusId = '';
        }
        $url = MyHelper::getUrlAsnListDetail($whsId, $cusId, $ctnrID, $limit, $page);
        $dataWms = ['whs_id' => $whsId, 'cus_id' => $cusId, 'ctnr_id' => $ctnrID, 'limit' => $limit, 'page' => $page];
        $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
        return $data;
    }

    /**
     * @param $request
     * @param $whsId
     * @param $cusId
     * @param $asnDtlId
     * @param $checkerId
     * @return array|string
     */
    public static function asnHistory($request, $whsId, $cusId, $asnDtlId, $checkerId)
    {

        $params = Input::all();
        $url = MyHelper::getUrlAsnHistory($whsId, $cusId, $asnDtlId);
        $dataWms = ['whs_id' => $whsId, 'cus_id' => $cusId, 'asn-history' => $asnDtlId];
        $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
        $dataDecode = \GuzzleHttp\json_decode($data);
        $currentAsn = [];
        $history['history'] = [];
        foreach ($dataDecode->history as $key => $value) {
            if ($value->asn_dtl_id == $asnDtlId) {
                $currentAsn['current'] = $value;
                $currentAsn['cua_id'] = $cusId;

            }
            $history['history'][] = $value;
        }
        $recReceiving = RecProcessing::getStatusRv($checkerId);
        $recReceivingId = $recReceiving->asn_detail_processing_id;

        $cartonList = CartonProcessing::getCartonProcessingByAsnDetailProcessing($recReceivingId);
        $listScannedCarton['listScannedCarton'] = [];
        if (count($cartonList) == 0) {
            $listScannedCarton['listScannedCarton'] == Null;
        } else {
            $listScannedCarton['listScannedCarton'] = $cartonList;
        }
        $dataCurrentHistory = array_merge($currentAsn, $history, $listScannedCarton);
        if (count($dataCurrentHistory) > 0) {
            $dataSend = [
                'action' => "ScanCarton",
                'checker' => $checkerId,
                'skuInfo' => $dataCurrentHistory,
            ];

            Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
            //self::socketTransferDataToFE('ScanCarton', $dataCurrentHistory, $checkerId);
        }
        return $currentAsn;
    }

    public function detailAsn(Request $request, $wshId, $cusId, $asnId, $ctnrId)
    {
        try {
            if (isset($wshId) && isset($cusId) && isset($asnId) && isset($ctnrId)) {
                $url = MyHelper::getUrlDetailAsn($wshId, $cusId, $asnId, $ctnrId);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId, 'asn_id' => $asnId, 'ctnr_id' => $ctnrId];
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                if ($data) {
                    $dataJson = json_decode($data);
                    if (array_key_exists('data', $dataJson)) {
                        $asnDetailID = $dataJson->data->asn_details[0]->asn_dtl_id;
                        $params['asn_dtl_id'] = $asnDetailID;
                        $url = MyHelper::getUrlListVirtualCartonByAsnDetail($wshId, $cusId, $params);
                        $dataFinal = Clients::ConnectWmsData('GET', $url, [], $request);
                        $dataFinalJson = json_decode($dataFinal);
                        $dataJson->data->asn_details[0]->real_count_scan = count($dataFinalJson->data->rfid);
                        return json_encode($dataJson);
                    }
                }
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }


    }

    /**
     * @param Request $request
     * @param $wshId
     * @return array|string
     */
    public function cusList(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $url = MyHelper::getUrlCus($wshId);
                $dataWms = ['whs_id' => $wshId];
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function getEmptyLocationEnvironsRack(Request $request, $wshId, $cusId)
    {
        try {
            if (isset($wshId) && isset($cusId)) {
                $url = MyHelper::getUrlEmptyLocationEnvironsRack($wshId, $cusId);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId];
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
     * @return array|string
     */

    public function containersList(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $url = MyHelper::getUrlContainerList($wshId);
                $dataWms = ['whs_id' => $wshId];
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
     * @param $cusId
     * @return array
     */
    public function pickPutAway(Request $request, $wshId, $cusId)
    {
        try {
            $params = Input::all();

            if (isset($wshId) && isset($cusId) && isset($params)) {

                if ($params['last_status'] == Position::STATUS_PICKING) {
                    $positionStatusPicking = Position::STATUS_PICKING;
                    Position::updateStatus($params['ref_hex'], $positionStatusPicking, $skuNum = null);
                    $url = MyHelper::getUrlPickPutAway($wshId, $cusId);
                    $loc = Position::getPosition($params['ref_hex']);
                    $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId, 'loc-code' => $loc->code, 'pallet-rfid' => $loc->rfid];
                    $data = Clients::ConnectWmsData('PUT', $url, $dataWms, $request);
                }


                return MyHelper::response(true, "successful", 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $whsId
     * @param $cusId
     * @param $asnDtlId
     * @return array|string
     */
    public function asnDetail(Request $request, $whsId, $cusId, $asnDtlId)
    {
        try {
            if (isset($whsId) && isset($cusId) && isset($asnDtlId)) {
                $url = MyHelper::getUrlAsnDetail($whsId, $cusId, $asnDtlId);
                $dataWms = ['whs_id' => $whsId, 'cus_id' => $cusId, 'asn_dtl_id' => $asnDtlId];
                $data = Clients::ConnectWmsData('PUT', $url, $dataWms, $request);
                $abc = json_decode($data);
                if ($abc->{'gr-status'}) {
                    $asn = $abc->data->detail;
                    $asnId = $asn->asn_hdr_id;
                    UpdateScanPalletJob::checkGoodReceiptUpdateDB($asnId);
                    return MyHelper::response(true, $abc->message, 200);
                } else {
                    return MyHelper::responseMs(true,  trans('messages.success'), 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    /**
     * @param Request $request
     * @param $wshId
     * @param $cusId
     * @return array|string
     */
    public function putPallet(Request $request, $wshId)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($params)) {
                $url = MyHelper::getUrlPutPallet($wshId);
                $data = Clients::ConnectWmsDataJson('PUT', $url, $params, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function updateStatusRV(Request $request, $wshId)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($params)) {
                $userId = self::getUserId($request);
                $params['user_id'] = $userId;
                $params['asn_hdr_id'] = $params['asn_id'];
                $asnDetailProcessing = AsnDetailProcessing::getAsnsDetailId($params['asn_dtl_id']);
                if ($asnDetailProcessing) {
                    $asnDetailProcessingId = $asnDetailProcessing->asn_detail_processing_id;
                    $userSku = RecProcessing::getRecUserId($params);
                    if ($userSku == Null) {
                        return MyHelper::response(false, trans('messages.sku-reader-user'), 200);
                    }
                    $asnDetail = RecProcessing::getStatusRvAsn($asnDetailProcessingId);

                    if (!$asnDetail->isEmpty()) {
                        return MyHelper::response(false, trans('messages.sku-error'), 200);
                    }
                }
                AsnProcessing::updateStatus($params['asn_id'], $params['ctnr_id'], $params);
                AsnDetailProcessing::updateStatus($params['asn_dtl_id'], $params);

                $asnDetailProcessing = AsnDetailProcessing::getAsnsDetailId($params['asn_dtl_id']);
                $asnDetailProcessingId = $asnDetailProcessing->asn_detail_processing_id;
                self::updateStatusOnHoldToReceived();
                if ($asnDetailProcessingId) {
                    RecProcessing::updateStatus($asnDetailProcessingId, $params);
                }
                $asnProcessing = AsnProcessing::getAsnsId($params['asn_id']);

                $cusId = $asnProcessing->cus_id;
                $whsId = Warehouse::getWarehouseId();
                $asnDetailPro = AsnDetailProcessing::getAsnsDetailId($params['asn_dtl_id']);
                $checkerId = RecProcessing::getCheckerId($asnDetailPro->asn_detail_processing_id);
                self::asnHistory($request, $whsId, $cusId, $params['asn_dtl_id'], $checkerId);
                $dataStatusAsn = AsnProcessing::getAsnsStatus();
                foreach ($dataStatusAsn as $key => $value) {
                    $dataStatusAsnDetail = RecProcessing::getRecStatus($value['asn_id']);
                    foreach ($dataStatusAsnDetail as $k => $v) {
                        $expectedCartons = CartonProcessing::getCountCartonProcessingByAsnDetail($v['asn_detail_processing_id']);
                        $dataStatusAsnDetail[$k]['expected_cartons'] = $expectedCartons;
                    }
                    $dataStatusAsn[$key]['asn_detail'] = $dataStatusAsnDetail;
                }
                if (count($dataStatusAsn) > 0) {
                    $dataSend = [
                        'action' => "AsnList",
                        'checker' => $checkerId,
                        'dataRes' => $dataStatusAsn,
                    ];
                    Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
                    //self::socketTransferDataToFE('AsnList', $dataStatusAsn, $checkerId);
                }
                $userId = self::getUserId($request);
                LogEvent::saveLog(
                    LogEvent::PROCESS_ASN,
                    $userId,
                    null,
                    ['asn_num' => $params['asn_hdr_num'], 'sku_num' => $params['sku']]
                );
                return MyHelper::response(true, trans('messages.success'), 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }


    /**
     * @return array
     */
    public static function updateStatusOnHoldToReceived()
    {
        try {
            $queryAsnOnHold = RecProcessing::getRecStatusOnHold();
            foreach ($queryAsnOnHold as $key => $value) {
                if ($value->asn_dtl_id) {
                    $cartonExpected = CartonProcessing::getExpectedCartonProcessingByAsnDetail($value->asn_dtl_id);
                    if (isset($cartonExpected) && ($cartonExpected == $value->expected_cartons || $cartonExpected > $value->expected_cartons)) {
                        RecProcessing::updateStatusRd($value->asn_dtl_id);
                    }
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $wshId
     * @param $cusId
     * @return array|string
     */
    public function suggestPositionPallet(Request $request, $wshId, $cusId)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($cusId) && isset($params['pallet-rfid'])) {
                $url = MyHelper::getUrlSuggetPallet($wshId, $cusId, $params['pallet-rfid']);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId];
                $dataWms = array_merge($dataWms, $params);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param $wshId
     * @param $cusId
     * @return array|string
     */
    public function suggestLocationPallet(Request $request, $wshId, $cusId)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($cusId)) {
                $url = MyHelper::getUrlLocationPallet($wshId, $cusId);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId];
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
     * @param $cusId
     * @return array|string
     */
    public function listAllLocation(Request $request, $wshId, $cusId)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($cusId) && isset($params['loc_type'])) {
                $url = MyHelper::getUrlListAllLocation($wshId, $cusId, $params['loc_type']);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId];
                $dataWms = array_merge($dataWms, $params);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    public function listLocation()
    {
        try {
            $arr = array_filter([
                'loc_type' => Input::get('loc_type'),
                'loc_rec' => Input::get('loc_rec'),
                'loc_paw' => Input::get('loc_paw'),
            ]);
            $dataResult = Location::getListLocation($arr);
            /*  foreach ($dataResult as $key => $value) {
                  if (isset($value->last_status) && $value->last_status != Null) {
                      $value->status_number = Position::$arrayStatus[$value->last_status];
                  }
              }*/
            return MyHelper::response(true, $dataResult, 200);

        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $wshId
     * @param $cusId
     * @return array|string
     */
    public function setDamageCarton(Request $request, $wshId, $cusId)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($cusId) && isset($params['ctn_rfid'])) {
                CartonProcessing::updateStatusRfid($params['ctn_rfid'], CartonProcessing::STATUS_DAMAGED);
                $url = MyHelper::getUrlSetDamageCarton($wshId, $cusId);
                $data = Clients::ConnectWmsDataJson('PUT', $url,  $params, $request);
                $dataWmsSet = \GuzzleHttp\json_decode($data);
                $userId = self::getUserId($request);
                if (isset($dataWmsSet->status) && $dataWmsSet->status == false) {
                    LogEvent::saveLog(
                        LogEvent::SET_DAMAGE_FOR_CARTON,
                        $userId,
                        null,
                        ['ctn_rfid' => $params['ctn_rfid'],]
                    );
                    return MyHelper::response(false, $dataWmsSet->message, 200);
                } else {
                    LogEvent::saveLog(
                        LogEvent::SET_DAMAGE_FOR_CARTON,
                        $userId,
                        null,
                        ['ctn_rfid' => $params['ctn_rfid'],]
                    );
                    return MyHelper::response(true, $dataWmsSet->message, 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $wshId
     * @param $cusId
     * @return array
     */
    public function deleteVirtualCarton(Request $request, $wshId, $cusId)
    {
        try {

            $params = Input::all();
            if (isset($wshId) && isset($cusId) && isset($params['ctn_rfid'])) {
                CartonProcessing::deleteRfid($params['ctn_rfid']);
                $url = MyHelper::getUrlDeleteVirtualCarton($wshId, $cusId, $params['ctn_rfid']);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId];
                $dataWms = array_merge($dataWms, $params);
                $data = Clients::ConnectWmsData('PUT', $url, $dataWms, $request);
                $dataWmsSet = \GuzzleHttp\json_decode($data);
                $userId = self::getUserId($request);
                if (isset($dataWmsSet->status) && $dataWmsSet->status == false) {
                    LogEvent::saveLog(
                        LogEvent::DELETE_CARTON,
                        $userId,
                        null,
                        ['ctn_rfid' => $params['ctn_rfid'],]
                    );
                    if (isset($dataWmsSet->empty) && $dataWmsSet->empty == true) {
                        return MyHelper::response(true, $dataWmsSet->message, 200);
                    } else {
                        return MyHelper::response(false, $dataWmsSet->message, 200);
                    }
                } else {
                    LogEvent::saveLog(
                        LogEvent::DELETE_CARTON,
                        $userId,
                        null,
                        ['ctn_rfid' => $params['ctn_rfid'],]
                    );

                    return MyHelper::response(true, $dataWmsSet->message, 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param $wshId
     * @param $cusId
     * @param $asns
     * @param $ctnrId
     * @return array|string
     */
    public static function listVirtualCarton($request, $wshId, $cusId, $asns, $ctnrId)
    {
        //$dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId,'asns'=>$asns,'containers'=>$ctnrId];
        //dd($dataWms);
        try {
            $params = Input::all();
            //dd($wshId);
            if (isset($wshId) && isset($cusId) && isset($asns) && isset($ctnrId)) {
                $url = MyHelper::getUrlListVirtualCartons($wshId, $cusId, $asns, $ctnrId);
                // dd($url);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId, 'asns' => $asns, 'containers' => $ctnrId];
                $dataWms = array_merge($dataWms, $params);
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
     * @param $cusId
     * @param $asns
     * @param $ctn_id
     * @return array|string
     */
    public function completeGoodReceipt(Request $request, $wshId, $cusId, $asns, $ctn_id)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($cusId)) {
                $url = MyHelper::getUrlCompleteGoodReceipt($wshId, $cusId, $asns, $ctn_id);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId, 'asns' => $asns, 'containers' => $ctn_id];
                $dataWms = array_merge($dataWms, $params);
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
     * @param $cusId
     * @param $asns
     * @param $ctn_id
     * @return array|string
     */
    public function createGoodReceipt(Request $request, $wshId, $cusId, $asns, $ctn_id)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($cusId) && isset($params['asn_dtl_id'])) {
                $url = MyHelper::getUrlCreateGoodReceipt($wshId, $cusId, $asns, $ctn_id);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId, 'asns' => $asns, 'containers' => $ctn_id];
                $dataWms = array_merge($dataWms, $params);
                $data = Clients::ConnectWmsData('POST', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    /**
     * @param Request $request
     * @param $wshId
     * @param $cusId
     * @param $asns
     * @return array|string
     */
    public function showAsnsDetail(Request $request, $wshId, $cusId, $asns)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($cusId) && isset($asns)) {
                $url = MyHelper::getUrlShowAsnsDetail($wshId, $cusId, $asns);
                $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId];
                $dataWms = array_merge($dataWms, $params);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    /**
     * @param $wshId
     * @param $cusId
     * @return array
     */
    public function updateStatus($wshId, $cusId)
    {
        try {
            $params = Input::all();
            if (isset($wshId) && isset($cusId) && isset($params)) {
                Position::updateStatus($params['ref_hex'], $params['last_status'], $params['sku_num']);
                if ($params['last_status'] == Position::STATUS_FULL) {
                    $idProcessing = AsnDetailProcessing::getAsnsDetailId($params['asn_dtl_id']);
                    dispatch((new CallRobot($idProcessing->asn_processing_id))->onQueue('call_robot'));
                }
                return MyHelper::response(true, trans('messages.success'), 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    /**
     * @param Request $request
     * @param $wshId
     * @return array
     */
    public function rfidList(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $token = $request->header('Authorization');
                $user = Clients::jwtDecode($token);
                $userId = $user['user_id'];
                $codeReceiving = Location::REC;
                $statusReceiving = AsnProcessing::STATUS_RECEIVING;
                $dataE = Location::getListLocation(['loc_type' => $codeReceiving]);
                $data = Location::getListLocationRv($codeReceiving, $statusReceiving);
                if ($data->isEmpty()) {
                    $result = $dataE;
                } else {
                    foreach ($dataE as $key => $value) {
                        foreach ($data as $k => $v) {
                            if ($value['location_id'] !== $v['location_id']) {
                                $result[] = $value;
                            }
                        }
                    }

                }

                return MyHelper::response(true, $result, 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $wshId
     * @return array
     */
    public function gateCodeReceivingUser(Request $request, $wshId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $token = $request->header('Authorization');
                $user = Clients::jwtDecode($token);
                $userId = $user['user_id'];
                $codeReceiving = Location::REC;
                $data = Position::getLocationReceiving($codeReceiving, $userId, $params);
                return MyHelper::response(true, $data, 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param $wshId
     * @return array
     */
    public function getCartonAsn($wshId)
    {
        $params = Input::all();
        try {
            if (isset($wshId)) {
                $data = CartonProcessing::getCartonProcessingByAsnDetail($params['asn_dtl_id']);
                return MyHelper::response(true, $data, 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $wshId
     * @return array
     */
    public function getAsnProcessing(Request $request, $wshId)
    {
        try {
            if (isset($wshId)) {
                $data = AsnDetailProcessing::getAsnsDetailProcessingRv();
                return MyHelper::response(true, $data, 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param $wshId
     * @return array
     */
    public function getReaderList($wshId)
    {
        try {
            if (isset($wshId)) {
                $deviceReader = Device::getListDevice();
                /*$deviceReaderReceiving = RecProcessing::getDeviceStatusRv();
                $dataReader1 = [];
                $dataReader2 = [];
                $data = [];
                if ($deviceReaderReceiving->isEmpty()) {
                    $data = $deviceReader;
                } else {
                    foreach ($deviceReader as $key => $value) {
                        foreach ($deviceReaderReceiving as $k => $v) {
                            if ($value['device_id'] != $v['rfid_reader_1']) {
                                $dataReader1[] = $value;
                            }
                            if ($value['device_id'] != $v['rfid_reader_2']) {
                                $dataReader2[] = $value;
                            }
                        }
                    }
                }
                foreach ($dataReader1 as $i => $v) {
                    foreach ($dataReader2 as $t => $e) {
                        if ($v['device_id'] == $e['device_id']) {
                            $data[] = $v;
                        }
                    }
                }*/
                return MyHelper::response(true, $deviceReader, 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }


    public function getEmptyLocations(Request $request, $whsId, $Rfid)
    {
        try {
            $params = Input::all();
            if (isset($whsId) && isset($Rfid)) {
                $dataWms = [];
                if (isset($params['loc_rfid'])) {
                    $url = MyHelper::getUrlEmptyLocations($whsId, $Rfid, $params['loc_rfid']);
                    $dataWms = ['whs_id' => $whsId, 'rfid' => $Rfid, 'loc_rfid' => $params['loc_rfid']];
                } else {
                    $url = MyHelper::getUrlEmptyLocations($whsId, $Rfid, false);
                    $dataWms = ['whs_id' => $whsId, 'rfid' => $Rfid];
                }
                $dataWms = array_merge($dataWms, $params);
                $data = Clients::ConnectWmsData('GET', $url, $dataWms, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }


    public static function getUserId($request)
    {
        $token = $request->header('Authorization');
        $userArray = Clients::jwtDecode($token);
        $user = User::getUser($userArray['username']);
        $userId = $user->user_id;
        return $userId;
    }


    public static function scanRackOutbound()
    {
        $dataSend = [
            'action' => "ScanRackOutbound",
            'result' => ['rack' => "DDDDDDDD0000000000000006"]
        ];
        Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
    }

    /**
     * @param Request $request
     * @param $wshId
     * @return array|string
     */
    public function getDropLocation(Request $request, $wshId, $palletRfid)
    {
        try {
            $params = Input::all();
            if (isset($wshId)) {
                $url = MyHelper::getUrlGetDropLocation($wshId, $palletRfid, $params['loc_rfid']);
                $data = Clients::ConnectWmsData('GET', $url, $params, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $wshId
     * @param $palletRfid
     * @return array|string
     */
    public function DropLocation(Request $request, $wshId, $palletRfid)
    {
        try {
            $params = Input::all();
            if (isset($wshId)) {
                $url = MyHelper::getUrlDropLocation($wshId, $palletRfid);
                $data = Clients::ConnectWmsDataJson('POST', $url, $params, $request);
                return $data;
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param Request $request
     * @param $whsId
     * @param $cusId
     * @param $checkerId
     * @return array
     */

    public function getDataChecker(Request $request, $whsId, $checkerId)
    {
        try {
            if ($whsId && $checkerId) {
                $recData = RecProcessing::getStatusRv($checkerId);
                if ($recData) {
                    $asnDetailProcessing = AsnDetailProcessing::getAsnsDetailProcessingId($recData->asn_detail_processing_id);
                    if ($asnDetailProcessing) {
                        $asnDetailId = $asnDetailProcessing->asn_dtl_id;
                        $asn = AsnProcessing::getAsnsProcessingId($asnDetailProcessing->asn_processing_id);
                        $cusId = $asn->cus_id;
                        self::asnHistory($request, $whsId, $cusId, $asnDetailId, $checkerId);
                        return MyHelper::response(true, trans('messages.success'), 200);
                    } else {
                        return MyHelper::response(false, trans('messages.success'), 200);
                    }

                } else {
                    return MyHelper::response(false, trans('messages.success'), 200);
                }
            }
        } catch (\Exception $e) {

        }
    }


    /**
     * @param $whsId
     * @param $checkerId
     * @return array
     */

    public function stopAsnToOh($whsId, $checkerId)
    {
        try {
            $params = Input::all();
            if ($whsId && $checkerId) {
                $recData = RecProcessing::getStatusRv($checkerId);
                if ($recData && $params['status'] == RecProcessing::STATUS_ON_HOLD) {
                    RecProcessing::updateStatusOh($checkerId, $params['status']);

                    $dataSend = [
                        'action' => "AsnList",
                        'checker' => $checkerId,
                        'error_code' => 100,
                        'asn_dtl_id' => $params['asn_dtl_id'],
                        'message' => trans('messages.sku-is-stop'),
                    ];
                    Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
                    //self::socketTransferDataToFE('AsnList', $dataStatusAsn, $checkerId);
                    return MyHelper::response(true, trans('messages.success'), 200);
                }
                $asnDetail = AsnDetailProcessing::getAsnsDetailId($params['asn_dtl_id']);
                $asnDetailProcessingId = $asnDetail->asn_detail_processing_id;
                if ($asnDetailProcessingId && $params['status'] == RecProcessing::STATUS_RECEIVING) {
                    RecProcessing::updateStatusReceiVing($asnDetailProcessingId, $params['status']);
                    return MyHelper::response(true, trans('messages.success'), 200);
                } else {
                    return MyHelper::response(false, trans('messages.faild'), 200);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, trans('messages.faild'), 200);
        }
    }

}

