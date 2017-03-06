<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class AsnDetailProcessing extends AsnDetailProcessingFactory
{
    protected $table = 'asn_detail_processing';
    protected $primaryKey = 'asn_detail_processing_id';

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_date';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_date';

    const STATUS_NEW = 'NW';
    const STATUS_RECEIVING = 'RV';
    const STATUS_RECEIVED = 'RD';

    public static function updateStatus($asnDtlId, $data)
    {
        $query = self::where('asn_dtl_id', $asnDtlId);


        if ($query->count() != 0) {
            $query->update([
                'status' => AsnDetailProcessing::STATUS_RECEIVING
            ]);
        } else {
            if ($data['status'] == AsnDetailProcessing::STATUS_RECEIVED) {
                $query->update([
                    'status' => AsnDetailProcessing::STATUS_RECEIVED
                ]);
            } else {
                $data['status'] = AsnDetailProcessing::STATUS_RECEIVING;
                $query = AsnProcessing::where([
                    ['ctnr_id', $data['ctnr_id']],
                    ['asn_id', $data['asn_id']]
                ])->first();
                $asnProcessing = new AsnDetailProcessing();
                $asnProcessing['asn_dtl_id'] = $asnDtlId;
                $asnProcessing['expected_cartons'] = $data['expected_cartons'];
                $asnProcessing['asn_processing_id'] = $query->asn_processing_id;
                $asnProcessing['item_id'] = $data['item_id'];
                $asnProcessing['status'] = $data['status'];

                $asnProcessing->save();
            }
        }
        return $query->first();
    }

    public static function getAsnsDetailId($asnDtlId)
    {

        $query = self::where('asn_dtl_id', $asnDtlId)->first();
        return $query;
    }

}