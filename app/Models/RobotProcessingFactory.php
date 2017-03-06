<?php
namespace App\Models;

use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */

abstract class RobotProcessingFactory extends AbstractModel
{

    public static function getRobotProcessing($id)
    {
        $query = self::where('device_id',$id);

        return $query->first();
    }

    public static function UpdateMac($id,$status)
    {
        $query = self::where('device_id',$id);
        $query->update([
            'status' => $status
        ]);
        return true;
    }

    public static function UpdatePositionRobot($position)
    {

        $query = self::where('device_id',1);
        $query->update([
            'pick_position_id' => $position,
        ]);

        return true;
    }



    public static function updatePositionStatus($deviceId,$status)
    {
        $query = self::where('device_id',$deviceId);
        $query->update([
            'status' => $status,
        ]);
        return true;
    }

    public static function UpdatePositionPicking($pickingStatus,$positionId)
    {
        $query = self::where('device_id',1);
        $query->update([
            'drop_position_id'=>$positionId,
            'status' => $pickingStatus,
        ]);
        return true;
    }

    public static function updateStatus($status)
    {
        $query = self::where('device_id',1);
        $query->update([
            'status' => $status,
        ]);
        return true;
    }


    public static function UpdateOrInsert($deviceId,$PickPositionId,$dropPositionId,$asnId)
    {
        $query = self::where('device_id',$deviceId)->first();
        if(!$query){
            $robotProcessing = new RobotProcessing();
            $robotProcessing->device_id = $deviceId;
            $robotProcessing->pick_position_id = $PickPositionId;
            $robotProcessing->drop_position_id = $dropPositionId;
            $robotProcessing->asn_processing_id = $asnId;
            $robotProcessing->save();
        }else{
            $queryUpdate = self::where('device_id',$deviceId);
            $queryUpdate->update([
                'pick_position_id' => $PickPositionId,
                'drop_position_id' => $dropPositionId,
                'asn_processing_id' => $asnId,
            ]);
        }
    }
}