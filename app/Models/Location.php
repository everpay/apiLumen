<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 4:23 PM
 */
namespace App\Models;


class Location extends LocationFactory
{
    protected $table = 'location';
    protected $primaryKey = 'location_id';
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

    const REC = "REC";
    const PAW = "PAW";

    public function position()
    {
        return $this->hasMany('position', 'position_id');
    }
}