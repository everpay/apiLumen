<?php

namespace App\Models;
use App\Models\AbstractModel;
use App\Libraries\MyHelper;


class UserWarehouse extends AbstractModel
{

    protected $table = 'user_warehouse';
    public $timestamps = false;

    public static function getUserWarehouse($userId)
    {
        $data = self::where('user_id', $userId)->first();
        return $data;
    }

    public static function createWareHouseUser($data,$userId)
    {
        $userWareHouse = new UserWarehouse();
        $userWareHouse->user_id = $userId;
        $userWareHouse->warehouse_id = $data['warehouse_id'];
        $userWareHouse->save();
    }
}