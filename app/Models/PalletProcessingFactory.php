<?php
namespace App\Models;

use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */
abstract class PalletProcessingFactory extends AbstractModel
{

    public static function createPalletProcessing($userId,$device)
    {
        $palletProcessing = new PalletProcessing();
        $palletProcessing['rfid_reader_id'] = $device->device_id;
        $palletProcessing['pl_rfid'] = $device->rfid;
        $palletProcessing['processor_id'] = $userId;
        $palletProcessing['status'] = PalletProcessing::STATUS_NEW;
        $palletProcessing->save();
    }

    public static function getWavePickByPalletRFID($pallet)
    {
        $query = PalletProcessing::where('pl_rfid', $pallet)
            ->where('pallet_processing.status', PalletProcessing::STATUS_PICKING)
            ->where('wp_processing.status', WpProcessing::STATUS_PICKING)
            ->join('wp_processing', 'wp_processing.processing_user_id', '=', 'pallet_processing.processor_id')
            ->first();
        if (count($query) > 0) {
            return $query->wave_id;
        } else {
            return '';
        }
    }

    public static function getPalletProcessingId($userId)
    {
        $query = self::where('processor_id', $userId)->first();
        return $query;
    }
     public static function updateStatus($processorId,$plRfid,$status)
    {
        $query = self::where([
            ['processor_id', $processorId]
        ]);
         $query->update([
                'status' => $status,
                'pl_rfid' => $plRfid
            ]);
         
        return true;
    }
    public static function updateOnlyStatus($processorId,$status)
    {
        $query = self::where([
            ['processor_id', $processorId]
        ]);
         $query->update([
                'status' => $status
            ]);
         
        return true;
    }
    public static function getStatus($processorId)
    {
         $query = self::where([
            ['processor_id', $processorId]
        ])->first();
         return $query->status;
    }
}