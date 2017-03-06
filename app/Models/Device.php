<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class Device extends DeviceFactory
{
    protected $table = 'device';
    protected $primaryKey = 'device_id';

    const STATUS_READY = 1;
    const STATUS_CHARGING = 2;
    const STATUS_WORKING = 3;
    const STATUS_PICKING = 4;
    const STATUS_DROPPED = 5;

    const STATUS_ROBOT_READY = "0x0001";
    const STATUS_ROBOT_CHARGING = "0x0002";
    const STATUS_ROBOT_WORKING = "0x0003";
    const STATUS_ROBOT_PICKING = "0x0004";
    const STATUS_ROBOT_DROPPED = "0x0005";

    const ACTIVE = "AT";
    const INACTIVE = "IA";
    const RECEIVING = "RV";

    const TYPE_ROBOT = "ROBOT_01";
    const TYPE_RFID_SG = "RFID_SG";
    const TYPE_RFID_PG = "RFID_PG";

    /**

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'last_updated_time';
    
}