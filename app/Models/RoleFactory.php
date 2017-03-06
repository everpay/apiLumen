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
abstract class RoleFactory extends AbstractModel
{
    /**
     * @param $pagination
     * @return mixed
     */
    public function getList($pagination)
    {
        $query = self::leftJoin('user_role',
            'role.role_id',
            '=',
            'user_role.role_id')
            ->leftJoin('users',
                'user_role.user_id',
                '=',
                'users.user_id')
            //->where('users.status', User::ACTIVE)
            ->groupBy('role.role_id')
            ->select(
                'users.user_id',
                'role.role_id',
                'role.code',
                'role.name',
                Db::raw('count(user_role.user_id) as number_of_user'),
                'role.description',
                'role.status');
        if(!isset($pagination['length'])){
            $length = 15;
        }else{
            $length = $pagination['length'];
        }
        $data = $query->paginate($length);
        $result = [];
        foreach ($data as $item) {
            $result['data'][] = [
                'role_id' => $item->role_id,
                'cd' => $item->code,
                'name' => $item->name,
                'des' => $item->description,
                'number_of_user' => $item->number_of_user,
                'sts' => $item->status
            ];
        }

        $result['page'] = $data->currentPage();
        $result['length'] = $data->perPage();
        $result['total_record'] = $data->total();
        $result['total_page'] = ceil($result['total_record'] / $result['length']);
        return $result;
    }

    public function getAllRole()
    {
        return self::select(
            'role_id',
            'name'
        )->get()->toArray();
    }

    public static function getRoleId($userId)
    {
        return self::leftJoin('user_role',
            'role.role_id',
            '=',
            'user_role.role_id')
            ->leftJoin('users',
                'user_role.user_id',
                '=',
                'users.user_id')->where('users.user_id',$userId)->select(
            'role.code'
        )->first();
    }


    public static function getOne($roleId)
    {
        $role = Role::where('role_id', '=', $roleId)->get()->first();
        if (empty($role)) {
            throw new ModelNotFoundException;
        }
        $data = self::where('role.role_id', '=', $roleId)
            ->leftJoin(
                'role_permission',
                'role_permission.role_id',
                '=',
                'role.role_id'
            )
            ->leftJoin(
                'permission',
                'permission.permission_id',
                '=',
                'role_permission.permission_id'
            )
            ->select(
                'role.role_id',
                'role.name as name_role',
                'role.code as cd',
                'role.description as des',
                'role_permission.permission_id as perms_id',
                'permission.name as name_perms'

            )->get()->toArray();
        if (count($data) === 0 or !is_array($data)) {
            return [];
        }
        $result = [];
        $result['role_id'] = $data[0]['role_id'];
        $result['name'] = $data[0]['name_role'];
        $result['des'] = $data[0]['des'];
        $result['cd'] = $data[0]['cd'];
        $result['number_of_user'] = 0;
        $result['perms'] = [];
        $total = DB::table('user_role')
            ->select(DB::raw('count(user_id) as total'))
            ->where('role_id', $roleId)
            ->get()
            ->toArray();
        if (count($total) > 0 && isset($total[0]->total)) {
            $result['number_of_user'] = $total[0]->total;
        }
        foreach ($data as $item) {
            if (empty($item['perms_id'])) {
                break;
            }
            $result['perms'][] = [
                'perms_id' => $item['perms_id'],
                'name_perms' => $item['name_perms']
            ];
        }
        return $result;
    }


    public static function updateRole($status, $roleId)
    {
        self::where('role_id',$roleId)->update(['status'=>$status]);
        $role = self::where('role_id',$roleId)->first();

        return $data = [
            'role_id' => $role->role_id,
            'cd' => $role->code,
            'name' => $role->name,
            'des' => $role->description,
            'sts' => $role->status
        ];
    }


    public static function updateRoleForUser($data,$userId)
    {
        if ($data['role_id'] && count($data['role_id']) > 0) {
            UserRole::where('user_id', $userId)->delete();
            $dataInsert = [];
            foreach ($data['role_id'] as $roleId) {
                $dataInsert[] = [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                ];
            }
            DB::table('user_role')->insert($dataInsert);
        }
    }


}