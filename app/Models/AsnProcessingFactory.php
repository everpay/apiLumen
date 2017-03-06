<?php
namespace App\Models;

use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */
abstract class AsnProcessingFactory extends AbstractModel
{
    public static function createAsns($data)
    {
        $asnProcessing = new AsnProcessing();
        $asnProcessing['processing_user_id'] = $data['user_id'];
        $asnProcessing['cus_id'] = $data['cus_id'];
        $asnProcessing['ctnr_id'] = $data['ctnr_id'];
        $asnProcessing['asn_id'] = $data['asn_hdr_id'];
        $asnProcessing['status'] = $data['status'];
        $asnProcessing->save();
    }

    public static function getAsnsId($asnId)
    {
        $query = self::where('asn_id', $asnId)->first();
        return $query;
    }

    public static function getAsns()
    {
        $query = self::get();
        return $query;
    }

    public static function getAsnsProcessingId($asnProcessingId)
    {
        $query = self::where('asn_processing_id', $asnProcessingId)->first();
        return $query;
    }

    public static function updateStatus($asnId, $ctnrId, $data)
    {
        $query = self::where([
            ['ctnr_id', $ctnrId],
            ['asn_id', $asnId],
        ]);

        if ($query->count() != 0) {
            $query->update([
                'status' => AsnProcessing::STATUS_RECEIVING
            ]);
        } else {
            $data['status'] = AsnProcessing::STATUS_RECEIVING;
            self::createAsns($data);
        }
        return true;
    }

    public static function getCusId()
    {
        $query = self::where('status', AsnProcessing::STATUS_RECEIVED)->first();
        return $query->cus_id;
    }

    public static function getAsnsStatus()
    {
        $query = self::select('asn_id', 'status')->get();
        return $query;
    }

}