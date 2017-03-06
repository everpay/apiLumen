<?php
namespace App\Models;
use App\Models\AbstractModel;
use App\Models\Location;
use App\Libraries\MyHelper;
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 11:41 AM
 */
class PositionFactory extends  AbstractModel{



    public static function getPositionId($data)
    {
        if ($data) {
            $Position = self::where('ref_hex',$data)->first();
            return $Position->position_id;
        }
    }

    public static function getPosition($data)
    {
        if ($data) {
            $Position = self::where('ref_hex',$data)->first();
            return $Position;
        }
    }

    public static function getPositionDeviceId($data)
    {
        if ($data) {
            $Position = self::where('device_id',$data)->first();
            return $Position;
        }
    }

    public static function getRefHex($code)
    {
        if ($code) {
            $Position = self::where('code',$code)->first();
            return $Position;
        }
    }

    public static function getRefHexPositionId($id)
    {
        if ($id) {
            $Position = self::where('position_id',$id)->first();
            return $Position;
        }
    }



    public static function updateStatus($refHex,$status,$skuNum )
    {
            $query = self::where('ref_hex',$refHex);
            if($status == Position::STATUS_RESERVED)
            {
                $query->update([
                'last_status' =>$status,
                'sku_num' => $skuNum
                ]);
            }
            else
            {
                $query->update([
                'last_status' =>$status
                ]);
            }
            return true;

    }



    public static function updateStatusPositionId($positionId,$status,$sku=null)
    {
        $query = self::where('position_id',$positionId);
        $query->update([
            'last_status' =>$status,
            'sku_num'=>$sku
        ]);
        return true;

    }

    public static function updateStatusPositionIdEt($positionId,$status)
    {
        $query = self::where('position_id',$positionId);
        $query->update([
            'last_status' =>$status,
        ]);
        return true;

    }

    public static function updateStatusCode($code,$status)
    {
        $query = self::where('code',$code);
        $query->update([
            'last_status' =>$status,
        ]);
        return true;

    }

    public static function updateStatusRfid($rfid,$status)
    {
        $query = self::where('rfid',$rfid);
        $query->update([
            'last_status' =>$status,
        ]);
        return true;

    }


    public static function getStatus()
    {
        $query = self::where('last_status',4)->select('last_status','position_id','ref_hex');
        return $query->first();
    }

    public static function getPositionFull()
    {
        $query = self::where('last_status',Position::STATUS_FULL)->select('position_id','ref_hex');
        return $query->first();
    }


    public static function getLocationReceiving($type,$idUser,$locationId){
        $query = self::leftJoin('location',
            'positions.location_id',
            '=','location.location_id'
        )
            ->leftJoin('location_type',
                'location.location_type_id',
                '=',
                'location_type.location_type_id'
            )
            ->leftJoin('asn_processing',
                'location.location_id',
                '=',
                'asn_processing.location_id'
            )
            ->select('positions.*','location.name')->distinct()
            ->where([['location_type.code', $type],['asn_processing.processing_user_id',$idUser]])
        ;
        if (isset($locationId['location_id'])) {
            $query->where('location.location_id', $locationId);
        }
        return $query->get();
    }


}