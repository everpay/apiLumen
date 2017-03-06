<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class RobotProcessing extends RobotProcessingFactory
{

    protected $table = 'robot_processing';
    protected $primaryKey = 'robot_processing_id';
    public $timestamps = false;

    const DROPPED = "DD";
    const PICKING = "PD";

    protected $fillable = array('device_id', 'pick_position_id', 'drop_position_id','status');


}
