<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/19/2016
 * Time: 5:34 PM
 */

namespace App\Libraries;
use GuzzleHttp\Client;

/**
 * Class MyHelper
 */
class MyHelper{

    /**
     * @param $status
     * @param null $data
     * @return array
     */
    public static function response($status, $data = NULL, $statusCode = 200)
    {
        $key = $status ? "data" : "message";
        $dataReturn = [
            "status" => $status,
            $key => $data,
        ];
        // Set our response code
        return response()->json($dataReturn, $statusCode);
    }

    /**
     * @param $status
     * @param null $data
     * @return array
     */
    public static function responseMs($status, $data = NULL, $statusCode = 200)
    {
        $key = "message";
        $dataReturn = [
            "status" => $status,
            $key => $data,
        ];
        // Set our response code
        return response()->json($dataReturn, $statusCode);
    }


    /**
     * Get value for the object or array with default value
     *
     * @author Binh pham
     *
     * @param Object|array $object Object to get value
     * @param string $value key value
     * @param null $defaultValue default value if object's key not exist
     * @param bool $isObject determine the object type is array or object
     *
     * @return mixed value of key in the object
     */
    public static function get($object, $value, $defaultValue = NULL) {
        if (is_array($value)) {
            $tmpValue = $object;
            for ($i = 0, $len = count($value); $i < $len; $i++) {
                $tmpValue = self::get($tmpValue, $value[$i], $defaultValue);
            }
            return $tmpValue;
        } else {
            if (!isset($object)) {
                return $defaultValue;
            } elseif (is_array($object)) {
                return isset($object[$value]) ?
                    $object[$value] : $defaultValue;
            } elseif (is_object($object)) {
                return isset($object->$value) ?
                    $object->$value : $defaultValue;
            }
        }
    }


    /**
     * @param array $list
     * @return array
     */
    public static function listRespond(array $list)
    {
        return [
            'rows' => array_get($list, 'data', []),
            'page' => array_get($list, 'current_page'),
            'length' => array_get($list, 'per_page'),
            'total_record' => array_get($list, 'total'),
            'total_page' => array_get($list, 'last_page'),
        ];
    }


    /**
     * @param $json
     * @param string $type
     * @return array
     */
    public static function responseToArray($json)
    {
        return \json_decode($json, true);
    }

    /**
     * @param $whsId
     * @return string
     */
    public static function getUrlAsn($whsId,$cusId,$ctnrId,$limit,$page)
    {
        
            $url =  WMS_URL_API.'/whs/'.$whsId.'/asns?ctnr_id='.$ctnrId.'&cus_id='.$cusId.'&limit='.$limit.'&page='.$page;

        return $url;
    }

    public static function getUrlAsnListDetail($whsId,$cusId,$ctnrId,$limit,$page)
    {
        if($cusId != "" || $ctnrId != "" || $limit != "" || $page != ""){
            $url =  WMS_URL_API.'/whs/'.$whsId.'/asnsV1?ctnr_id='.$ctnrId.'&cus_id='.$cusId.'&limit='.$limit.'&page='.$page;
            return $url;
        }
        $url =  WMS_URL_API.'/whs/'.$whsId.'/asnsV1';
        return $url;
    }

    public static function getUrlEmptyLocationEnvironsRack($whsId,$cusId)
    {
        
            $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/location/rack/get-empty-location-environs';

        return $url;
    }
    public static function getUrlAsnHistory($whsId,$cusId,$asnDtlId)
        {
                $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/asn-history/'.$asnDtlId;

            return $url;
        }
    public static function getUrlCus($whsId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus-list';

        return $url;
    }

    public static function getUrlContainerList($whsId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/containers';

        return $url;
    }


    public static function getUrlDetailAsn($wshId,$cusId,$asnId,$ctnrId)
    {
        $url =  WMS_URL_API.'/whs/'.$wshId.'/cus/'.$cusId.'/asns/'.$asnId.'/containers/'.$ctnrId;
        return $url;
    }

    public static function getUrlUpdateCarton($wshId,$cusId){
        $url =  WMS_URL_API.'/v1/whs/'.$wshId.'/cus/'.$cusId.'/add-virtual-carton';
        return $url;
    }

    public static function getUrlUpdateMultiCarton($wshId,$cusId){
        $url =  WMS_URL_API.'/whs/'.$wshId.'/cus/'.$cusId.'/add-virtual-cartons';
        return $url;
    }

    public static function getUrlGetReceivingGates($wshId){
        $url =  WMS_URL_API.'/whs/'.$wshId.'/get-receiving-gates';
        return $url;
    }

