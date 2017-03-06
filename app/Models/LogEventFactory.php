<?php
namespace App\Models;

use App\Models\AbstractModel;
use App\Models\User;
use DB;
use App\Models\AccessLog;
use App\Libraries\Helpers;


/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */
abstract class LogEventFactory extends AbstractModel
{


    /**
     * @return mixed
     */
    public static function getLogEvent()
    {
        return self::select('log_event_id AS item_id',
            'name AS item_num')->get();
    }

    /**
     * @param $code
     * @param $user
     * @param $ip
     * @param array $option
     */
    public static function saveLog($code,  $userId, $ip, array $option = null)
    {
        try {

            $logEvt = LogEvent::getLogEventByCode($code);
            $data = [];
            if ($logEvt) {
                $data['log_event_id'] = $logEvt->log_event_id;
                $time = time();
                if($option != null){
                    $mapKey = explode('#|' . $time . '|#', '<' . implode('>#|' . $time . '|#<', array_keys($option)) . '>');
                    $data['event_object'] = str_replace($mapKey, array_values($option), $logEvt->object_info);
                    if (!empty($option['items'])) {
                        $data['event_object'] = $option['items'] . ', ' . $data['event_object'];
                    }
                }
                $format = 'Y-m-d H:i:s';
                $data['user_id'] = !empty($userId) ? $userId : Null;
                $data['access_ip'] = $ip;
                $mailRole = !empty($userId) ? User::getMainRole($userId): Null;
                $data['role_id'] = $mailRole ? $mailRole->role_id : null;
                $data['access_time'] = date($format,$time);

                AccessLog::insertAccessLog($data);
            }
        }
        catch (\Exception $e)
        {
            return;
        }
    }

}