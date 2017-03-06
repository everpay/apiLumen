<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class RecProcessing extends RecProcessingFactory
{
    protected $table = 'rec_processing';
    protected $primaryKey = 'rec_processing_id';


    const STATUS_RECEIVING = "RV";
    const STATUS_RECEIVED = "RD";
    const STATUS_ON_HOLD = "OH";

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

    
}