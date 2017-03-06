<?php
namespace App\Models;

use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */

abstract class WpDetailProcessingFactory extends AbstractModel
{

    /**
     * @param $data
     */
    public static function createWpDetail($data)
    {

        $wpDetailProcessing = new WpDetailProcessing();
        $wpDetailProcessing['wp_processing_id'] = $data['wp_processing_id'];
        $wpDetailProcessing['wave_detail_id'] = $data['wave_detail_id'];
        $wpDetailProcessing['item_id'] = $data['item_id'];
        $wpDetailProcessing['status'] = WpProcessing::STATUS_PICKING;
        $wpDetailProcessing->save();
    }

    /**
     * @param $wpDtlId
     * @return mixed
     */
    public static function getWpDetailId($wpDtlId){
        $query = self::where('wave_detail_id' , $wpDtlId)->first();
        return $query;
    }
}