<?php
namespace App\Models;

use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */
abstract class CartonProcessingFactory extends AbstractModel
{
    public static function addCartonProcessing($params, $status)
    {
        $asnDetail = AsnDetailProcessing::where('asn_dtl_id', $params['asn_dtl_id'])
            ->where('item_id', $params['item_id'])
            ->first();
        $dataInsert = [];
        foreach ($params['ctns_rfid'] as $valCode) {
            $check = CartonProcessing::where('rfid', $valCode)->first();
            if (count($check) == 0) {
                $dataInsert[] = [
                    'asn_detail_processing_id' => $asnDetail->asn_detail_processing_id,
                    'rfid' => $valCode,
                    'status' => $status
                ];
            }
        }
        CartonProcessing::insert($dataInsert);
    }

    public static function getTotalCarton($asnDetailProcessingId)
    {
        $totalCarton = CartonProcessing::where('asn_detail_processing_id', $asnDetailProcessingId)
            ->where('status', CartonProcessing::STATUS_RECEIVED)
            ->get();

        return count($totalCarton);
    }

    public static function getCartonProcessingByAsnDetail($asnDetailId)
    {
        $asnDetail = self::leftjoin('asn_detail_processing',
            'cartons_processing.asn_detail_processing_id',
            '=',
            'asn_detail_processing.asn_detail_processing_id'
        )->where('asn_detail_processing.asn_dtl_id', $asnDetailId)
            ->select('cartons_processing.rfid', 'cartons_processing.status')->get();
        return $asnDetail;
    }

    public static function updateStatusRfid($rfid,$status)
    {
        $query = self::where('rfid',$rfid);
        $query->update([
            'status' =>$status,
        ]);
        return true;
    }

    public static function deleteRfid($rfid)
    {
        $query = self::where('rfid',$rfid);
        $query->delete();
        return true;

    }

    public static function getCartonProcessingByAsnDetailProcessing($recReceivingId)
    {
        $asnDetailProcessing = self::leftjoin('asn_detail_processing',
            'cartons_processing.asn_detail_processing_id',
            '=',
            'asn_detail_processing.asn_detail_processing_id'
        )->leftjoin('rec_processing',
            'asn_detail_processing.asn_detail_processing_id',
            '=',
            'rec_processing.asn_detail_processing_id'
        )->where('rec_processing.asn_detail_processing_id', $recReceivingId)
            ->select('cartons_processing.rfid', 'cartons_processing.status')->get();
        return $asnDetailProcessing;
    }


    public static function getCountCartonProcessingByAsnDetail($asnDetailId)
    {
        $asnDetail = self::leftjoin('asn_detail_processing',
            'cartons_processing.asn_detail_processing_id',
            '=',
            'asn_detail_processing.asn_detail_processing_id'
        )->where('asn_detail_processing.asn_detail_processing_id', $asnDetailId)
            ->where('cartons_processing.status', CartonProcessing::STATUS_RECEIVED)
            ->count();
        return $asnDetail;
    }

    public static function getExpectedCartonProcessingByAsnDetail($asnDetailId)
    {
        $asnDetail = self::leftjoin('asn_detail_processing',
            'cartons_processing.asn_detail_processing_id',
            '=',
            'asn_detail_processing.asn_detail_processing_id'
        )->where('asn_detail_processing.asn_detail_processing_id', $asnDetailId)
            ->select('asn_detail_processing.expected_cartons')
            ->distinct()
            ->first();
        return $asnDetail;
    }

}