<?php
namespace App\Models;

use App\Models\AbstractModel;
use DB;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */
abstract class RecProcessingFactory extends AbstractModel
{

    public static function createRec($asnDetailProcessingId, $data)
    {
        $recProcessing = new RecProcessing();
        $recProcessing['asn_detail_processing_id'] = $asnDetailProcessingId;
        $recProcessing['receiver_id'] = $data['user_id'];
        $recProcessing['checker_id'] = $data['checker_id'];
        $recProcessing['rfid_reader_1'] = $data['rfid_reader_1'];
        $recProcessing['rfid_reader_2'] = $data['rfid_reader_2'];
        $recProcessing['status'] = $data['status'];
        $recProcessing->save();
    }

    /**
     * @param $asnDetailProcessingId
     * @param $data
     * @param bool $status
     */
    public static function updateStatus($asnDetailProcessingId, $data)
    {
        $queryAsn = self::where([
            ['status', RecProcessing::STATUS_RECEIVING]
            , ['rfid_reader_1', $data['rfid_reader_1']]
            , ['rfid_reader_2', $data['rfid_reader_2']
                , ['receiver_id', $data['user_id']]]
        ]);
        $query = self::where([['asn_detail_processing_id', $asnDetailProcessingId], ['receiver_id', $data['user_id']]]);

        if ($queryAsn->count() > 0 && $query->count() > 0) {
            $queryAsn->update([
                'status' => RecProcessing::STATUS_ON_HOLD
            ]);
            $query->update([
                'status' => RecProcessing::STATUS_RECEIVING,
                'checker_id' => $data['checker_id']
            ]);
        } elseif ($queryAsn->count() > 0 && $query->count() == 0) {
            $data['status'] = RecProcessing::STATUS_RECEIVING;
            $queryAsn->update([
                'status' => RecProcessing::STATUS_ON_HOLD
            ]);
            self::createRec($asnDetailProcessingId, $data);
        } elseif ($queryAsn->count() == 0 && $query->count() > 0) {
            $query->update([
                'status' => RecProcessing::STATUS_RECEIVING,
                'checker_id' => $data['checker_id']
            ]);
        } else {
            $data['status'] = RecProcessing::STATUS_RECEIVING;
            self::createRec($asnDetailProcessingId, $data);
        }


    }

    /**
     * @param $asnId
     * @return array
     */
    public static function getRecStatus($asnId)
    {
        $query = self::leftJoin('asn_detail_processing',
            'rec_processing.asn_detail_processing_id',
            '=',
            'asn_detail_processing.asn_detail_processing_id'
        )->leftJoin('asn_processing',
            'asn_detail_processing.asn_processing_id',
            '=',
            'asn_processing.asn_processing_id'
        )->distinct()->select('rec_processing.status', 'asn_detail_processing.asn_dtl_id', 'asn_detail_processing.asn_detail_processing_id')
            ->where('asn_processing.asn_id', $asnId)
            ->get();
        if ($query) {
            return $query->toArray();
        } else {
            return [];
        }


    }

    public static function getCheckerId($asnDetailProcessing)
    {
        $query = self::where('asn_detail_processing_id', $asnDetailProcessing)->first();
        return $query->checker_id;
    }

    public static function getStatusRv($checkerId)
    {
        $query = self::where([['status', RecProcessing::STATUS_RECEIVING], ['checker_id', $checkerId]])->first();
        return $query;
    }

    public static function getDeviceStatusRv($params)
    {
        $listDevice = self::where([['status', RecProcessing::STATUS_RECEIVING],
            ['rfid_reader_1', $params['rfid_reader_1']], ['rfid_reader_2', $params['rfid_reader_2']]])
            ->select('rfid_reader_1', 'rfid_reader_2', 'checker_id')->get();
        return $listDevice;
    }

    public static function getStatusRvAsn($asnDetailProcessingId)
    {
        $query = self::where([['asn_detail_processing_id', $asnDetailProcessingId],
            ['status', RecProcessing::STATUS_RECEIVING]])->get();
        return $query;
    }


    public static function getRecUserId($data)
    {
        $query = self::where([['receiver_id', $data['user_id']],
            ['rfid_reader_1', $data['rfid_reader_1']],
            ['rfid_reader_2', $data['rfid_reader_2']]])->first();
        return $query;
    }

    public static function getRecStatusOnHold()
    {
        $query = self::leftJoin('asn_detail_processing',
            'rec_processing.asn_detail_processing_id',
            '=',
            'asn_detail_processing.asn_detail_processing_id'
        )
            ->Where('rec_processing.status', RecProcessing::STATUS_ON_HOLD)
            ->select('asn_detail_processing.expected_cartons', 'asn_detail_processing.asn_dtl_id')->get();
        return $query;

    }

    public static function updateStatusRd($asnDetailProcessingId)
    {
        self::where('rec_processing.asn_detail_processing_id', $asnDetailProcessingId)
            ->update([
                'status' => RecProcessing::STATUS_RECEIVED,
            ]);
    }

    public static function updateStatusOh($checkerId, $status)
    {
        $query = self::where('checker_id', $checkerId)
            ->update([
                'status' => $status
            ]);
        return $query;
    }

    public static function updateStatusReceiVing($asnDetailProcessingId, $status)
    {
        $query = self::where('asn_detail_processing_id', $asnDetailProcessingId)
            ->update([
                'status' => $status
            ]);
        return $query;
    }
}