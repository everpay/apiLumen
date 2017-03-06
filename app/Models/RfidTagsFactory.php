<?php
namespace App\Models;

use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */

abstract class RfidTagsFactory extends AbstractModel
{

    public static function getPatternCode($type = RfidTags::PALLET)
    {
        $query = self::where('enable', '1')
            ->where('type', $type)
            ->first();
        if (count($query) > 0) {
            return $query->code;
        } else {
            return '';
        }
    }

    public static function filterDataScanGateWay($dataRecSocket, $type = RfidTags::PALLET)
    {
        $palletPattern = self::getPatternCode($type);
        if ($type != RfidTags::PALLET) {
            $palletPatternFilter = self::getPatternCode(RfidTags::PALLET);
        } else {
            $palletPatternFilter = $palletPattern;
        }
        $dataRep = [];
        $dataRep['pallet']['ctn-rfid'] = [];
        $dataRep['pallet']['pallet-rfid'] = '';
        foreach ($dataRecSocket as $code) {
            if (strpos($code, $palletPattern, 0) !== false) {
                $dataRep['pallet']['pallet-rfid'] = $code;
            } else {
                if (strpos($code, $palletPatternFilter, 0) === false) {
                    $dataRep['pallet']['ctn-rfid'][] = $code;
                }
            }
        }

        return $dataRep;
    }
}