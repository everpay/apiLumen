<?php
namespace App\Models;

use App\Libraries\MyHelper;
use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 4:23 PM
 */
abstract class LocationFactory extends AbstractModel
{
    public static function getIdLocation($code)
    {
        $query = self::where('code', $code)->first();
        return $query;
    }


    public static function getListLocation(array $option)
    {
        $typeLoc = MyHelper::get($option, 'loc_type');
        $query = self::leftJoin('location_type',
            'location.location_type_id',
            '=',
            'location_type.location_type_id'
        )
            ->leftJoin('positions',
                'positions.location_id',
                '=', 'location.location_id'
            )
            ->select('location.code', 'location.name', 'location.location_id')->distinct()
            ->where('location_type.code', '=', $typeLoc);
        if ($option && $typeLoc) {
            $locRec = MyHelper::get($option, 'loc_rec');
            $locPaw = MyHelper::get($option, 'loc_paw');
            if ($locRec) {
                $query->where('location.code', '=', $locRec)->addSelect('positions.*', 'location.code as abc');
            }
            if ($locPaw) {

                $query->where('location.code', '=', $locPaw)->addSelect('positions.*', 'location.code as abc');
            }
        };
        return $query->get();
    }

    public static function getListLocationRv($codeReceiving, $statusReceiving)
    {
        $query = self::leftJoin('location_type',
            'location.location_type_id',
            '=',
            'location_type.location_type_id'
        )
            ->leftJoin('asn_processing',
                'location.location_id',
                '=', 'asn_processing.location_id'
            )

            ->select('location.code', 'location.name', 'location.location_id')->distinct()
            ->where([['location_type.code', $codeReceiving],['asn_processing.status','=', $statusReceiving]]);
        return $query->get();
    }


}