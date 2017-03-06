<?php

namespace App\Http\Controllers;
use App\Libraries\HttpStatusCode;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    private $_defaultHttpCode = HttpStatusCode::OK;
    //
    public function respond($data, $statusCode = null, $headers = [])
    {
        $return = [];
        $statusCode = $statusCode ? $statusCode : $this->_defaultHttpCode;
        $status = ((int)$statusCode == $this->_defaultHttpCode) ? true : false;
        $return['status'] = $status;
        if ($status == true) {
            $return['data'] = $data;
        } else {
            $return['messages'] = $data;
        }
        return response()->json($return, $statusCode, $headers);
    }
}
