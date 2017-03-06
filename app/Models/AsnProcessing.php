<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class AsnProcessing extends AsnProcessingFactory
{
    protected $table = 'asn_processing';
    protected $primaryKey = 'asn_processing_id';
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
    const STATUS_RECEIVING = 'RV';
    const STATUS_RECEIVED = 'RD';

    public static function statusArr(){
        return  [
            'Receiving' =>  self::STATUS_RECEIVING,
            'New'       => self::STATUS_NEW,
            'Received'  => self::STATUS_RECEIVED
        ];
    }

    
}