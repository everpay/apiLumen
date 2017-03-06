<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 11:00 AM
 */
class DeviceType extends DeviceTypeFactory
{
    protected $table = 'device_type';
    protected $primaryKey = 'device_type_id';

    const ROBOT = "ROBOT";
    const RFID_CR = "RFID_CR";
    const RFID_PR = "RFID_PR";
    const RFID_GW = "RFID_GW";


    public function device()
    {
        return $this->hasMany('Device', 'device_id');
    }
}