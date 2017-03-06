<?php

namespace App\Models;

use App\Models\AbstractModel;
use Illuminate\Support\Facades\Request;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */
abstract class AccessLogFactory extends AbstractModel
{


    public static function insertAccessLog($data)
    {
        $access = new AccessLog();
        $access['log_event_id'] = $data['log_event_id'];
        $access['access_ip'] = $data['access_ip'];
        $access['role_id'] = isset($data['role_id']) ? $data['role_id'] : null;
        $access['user_id'] = isset($data['user_id']) ? $data['user_id'] : null;
        $access['event_object'] = isset($data['event_object']) ? $data['event_object'] : null;
        $access['access_time'] = $data['access_time'];
        $access->save();
    }

    public static function getList($prams)
    {
        $query = self::select([
            'access_log.*',
            'users.full_name AS by_user',
            'log_event.name AS activity'
        ])
            ->leftJoin('users',
                'access_log.user_id',
                '=',
                'users.user_id')
            ->Join("log_event",
                'log_event.log_event_id',
                '=',
                'access_log.log_event_id')
            ->orderBy('access_log.access_log_id', 'desc');
        $startDate = empty($prams['start_dt']) ? '' :
            date('Y-m-d', strtotime($prams['start_dt']));
        $endDate = empty($prams['end_dt']) ? '' :
            date('Y-m-d', strtotime($prams['end_dt']));
        $request = app('request');
        if ($request->isMethod('post')) {
            $dataSearch = [
                ['field' => 'access_time', 'cd' => '>=', 'val' => $startDate],
                ['field' => 'access_time', 'cd' => '<=', 'val' => $endDate],
                ['field' => 'users.full_name', 'cd' => 'like',
                    'val' => empty($request->get('by_user')) ? '' : '%' . $request->get('by_user') . '%'],
                ['field' => 'log_event.log_event_id', 'cd' => '=', 'val' => $request->get('activity')],
            ];
            foreach ($dataSearch as $search) {
                if (!empty($search['val'])) {
                    $query->where($search['field'], $search['cd'], $search['val']);
                }
            }
        }
        if (!isset($prams['length'])) {
            $length = 15;
        } else {
            $length = $prams['length'];
        }
        $dataE = $query->paginate($length);
        $result = $dataE->toArray();
        $data['data'] = $result['data'];
        $data['page'] = $result['current_page'];
        $data['per_page'] = $result['per_page'];
        $data['total_record'] = $result['total'];
        $data['total_page'] = $result['last_page'];
        return $data;
    }

}
