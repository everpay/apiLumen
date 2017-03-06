<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class RfidTags extends RfidTagsFactory
{
    protected $table = 'rfid_tags';
    protected $primaryKey = 'rfid_tag_id';
    const RACK = "RA";
    const PALLET = "PL";
    const FORKLIFT = "FL";
    const ROBOT = "RB";

    /**

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'last_updated_time';
    
}