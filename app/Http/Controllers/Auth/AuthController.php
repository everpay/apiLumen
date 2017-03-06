<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/17/2016
 * Time: 2:37 PM
 */
namespace App\Http\Controllers\Auth;

use App\Libraries\Clients;
use App\Models\Device;
use App\Models\LogEvent;
use App\Models\PalletProcessing;
use App\Models\PalletProcessingFactory;
use App\Models\RecProcessing;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Namshi\JOSE\Test\JWSTest;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use App\Libraries\MyHelper;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use App\Models\UserWarehouse;
Use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\JWT;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

class AuthController extends Controller
{
    const ACTION_SEND_DATA_SOCKET = 'senddata';

    /**
     * Handle a login request to the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {
        $param = Input::input();
        try {
            $validator = Validator::make($request->all(), [
                'user_name' => 'required',
                'password' => 'required',
                'uuid'     => '',
            ]);

            if ($validator->fails()) {
                return [
                    'message' => $validator->errors()->first(),
                    //'status' => IlluminateResponse::HTTP_BAD_REQUEST,
                    'status' => false
                ];
                //return MyHelper::response(false, trans('messages.username-password-empty'), 200);
            }


        } catch (HttpResponseException $e) {
            return response()->json([
                'error' => [
                    'message'     => 'Invalid auth',
                   // 'status_code' => IlluminateResponse::HTTP_BAD_REQUEST,
                    'status' => false
                ]],
                //IlluminateResponse::HTTP_BAD_REQUEST,
                $headers = []
            );
            //return MyHelper::response(false, trans('messages.username-password-empty'), 200);
        }
        try {
            $credentials = $this->getCredentials($request);
            $token = Clients::ConnectGetTokenWms($credentials);
            $user = Clients::jwtDecode($token);

            $data = array_merge($user, $credentials);
            $userLogin = [];
            $userLogin['user_name'] = $user['username'];
            $userLogin['token'] = $token;
            $dataSend = [
                'action' => "AuthLogin",
                'dataRes' => $userLogin,
            ];
            Clients::transferDataToFE(self::ACTION_SEND_DATA_SOCKET, $dataSend);
            if ($token) {
                $userDb = User::getUser($data['user_name']);
                // User::updateCheckerStatus($data['user_name'],User::ACTIVE);
                $wareHouse = Warehouse::first();
                $data['warehouse_id'] = $wareHouse->warehouse_id;
                if (!$userDb) {
                    return MyHelper::response(false, trans('messages.username-exist'), 200);
//                    user::createUser($data);
//                    $userId = user::getUser($user['username']);
//                    $userId = $userId->user_id;
//                    UserWarehouse::createWareHouseUser($data, $userId);
//                    $user = user::getUserRolePermission($userId);
                } else {
                    $user = user::getUserRolePermission($userDb->user_id);
                }
                if (count($user) == 0) {
                    $user['role_id'] = 0;
                    $array['data'] = array_merge(['token' => $token], ['whsid' => $wareHouse->warehouse_id], ['Code' => $wareHouse->code], ['user' => $user]);
                    return MyHelper::response(false, trans('messages.user-inactive'), 200);
                }
                $userLoad = PalletProcessing::getPalletProcessingId($user['user_id']);
                if ($user['role_code'] == User::PL_LOADER && isset($user['role_code']) || $user['role_code'] == User::WV_PICKER) {
                    if (isset($param['uuid'])) {
                        $uuid = $param['uuid'];
                        $device = Device::getDeviceIdFromUuid($uuid);
                        $deviceId = $device->device_id;
                        if ($userLoad == Null) {
                            PalletProcessing::createPalletProcessing($user['user_id'], $device);
                        }else{
                            PalletProcessing::updateOnlyStatus($user['user_id'],PalletProcessing::STATUS_PICKING);
                        }
                        $array['data'] = array_merge(['token' => $token], ['user' => $user], ['device_name' => $device->name], ['whsid' => $wareHouse->warehouse_id], ['Code' => $wareHouse->code]);
                    } else {
                        return MyHelper::response(true, trans('messages.no_uuid'), 200);
                    }

                } else {
                    $array['data'] = array_merge(['token' => $token], ['user' => $user], ['whsid' => $wareHouse->warehouse_id], ['Code' => $wareHouse->code]);
                }
                LogEvent::saveLog(
                    LogEvent::LOGIN_SUCCESS,
                    $user,
                    $request->ip(),
                    ['user_name' => $user['user_name']]
                );

                return MyHelper::response(true, $array['data'], 200);
            } else {
                return MyHelper::response(true, trans('messages.username-password-error'), 200);
            }
        } catch (\Exception $e) {
            $userDb = User::getUser($param['user_name']);
            if($userDb){
                $user = user::getUserRolePermission($userDb->user_id);
                if(isset($param['uuid']) ){
                    if($param['uuid'] == "" && $user['role_code'] == User::PL_LOADER && $user['role_code'] == User::WV_PICKER){
                        LogEvent::saveLog(
                            LogEvent::LOGIN_FAILD,
                            null,
                            null,
                            ['user_name' =>$param['user_name'],'errms' => json_encode(trans('messages.empty_uuid'))]
                        );
                        return MyHelper::response(false, trans('messages.empty_uuid'), 200);
                    }else{
                        LogEvent::saveLog(
                            LogEvent::LOGIN_FAILD,
                            null,
                            null,
                            ['user_name' =>$param['user_name'],'errms' => json_encode(trans('messages.username-password-error'))]
                        );
                        return MyHelper::response(false, trans('messages.username-password-error'), 200);
                    }
                }else{
                    LogEvent::saveLog(
                        LogEvent::LOGIN_FAILD,
                        null,
                        null,
                        ['user_name' =>$param['user_name'],'errms' => json_encode(trans('messages.username-password-error'))]
                    );
                    return MyHelper::response(false, trans('messages.username-password-error'), 200);
                }
            }else{
                LogEvent::saveLog(
                    LogEvent::LOGIN_FAILD,
                    null,
                    null,
                    ['user_name' =>$param['user_name'],'errms' => json_encode(trans('messages.username-password-error'))]
                );
                return MyHelper::response(false, trans('messages.username-password-error'), 200);
            }

        }

    }

    public function postLogout(Request $request)
    {
        $token = $request->header('Authorization');
        $user = Clients::jwtDecode($token);
        $username = $user['username'];
        $user = User::getUser($username);
        $processorId = $user->user_id;
        $pallet =  PalletProcessing::getPalletProcessingId($processorId);
        LogEvent::saveLog(
            LogEvent::LOG_OUT,
            null,
            null,
            ['user_name' =>$username,]
        );
        if($pallet){
            PalletProcessing::updateOnlyStatus($processorId,PalletProcessing::STATUS_DISCONNECT);
        }else{
            return MyHelper::response(true, 'successfully', 200);
        }
        return MyHelper::response(true, 'successfully', 200);
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    protected function getCredentials(Request $request)
    {
        return $request->only('user_name', 'password');
    }


    public function getCheckerList($wshId)
    {
        try {
            if (isset($wshId)) {
                $userActive = User::getListChecker();
               /* $checker = RecProcessing::getDeviceStatusRv();
                $data = [];
                if ($checker->isEmpty()) {
                    $data = $userActive;
                } else {
                    foreach ($userActive as $key => $value) {
                        foreach ($checker as $k => $v) {
                            if ($value->user_id != $v->checker_id) {
                                $data[] = $value;
                            }
                        }
                    }
                }*/
                return MyHelper::response(true, $userActive, 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    public function getCheckCheckerRv($wshId,$checkerId)
    {
        try {
            $params = Input::all();
            if (isset($wshId)) {
                $checker = RecProcessing::getDeviceStatusRv($params);
                if($checker){
                    foreach($checker as $k => $v){
                        if($v->checker_id == $checkerId){
                            return MyHelper::response(false, trans("messages.checker-receiving"), 200);
                        }
                    }
                }
                return MyHelper::response(true, trans("messages.success"), 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }


}