    public static function getUrlListVirtualCarton($whsId,$cusId,$params)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/asns/'.$params['asn_hdr_id'].'/containers/'.$params['ctnr_id'].'/carton'.'/list-virtual-carton';

        return $url;
    }

    public static function getUrlListVirtualCartonByAsnDetail($whsId,$cusId,$params)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/asn/status/'.$params['asn_dtl_id'];

        return $url;
    }

    public static function getUrlPallet($whsId)
    {
        $url =  WMS_URL_API.'/whs/'.$whsId.'/scan-pallet';

        return $url;
    }

    public static function getUrlCheckGoodReceipt($asnID)
    {
        $url =  WMS_URL_API.'/check-good-receipt/'.$asnID;

        return $url;
    }


    public static function getUrlPickPutAway($whsId,$cusId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/goods-receipts/put-away/pick-pallet';

        return $url;
    }


    public static function getUrlSuggestLocationRobot($whsId)
    {
        $url =  WMS_URL_API.'/whs/'.$whsId.'/location/put-away/get-empty-location';
        return $url;
    }

    public static function getUrlSuggetPallet($whsId,$cusId,$palletRfid)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/location/put-away/suggest-pallet?pallet-rfid='.$palletRfid;

        return $url;
    }

    public static function getUrlPutPallet($whsId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/location/rack/put-pallet';

        return $url;
    }
    
    public static function getUrlAsnDetail($whsId,$cusId,$asnDtlId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/asn-detail/'.$asnDtlId.'/complete-sku';

        return $url;
    }

    public static function getUrlListAllLocation($whsId,$cusId,$locType)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/location/list-all-location?loc_type='.$locType;

        return $url;
    }

    public static function getUrlSetDamageCarton($whsId,$cusId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/carton/set-damage-carton';

        return $url;
    }

    public static function getUrlDeleteVirtualCarton($whsId,$cusId,$vtlCtnId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/carton/delete-virtual-carton';

        return $url;
    }

    /**
     * @param $whsId
     * @param $cusId
     * @param $CtnId
     * @return string
     */
    public static function getUrlListVirtualCartons($whsId, $cusId,$asns, $CtnId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/asns/'.$asns.'/containers/'.$CtnId.'/carton/list-virtual-carton';

        return $url;
    }

    public static function getUrlLocationPallet($whsId, $cusId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/location/rack/get-empty-locations';

        return $url;
    }
    public static function getUrlLocationRack($whsId, $cusId, $locRfid)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/location/rack/get-empty-locations?loc_rfid='.$locRfid;

        return $url;
    }

    public static function getUrlCompleteGoodReceipt($whsId, $cusId,$asns, $CtnId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'asns/'.$asns.'/containers/'.$CtnId.'/goods-receipts/complete-good-receipt';

        return $url;
    }

    public static function getUrlCreateGoodReceipt($whsId, $cusId,$asns, $CtnId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'asns/'.$asns.'/containers/'.$CtnId.'/goods-receipts/create-good-receipt';

        return $url;
    }

    public static function getUrlShowAsnsDetail($whsId, $cusId,$asnsId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/asn/status/'.$asnsId;

        return $url;
    }

    public static function getUrlPutAwayPutPallet($whsId, $cusId)
    {

        $url =  WMS_URL_API.'/whs/'.$whsId.'/cus/'.$cusId.'/location/put-away/put-pallet';

        return $url;
    }

    public static function getUrlEmptyLocations($whsId, $rfid,$locRfid)
    {
        if($locRfid!=false)
        {
            $url =  WMS_URL_API.'/whs/'.$whsId.'/pallet/'.$rfid.'/location/rack/get-empty-locations?loc_rfid='.$locRfid;
        }
        else
        {
            $url =  WMS_URL_API.'/whs/'.$whsId.'/pallet/'.$rfid.'/location/rack/get-empty-locations';
        }
        return $url;
    }

    public static function getUrlUserListWms($whsId)
    {
        $url =  WMS_URL_API.'/'.$whsId.'/users';
        return $url;
    }

    public static function getUrlDropLocation($whsId,$palletRfid)
    {
        $url =  WMS_URL_API.'/whs/'.$whsId.'/pallet/'.$palletRfid.'/drop-location';
        return $url;
    }

    public static function getUrlGetDropLocation($whsId,$palletRfid,$locRfid)
    {
        $url =  WMS_URL_API.'/whs/'.$whsId.'/pallet/'.$palletRfid.'/drop-location?loc_rfid='.$locRfid;
        return $url;
    }


}