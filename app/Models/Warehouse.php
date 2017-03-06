<?php

namespace App\Models;

use App\Libraries\MyHelper;


class Warehouse extends AbstractModel
{
    protected $table = 'warehouse';
    const ACTIVE = "AT";
    const INACTIVE = "IA";
    public static function getWarehouse($warehouseId)
    {
        $data = self::where('warehouse_id', $warehouseId)->first();
        return $data;
    }

    public static function getWarehouseId()
    {
        $data = self::where('status',self::ACTIVE)->first();
        return $data->warehouse_id;
    }
}