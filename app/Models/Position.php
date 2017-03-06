<?php

namespace App\Models;

use App\Models\Device;
use App\Models\PositionFactory;
 /**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 11:41 AM
 */
class Position extends PositionFactory{
    protected $table = 'positions';
    protected $primaryKey = 'position_id';

    const STATUS_EMPTY = "ET";
    const STATUS_RESERVED = "RS";
    const STATUS_PICKING = "PK";
    const STATUS_FULL = "FL";

    public static $arrayStatus = [
          "Empty"    => self::STATUS_EMPTY,
          "Reserved" => self::STATUS_RESERVED,
          "Picking"  => self::STATUS_PICKING,
          "Full"     => self::STATUS_FULL
    ];
    /**
     * The name of the "last updated time" column.
     *
     * @var string
     */
    const UPDATED_AT = 'last_updated_time';


    public function device()
    {
        return $this->hasMany('Device', 'device_id');
    }
}
