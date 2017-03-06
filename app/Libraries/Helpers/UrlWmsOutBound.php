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
 * Class UrlWmsOutBound
 * @package App\Libraries
 */
class UrlWmsOutBound{

    /**
     * @param $whsId
     * @return string
     */
    public static function getEmptyLocationPA($whsId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/location/put-away/get-empty-location';
        return $url;
    }
    public static function getOrderList($whsId,$limit,$page)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/order?limit='.$limit.'&page='.$page;
        return $url;
    }
      public static function getWavePickDetail($whsId,$wvDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/whs/'.$whsId.'/wave/sku/'.$wvDtlId;
        return $url;
    }   

     public static function getOrderId($whsId,$orderId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/order/'.$orderId;
        return $url;
    }   

    public static function getDetailSku($whsId,$orderDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/order/sku/'.$orderDtlId;
        return $url;
    }
    public static function assignFullCarton($whsId,$orderDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/order/sku/'.$orderDtlId.'/carton';
        return $url;
    }
    public static function assignFullCartons($whsId,$orderDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/order/sku/'.$orderDtlId.'/cartons';
        return $url;
    }
    public static function assignFullCartonRfid($whsId,$ctnRfid)
    {
        $url =  WMS_URL_OUTBOUND_API.'/'.$whsId.'/order/sku/'.$ctnRfid.'/carton';
        return $url;
    }
    public static function pickPiece($whsId,$orderDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/order/sku/'.$orderDtlId.'/pieces';
        return $url;
    }
    public static function getOrderShipping($whsId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/order-shipping';
        return $url;
    }
    public static function locationPutAwayList($whsId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/location-put-away-list';
        return $url;
    }
    public static function locationPickingList($whsId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/location-picking-list';
        return $url;
    }
    public static function getUrlWarePick($whsId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/wave';
        return $url;
    }

    public static function getUrlUpdateWavePick($whsId, $wavePickId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'.$whsId.'/wave/'.$wavePickId.'/update';
        return $url;
    }

    public static function getUrlMovePalletOnRack($whsId, $palletID)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/whs/'.$whsId.'/pallet/'.$palletID.'/update-pallet-movement';
        return $url;
    }

    public static function getUrlPutBackPalletOnRack($whsId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/whs/'.$whsId.'/update-pallet-put-back';
        return $url;
    }
    /**
     * @param $whsId
     * @param $wvDtlId
     * @return string
     */
    public static function getUrlWarePickSkuLocation($whsId,$wvDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/whs/'. $whsId.'/wave/sku/'.$wvDtlId;
        return $url;
    }

    public static function getUrlWavePickList($whsId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'. $whsId.'/waves';
        return $url;
    }
    /**
     * @param $whsId
     * @param $wvDtlId
     * @return string
     */
    public static function getUrlWavePickSkuPickPallet($whsId,$wvDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'. $whsId.'/wave/sku/'.$wvDtlId.'/pallet';
        return $url;
    }

    /**
     * @param $whsId
     * @param $rfid
     * @return string
     */

    public static function getUrlScanCarton($whsId,$rfid)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'. $whsId.'/carton/rfid/'.$rfid;
        return $url;
    }

    /**
     * @param $whsId
     * @param $odrId
     * @return string
     */
    public static function getUrlAssignCarton($whsId,$odrId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'. $whsId.'/order/'.$odrId.'/cartons';
        return $url;
    }
    public static function getUrlPickFullCarton($whsId,$wvDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'. $whsId.'/wave/sku/'.$wvDtlId.'/carton';
        return $url;
    }

    public static function getUrlAssignPackedCartonsToPallet($whsId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'. $whsId.'/pallet/assign-cartons';
        return $url;
    }

    public static function getUrlPutDropPalletOnShippingLane($whsId)
    {
        $url =  WMS_URL_OUTBOUND_API2.'/'. $whsId.'/pallet/shipping';
        return $url;
    }

    public static function getUrlGetActiveLocation($whsId,$wvDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2 .'/whs/'. $whsId.'/wave/'.$wvDtlId.'/active-location';
        return $url;
    }

    public static function getUrlGetMoreSuggestLocation($whsId,$wvDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2 .'/whs/'. $whsId.'/wave/'.$wvDtlId.'/more-location';
        return $url;
    }

    public static function getUrlGetCartonsOfaPallet($whsId,$pallet)
    {
        $url =  WMS_URL_OUTBOUND_API2 .'/whs/'. $whsId.'/pallet/'.$pallet.'/cartons';
        return $url;
    }

    public static function getUrlGetNextSku($wshId,$wvId,$wvDtlId)
    {
        $url =  WMS_URL_OUTBOUND_API2 .'/whs/'. $wshId.'/wave/'.$wvId.'/next-sku/'.$wvDtlId;
        return $url;
    }

}