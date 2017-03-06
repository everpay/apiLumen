<?php
namespace App\Models;

use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */
abstract class AsnDetailProcessingFactory extends AbstractModel
{


    /**
     * @return mixed
     */
    public static function getAsnsDetailProcessingRv()
    {
        $query = self::where('asn_detail_processing.status', AsnDetailProcessing::STATUS_RECEIVING)
            ->leftJoin('asn_processing',
                'asn_processing.asn_processing_id',
                '=',
                'asn_detail_processing.asn_processing_id'

            )->leftJoin('location',
                'asn_processing.location_id',
                '=',
                'location.location_id'
            )
            ->select('location.*', 'asn_detail_processing.asn_dtl_id')->get();

        return $query;
    }

    public static function getAsnsDetailProcessingId($asnDtlProcessingId)
    {
        $query = self::where([['asn_detail_processing_id', $asnDtlProcessingId],['status', AsnDetailProcessing::STATUS_RECEIVING]])->first();
        return $query;
    }
}