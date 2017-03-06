<?php
namespace App\Models;

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/27/2016
 * Time: 10:41 AM
 */
class Role extends RoleFactory
{
    protected $table = 'role';
    protected $primaryKey = 'role_id';

    const ROLE_ACTIVE = "AT";
    const ROLE_INACTIVE = "IT";

    const SUPER_ADMIN = "SUPER_ADMIN";
    const CLERK = "CLERK";
    const CHECKER = "CHECKER";
    const PL_LOADER = "PL_LOADER";
    const WV_PICKER = "WV_PICKER";
    const ORD_PROCESSOR = "ORD_PROCESSOR";

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_date';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_date';

}