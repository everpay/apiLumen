<?php
namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Libraries\HttpStatusCode;
use App\Models\Role;
use App\Libraries\MyHelper;
use Illuminate\Support\Facades\Input;


class IndexController extends Controller
{
    protected $_role;

    public function __construct(Role $role)
    {
        $this->_role = $role;
    }

    public function getAllRole($whsId)
    {
        try {
            $data = $this->_role->getAllRole();
            return MyHelper::response(true, $data);
        } catch (\Exception $ex) {
            return MyHelper::response(false, $ex->getMessage(), 204);
        }
    }

    /**
     * @param $whsId
     * @return array
     */

    public function getList($whsId)
    {
        $params = Input::all();
        try {
            if ($whsId) {

                $model = $this->_role->getList($params);
                if (count($model)) {
                   // $list = MyHelper::listRespond($model);
                    return $this->respond($model);
                }
                return $this->respond(null, HttpStatusCode::NO_CONTENT);
            }
        } catch (\Exception $e) {
            return MyHelper::response(true, $e->getMessage(), 422);
        }
    }


    public function getOne($idRole)
    {
        try {
            $role = Role::getOne($idRole);
            return $this->respond($role);
        } catch (ModelNotFoundException $ex) {
            return $this->respond(sprintf(['not_found'], 'role_id: ' . $idRole),
                HttpStatusCode::NOT_FOUND);
        } catch (\Exception $ex) {
            return $this->respond($ex->getMessage(), HttpStatusCode::UNPROCESSABLE_ENTITY);
        }
    }

    public function updateRole( $whs,$roleId)
    {
        try {
            $params = Input::all();
            $data = $this->_role->updateRole($params['sts'], $roleId);
            return MyHelper::response(true, $data, 200);
        } catch (\Exception $ex) {
            return $this->respond($ex->getMessage(), HttpStatusCode::UNPROCESSABLE_ENTITY);
        }
    }


}