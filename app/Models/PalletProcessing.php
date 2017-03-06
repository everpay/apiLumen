<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class PalletProcessing extends PalletProcessingFactory
{
    protected $table = 'pallet_processing';
    protected $primaryKey = 'pallet_processing_id';
    /**
     * The name of the "created at" column.
     *
     * @var string
     */

    const STATUS_DISCONNECT = 'DI';
    const STATUS_NEW = 'NW';
    const STATUS_PICKING = 'PK';
    const STATUS_DROPPED = 'DD';

    const TYPE_PROCESS_INBOUND = 'IB';
    const TYPE_PROCESS_OUTBOUND = 'OB';




    const CREATED_AT = 'created_date';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_date';

}