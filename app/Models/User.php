<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/17/2016
 * Time: 11:15 AM
 */


namespace App\Models;

use Carbon\Carbon;
use App\Models\UserWarehouse;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use DB;

class User extends AbstractModel implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';
    public static $tblName = 'users';
    protected $primaryKey = 'user_id';
    const ACTIVE = "AT";
    const INACTIVE = "IA";
    const CHECKING = "CK";
    const SUPER_ADMIN = "SUPER_ADMIN";
    const CLERK = "CLERK";
    const CHECKER = "CHECKER";
    const FL_DRIVER = "FL_DRIVER";
    const WV_PICKER = "WV_PICKER";
    const ORD_PROCESSOR = "ORD_PROCESSOR";
    const PL_LOADER = "PL_LOADER";


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

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array $attributes
     * @return void
     */

    protected $fillable = [
        'full_name', 'user_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    public function getJWTIdentifier()
    {
        // TODO: Implement getJWTIdentifier() method.
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        // TODO: Implement getJWTCustomClaims() method.
        return [];
    }

    public static function getUser($userName)
    {
        $data = self::where('user_name', $userName)
            ->first();
        return $data;
    }

    public static function getFullNameUser($userName)
    {
        $data = self::where('full_name', $userName)
            ->first();
        return $data;
    }

    public static function updateTimeLogin($userId)
    {

        try {
            $current = new Carbon();
            $queryUser = self::where('user_id', $userId);
            $queryUser->update([
                'last_access' => $current
            ]);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }

    }

    public static function createUser($data)
    {
        $user = new User();
        $user->full_name = $data['full_name'];
        $user->job_title = $data['job_title'];
        $user->email = $data['email'];
        $user->user_name = $data['user_name'];
        $user->status = self::ACTIVE;
        $user->save();
    }

    public static function updatedInfoUser($data)
    {

        $query = self::where('user_name', $data->username)
            ->update([
                'full_name' => $data->first_name . ' ' . $data->last_name,
                'email' => $data->email,
            ]);
    }

    public static function getListChecker()
    {
        $listChecker = self::leftJoin('user_role',
            'users.user_id', '=', 'user_role.user_id'
        )->leftJoin('role',
            'user_role.role_id', '=', 'role.role_id'
        )
            ->where([['role.code', self::CHECKER], ['users.status', self::ACTIVE]])->select('users.full_name', 'users.user_id')->get();
        return $listChecker;
    }

    public static function updateCheckerStatus($userName, $status)
    {
        $query = self::where('user_name', $userName)->update(['status' => $status]);
        return $query;
    }


    /**
     * @param $userId
     * @return array
     */
    public static function getUserRolePermission($userId)
    {
        $data = self::leftJoin('user_role', 'users.user_id', '=', 'user_role.user_id')
            ->leftJoin('role', 'user_role.role_id', '=', 'role.role_id')
            ->leftJoin('role_permission', 'role.role_id', '=', 'role_permission.role_id')
            ->leftJoin('permission', 'role_permission.permission_id', '=', 'permission.permission_id')
            ->leftJoin('perms_group', 'permission.permission_group_id', '=', 'perms_group.permission_group_id')
            ->select(
                'users.user_id',
                'users.full_name',
                'users.user_name',
                'users.email',
                'users.status as sts',
                'users.job_title',
                'user_role.is_main',
                'role.code',
                'role.role_id',
                'role.code',
                'permission.code as perms_code'

            )
            ->where('users.user_id', $userId)
             ->where('users.status', User::ACTIVE)
            ->where('role.status',Role::ROLE_ACTIVE)
            ->get()->toArray();

        if (!is_array($data) || count($data) == 0) {
            return [];
        }
        $result['user_id'] = $data[0]['user_id'];
        $result['full_name'] = $data[0]['full_name'];
        $result['user_name'] = $data[0]['user_name'];
        $result['is_main'] = $data[0]['is_main'];
        $result['role_code'] = $data[0]['code'];
        $result['role_id'] = $data[0]['role_id'];
        $result['email'] = $data[0]['email'];
        $result['status'] = $data[0]['sts'];
        $result['job_title'] = $data[0]['job_title'];
        $result['permission'] = [];
        foreach ($data as $key => $value) {
            $result['permission'][str_replace('-', '_', $value['perms_code'])] = 1;
        }

        return $result;

    }


    /**
     * @param $userId
     * @return array
     */
    public static function getUserDetail($userId)
    {
        $data = self::leftJoin('user_role', 'users.user_id', '=', 'user_role.user_id')
            ->leftJoin('role', 'user_role.role_id', '=', 'role.role_id')
            ->leftJoin('role_permission', 'role.role_id', '=', 'role_permission.role_id')
            ->leftJoin('permission', 'role_permission.permission_id', '=', 'permission.permission_id')
            ->leftJoin('perms_group', 'permission.permission_group_id', '=', 'perms_group.permission_group_id')
            ->select(
                'users.user_id',
                'users.full_name',
                'users.user_name',
                'users.email',
                'users.created_date as created_at',
                'users.status as sts',
                'users.job_title',
                'user_role.is_main',
                'role.code',
                'role.role_id',
                'role.code',
                'role.name',
                'permission.code as perms_code'
            )
            ->where('users.user_id', $userId)
            ->where('role.status', Role::ROLE_ACTIVE)
            ->get()->toArray();
        if (!is_array($data) || count($data) == 0) {
            return [];
        }
        $result['user_id'] = $data[0]['user_id'];
        $result['full_name'] = $data[0]['full_name'];
        $result['user_name'] = $data[0]['full_name'];
        $result['is_main'] = $data[0]['is_main'];
        $result['role_code'] = $data[0]['code'];
        $result['email'] = $data[0]['email'];
        $result['created_at'] = $data[0]['created_at'];
        $result['status'] = $data[0]['sts'];
        $result['job_title'] = $data[0]['job_title'];
        $result['role_id'] = [];
        $result['permission'] = [];
        foreach ($data as $row) {
            if (isset($row['role_id']) && !in_array($row['role_id'], $result['role_id'])) {
                $result['role_id'][] = $row['role_id'];
            }
            $result['permission'][str_replace('-', '_', $row['perms_code'])] = 1;
        }
        $result['role_log'] = 2;
        if (in_array(1, $result['role_id'])) {
            $result['role_log'] = 1;
        }
        return $result;

    }

    /**
     * @param $pagination
     * @return array
     */
    public static function getList($pagination)
    {
        /** @var TYPE_NAME $this */

        if (!isset($pagination['length'])) {
            $length = 15;
        } else {
            $length = $pagination['length'];
        }
        $query = self::select(
            'user_id',
            'full_name',
            'job_title',
            'email',
            'user_name',
            'status',
            'created_date'
        );
        $data = $query->paginate($length);

        $result = [];
        $listUserId = [];
        foreach ($data as $item) {
            $result['rows'][] = [
                'user_id' => $item->user_id,
                'full_name' => $item->full_name,
                'job_title' => $item->job_title,
                'email' => $item->email,
                'user_name' => $item->user_name,
                'sts' => $item->status,
                'created_at' => $item->created_date,
            ];
            $listUserId[] = $item->user_id;
        }

        $dataUserRole = UserRole::whereIn('user_id', $listUserId)
            ->where('role.status', Role::ROLE_ACTIVE)
            ->leftJoin(
                'role',
                'role.role_id',
                '=',
                'user_role.role_id'
            )
            ->select('user_role.user_id', 'user_role.role_id', 'role.name')->get()->toArray();
        $dataRoleId = [];
        foreach ($dataUserRole as $row) {
            $dataRoleId[$row['user_id']][] = (object)['role_id' => $row['role_id'], 'name' => $row['name']];
        }
        foreach ($result['rows'] as $index => $row) {
            $result['rows'][$index]['roles'] = [];
            if (isset($dataRoleId[$row['user_id']])) {
                $result['rows'][$index]['roles'] = $dataRoleId[$row['user_id']];
            }
        }
        $result['page'] = $data->currentPage();
        $result['length'] = $data->perPage();
        $result['total_record'] = $data->total();
        $result['total_page'] = ceil($result['total_record'] / $result['length']);
        return $result;
    }

    /**
     * @param $input
     * @param $userId
     * @return bool
     */
    public static function updateUser($input, $userId)
    {
        $user = self::updatedUser($input, $userId);
        if ($input['role_id'] && count($input['role_id']) > 0) {
            UserRole::where('user_id', $userId)->delete();
            $dataInsert = [];
            foreach ($input['role_id'] as $roleId) {
                $dataInsert[] = [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                ];
            }
            DB::table('user_role')->insert($dataInsert);
        }
        return $user;
    }

    public static function updatedUser($data, $userId)
    {
        $query = self::where('user_id', $userId);
        if (!isset($data['full_name'])) {
            $query->update([
                'status' => $data['status'],
            ]);
        } else {
            $query->update([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'job_title' => $data['job_title'],
                'status' => $data['status'],
            ]);
        }
        $data = self::where('user_id', $userId);
        return $data->get();
    }


    public static function getMainRole($userId)
    {

        $query = self::join('user_role', 'users.user_id', 'user_role.user_id')
            ->join('role', 'user_role.role_id', 'role.role_id')->select([
                'users.user_id',
                'role.role_id',
            ])
            ->where('users.user_id', $userId)
            ->first();
        return $query;
    }


    public static function updatedTokenUser($username, $token)
    {
        self::where('user_name', $username)
            ->update(array(
                'token' => $token,
                'last_access' =>   DB::raw('NOW()')
            ));
    }

}
