<?php
namespace App\Models;

use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */

abstract class DeviceFactory extends AbstractModel
{

    public function getDeviceRfid(){

    }


    public static function getDeviceStatus($deviceName){
        $query = self::where('mac_address',$deviceName)->first();
        $status = $query->last_status;
        return $status;

    }

    public static function getPositionId($deviceName){
        $query = self::where('mac_address',$deviceName)->first();
        $status = $query->last_position_id;
        return $status;

    }

    public static function getDeviceId($deviceName){
        $query = self::where('mac_address',$deviceName)->first();
        return $query;

    }

    public static function getDeviceIdFromUuid($uuid){
        $query = self::where('attached_tablet_uuid',$uuid)->first();
        return $query;

    }

    public static function getMacAddress($type,$status){
        $query = self::where('code',$type)
            ->Where('last_status',$status)->first();
        return $query->mac_address;

    }

    public static function updatePositionDevice($deviceName,$positionId)
    {
        $query = self::where('mac_address',$deviceName);
        $query->update([
            'last_position_id' =>$positionId
        ]);
        return $query;
    }
    public static function updateStatusDevice($nameDevice,$statusDevice){
        $query = self::where('mac_address',$nameDevice)->update(['last_status'=>$statusDevice]);
        return $query;
    }

    public static function getDetailDevice($code)
    {
        $detailDevice = self::where('code', $code)->first();
        if($detailDevice){
            return $detailDevice->toArray();
        }else{
            return [];
        }
    }

    public static function getDetailDeviceById($idDevice)
    {
        $detailDevice = self::where('device_id', $idDevice)->first();
        if($detailDevice){
            return $detailDevice->toArray();
        }else{
            return [];
        }
    }

    public static function getListDevice()
    {
        $listDevice = self::leftJoin(
            'device_type',
            'device.device_type_id','=','device_type.device_type_id'
        )->select('device.name','device.device_id')
          ->where([['device_type.code', DeviceType::RFID_CR],['device.last_status',Device::ACTIVE]])->get();
        if($listDevice){
            return $listDevice->toArray();
        }else{
            return [];
        }
    }

    public static function updateStatusDeviceRv($deviceId){
        $query = self::where('device_id',$deviceId)->update(['last_status'=>Device::RECEIVING]);
        return $query;
    }
}