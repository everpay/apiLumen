<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class CartonProcessing extends CartonProcessingFactory
{
    protected $table = 'cartons_processing';
    protected $primaryKey = 'cartons_processing_id';
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

    const STATUS_RECEIVING = 'RV';
    const STATUS_UNDONE = 'UD';
    const STATUS_RECEIVED = 'RD';
    const STATUS_DAMAGED = 'DD';
}