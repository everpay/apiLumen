<?php
namespace App\Models;

use App\Models\AbstractModel;
use App\Models\User;
use DB;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:43 AM
 */
class RolePermission extends RolePermissionFactory
{
    protected $table = 'role_permission';
    protected $primaryKey = 'role_permission_id';
}