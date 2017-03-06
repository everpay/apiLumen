<?php

namespace App\Models;


class AccessLog extends AccessLogFactory
{
    protected $table = 'access_log';
    protected $primaryKey = 'access_log_id';
    public $timestamps = false;

}
