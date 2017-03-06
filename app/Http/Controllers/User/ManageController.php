<?php

namespace App\Http\Controllers\User;


use App\Libraries\Clients;
use App\Libraries\MyHelper;
use App\Models\LogEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;


class ManageController extends Controller
{

    /**
     * Handle a login request to the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request, $whs)
    {

        $param = Input::input();
        try {
            $validator = Validator::make($request->all(), [
                'user_name' => 'required',
                'password' => 'required'
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
                    'message' => 'Invalid auth',
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

            if (!$token = Auth::attempt($credentials)) {
                return response()->json(['message' => 'user name is not esxit or password  is incorrect.'
                    , 'status' => false], 400);
            }
            return MyHelper::response(true, compact('token'), 200);
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 200);
        }

    }


    public function getListUserWms(Request $request, $whsId)
    {
        try {
            if ($whsId) {
                $url = MyHelper::getUrlUserListWms($whsId);
                $dataWms = ['whs_id' => $whsId];
                $data = Clients::ConnectWmsDataUserWmsList('GET', $url, $dataWms);
                $dataWmsSet = \GuzzleHttp\json_decode($data);
                $result = [];
                foreach ($dataWmsSet->data as $key => $value) {
                    $user = User::getUser($value->username);
                    if (!$user) {
                        $result[] = $value;
                    }
                }
                return MyHelper::response(true, $result, 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(true, $e->getMessage(), 422);
        }
    }

    public function getUserDetail(Request $request, $whsId, $userId)
    {
        try {
            if ($whsId) {
                $userDetail = User::getUserDetail($userId);
                return MyHelper::response(true, $userDetail, 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(true, $e->getMessage(), 422);
        }
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

    public function getListUser()
    {
        $params = Input::all();
        $result = User::getList($params);
        return $result;
    }

    public function putUpdateUser($whsId, $idUser)
    {
        try {
            if ($whsId) {
                $input = Input::all();
                User::updateUser($input, $idUser);
                LogEvent::saveLog(
                    LogEvent::EDIT_USER,
                    null,
                    null,
                    ['user_name' => json_encode($input),]
                );
                return MyHelper::response(true, trans('messages.success'), 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(true, $e->getMessage(), 422);
        }

    }

    public function createUser($whsId)
    {
        try {
            if ($whsId) {
                $input = Input::all();
                $user = User::getUser($input['user_name']);
                if (!$user) {
                    User::createUser($input);
                }
                $userLate = User::getUser($input['user_name']);
                Role::updateRoleForUser($input, $userLate->user_id);
                LogEvent::saveLog(
                    LogEvent::CREATE_USER,
                    null,
                    null,
                    ['user_name' => $input['user_name'],]
                );
                return MyHelper::response(true, trans('messages.success'), 200);
            }
        } catch (\Exception $e) {
            return MyHelper::response(true, $e->getMessage(), 422);
        }

    }


}
