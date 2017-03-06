<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class WpProcessing extends WpProcessingFactory
{
    protected $table = 'wp_processing';
    protected $primaryKey = 'wp_processing_id';
    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_date';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_date';

    const STATUS_NEW = 'NW';
    const STATUS_PICKING = 'PK';
    const STATUS_PICKED = 'PD';

    public static function statusArr(){
        return  [
            'picking' =>  self::STATUS_PICKING,
            'New'       => self::STATUS_NEW,
            'picked'  => self::STATUS_PICKED
        ];
    }
    
}