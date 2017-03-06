<?php

namespace App\Models;

use App\Libraries\MyHelper;


class UserRole extends UserRoleFactory
{
    protected $table = 'user_role';
    const ACTIVE = "AT";
    const INACTIVE = "IA";

}