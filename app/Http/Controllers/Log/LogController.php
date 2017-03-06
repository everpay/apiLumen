<?php

namespace App\Http\Controllers\Log;

use App\Models\AccessLog;
use App\Models\LogEvent;
use App\Libraries\Helpers;
use App\Libraries\HttpStatusCode;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class LogController extends Controller
{
    public function getListAccessLog()
    {
        try {
            $params = Input::all();
            $data = AccessLog::getList($params);
            return $this->respond($data);
        } catch (\Exception $e) {
            return $this->respond($e->getMessage(), HttpStatusCode::UNPROCESSABLE_ENTITY);
        }
    }

    public function getListLogEvent()
    {
        try {
            $data = LogEvent::getLogEvent();
            return $this->respond($data->toArray());
        } catch (\Exception $e) {
            return $this->respond($e->getMessage(), HttpStatusCode::UNPROCESSABLE_ENTITY);
        }
    }

}
