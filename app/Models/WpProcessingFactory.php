<?php
namespace App\Models;

use App\Models\AbstractModel;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */

abstract class WpProcessingFactory extends AbstractModel
{

    /**
     * @param $data
     */
    public static function createWp($data)
    {
        $wpProcessing = new WpProcessing();
        $wpProcessing['processing_user_id'] = $data['user_id'];
        $wpProcessing['wave_id'] = $data['wave_id'];
        $wpProcessing['status'] = $data['status'];
        $wpProcessing->save();
    }

    public static function getWpId($wpId){
        $query = self::where('wave_id' , $wpId)->first();
        return $query;
    }
}