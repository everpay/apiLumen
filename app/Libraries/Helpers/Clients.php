<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/21/2016
 * Time: 2:34 PM
 */


namespace App\Libraries;

use App\Http\Controllers\Wap\AsnController;
use App\Libraries\Helpers\SocketCLI\ConnectRFID;
use App\Models\AsnProcessing;
use App\Models\LogEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Libraries\MyHelper;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use GuzzleHttp\Psr7;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\Check;
use Illuminate\Http\Request;
use Illuminate\Http\Exception\HttpResponseException;
use ElephantIO\Client;
use ElephantIO\Engine\SocketIO\Version1X;

/**
 * Class Clients
 */
class Clients
{

    /**
     * @param array $option
     * @return mixed
     */
    public static function ConnectGetTokenWms(array $option)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request('POST', MWS_URL_LOGIN . '/v1/login', [
                'form_params' => ['username' => $option['user_name'],
                    'password' => $option['password']]
            ]);
            $data = explode('"', $res->getBody()->getContents());
            $arrayToken = array_slice($data, 5);
            $token = array_shift($arrayToken);
            LogEvent::saveLog(
                LogEvent::CALL_WMS_SUCCESSFUL,
                null,
                null,
                ['api_name' => MWS_URL_LOGIN . '/v1/login', 'param' => implode($option)]
            );
            return $token;
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }

    /**
     * @param array $option
     * @return mixed
     */
    public static function ConnectGetTokenWmsProcessing()
    {
        try {
            $client = new \GuzzleHttp\Client();
            $res = $client->request('POST', MWS_URL_LOGIN . '/v1/login', [
                'form_params' => ['username' => USER_WMS_PROCESS,
                    'password' => 'Seldat@123']
            ]);
            $data = explode('"', $res->getBody()->getContents());
            $arrayToken = array_slice($data, 5);
            $token = array_shift($arrayToken);
            User::updatedTokenUser(USER_WMS_PROCESS, $token);
            $userData = User::getUser(USER_WMS_PROCESS);
            LogEvent::saveLog(
                LogEvent::CALL_WMS_SUCCESSFUL,
                null,
                null,
                ['api_name' => MWS_URL_LOGIN . '/v1/login', 'param' => implode(['username' => USER_WMS_PROCESS])]
            );
            return $userData->token;

        } catch (\Exception $e) {
            LogEvent::saveLog(
                LogEvent::CALL_WMS_FAILD,
                null,
                null,
                ['api_name' => MWS_URL_LOGIN . '/v1/login', 'errms' => $e->getMessage()]
            );
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }


    /**
     * @return mixed
     */
    public static function ConnectGetTokenWmsScan()
    {
        $user = user::getUser(USER_WMS_PROCESS);
        $asnProcessing = asnProcessing::getAsns();
        if (count($asnProcessing) == 1) {
            $token = self::ConnectGetTokenWmsProcessing();
            return $token;
        }
        if ($user && $user->token == Null) {
            $token = self::ConnectGetTokenWmsProcessing();
            return $token;
        } elseif ($user->token) {
            $timeExpire = date('Y-m-d H:i:s', $user->last_access + time() + TIME_24H);
            $now = Carbon::now();
            $now->toDateTimeString();
            if ($timeExpire < $now->toDateTimeString() || $timeExpire == $now->toDateTimeString()) {
                $token = self::ConnectGetTokenWmsProcessing();
                return $token;
            } else {
                return $user->token;
            }
        } else {
            $token = self::ConnectGetTokenWmsProcessing();
            return $token;
        }
    }

    /**
     * @param $method
     * @param $url
     * @param $data
     * @param $request
     * @param bool $json
     * @return array|string
     */

    public static function ConnectWmsData($method, $url, $data, $request)
    {
        try {
            $token = $request->header('Authorization');
            if (!empty($token)) {
                $headers = [
                    'Authorization' => $token,
                ];
                $client = new \GuzzleHttp\Client();
                $res = $client->request($method, $url, [
                    'form_params' => $data,
                    'headers' => $headers,
                ]);
                $body = $res->getBody()->getContents();
                $dataJ = json_encode($data);
                $userId = AsnController::getUserId($request);
                LogEvent::saveLog(
                    LogEvent::CALL_WMS_SUCCESSFUL,
                    $userId,
                    null,
                    ['api_name' => $url, 'param' => $dataJ]
                );
                return $body;
            }
        } catch (\Exception $e) {
            $data = json_encode($e->getMessage());
            $dataJ = json_encode($data);
            $userId = AsnController::getUserId($request);
            LogEvent::saveLog(
                LogEvent::CALL_WMS_FAILD,
                $userId,
                null,
                ['api_name' => $url, 'param' => $dataJ, 'errms' => $data]
            );
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }


    public static function ConnectWmsDataUserWmsList($method, $url, $data)
    {
        try {
            $token = self::ConnectGetTokenWmsScan();
            if ($token) {
                $headers = [
                    'Authorization' => 'Bearer ' . $token,
                ];
                $client = new \GuzzleHttp\Client();
                $res = $client->request($method, $url, [
                    'form_params' => $data,
                    'headers' => $headers,
                ]);
                $body = $res->getBody()->getContents();
                LogEvent::saveLog(
                    LogEvent::CALL_WMS_SUCCESSFUL,
                    null,
                    null,
                    ['api_name' => $url, 'param' => $data]
                );
                return $body;
            }
        } catch (\Exception $e) {
            $data = json_encode($e->getMessage());
            LogEvent::saveLog(
                LogEvent::CALL_WMS_FAILD,
                null,
                null,
                ['api_name' => $url, 'param' => $data, 'errms' => $data]
            );
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }


    /**
     * @param $method
     * @param $url
     * @param $json
     * @param $request
     * @return array|string
     */

    public static function ConnectWmsDataJson($method, $url, $json, $request)
    {
        try {
            $token = $request->header('Authorization');
            if (!empty($token)) {
                $headers = [
                    'Authorization' => $token,
                    'Content-Type' => 'application/json',
                ];
                $client = new \GuzzleHttp\Client();
                $res = $client->request($method, $url, [
                    'body' => json_encode($json),
                    'headers' => $headers,
                ]);
                $body = $res->getBody()->getContents();
                $userId = AsnController::getUserId($request);
                LogEvent::saveLog(
                    LogEvent::CALL_WMS_SUCCESSFUL,
                    $userId,
                    null,
                    ['api_name' => $url, 'param' => json_encode($json)]
                );

                return $body;
            }
        } catch (\Exception $e) {
            $userId = AsnController::getUserId($request);
            LogEvent::saveLog(
                LogEvent::CALL_WMS_FAILD,
                $userId,
                null,
                ['api_name' => $url, 'param' => json_encode($json), 'errms' => $e->getMessage()]
            );
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    /**
     * @param $token
     * @return string
     * @throws UnexpectedValueException
     */
    public static function jwtDecode($token)
    {
        $tks = explode('.', $token);
        $payLoad = base64_decode($tks[1]);
        $data = \GuzzleHttp\json_decode($payLoad);
        $user = [
            "user_id" => $data->jti,
            "full_name" => $data->name,
            "exp" => $data->exp,
            'username' => $data->username,

        ];
        return $user;
    }

    /**
     * @param $actionEmit : action emit socket
     * @param $dataSend : data send to frontend
     */
    public static function transferDataToFE($actionEmit, $dataSend)
    {
        $ipServerNodejs = env('IP_SERVER_NODEJS', '');
        $postNodejs = env('PORT_NODEJS', '');
        $client = new Client(new Version1X($ipServerNodejs . ':' . $postNodejs));
        $client->initialize();
        $client->emit($actionEmit, [
            'dataRes' => $dataSend
        ]);
        $client->close();
    }

    public static function setAlertReader($sock)
    {
        $commandGPO = ConnectRFID::CMD_SET_GPO;
        //send command stop operation
        //ConnectRFID::sendCommand($sock, ConnectRFID::CMD_STOP_OPERATION);
        //send command off led green
        //ConnectRFID::sendCommand($sock, $commandGPO, 'gpo2:off');//led green
        //send command off led yellow
        //ConnectRFID::sendCommand($sock, $commandGPO, 'gpo1:off');//led yellow
        //send command on alarm
        ConnectRFID::sendCommand($sock, $commandGPO, 'gpo3:on');//alarm
        //send command on led red
        $red = false;
        $count = 0;
        while (true) {
            if ($count < 6) {
                if (!$red) {
                    ConnectRFID::sendCommand($sock, $commandGPO, 'gpo0:on');//led red on
                    $red = true;
                } else {
                    ConnectRFID::sendCommand($sock, $commandGPO, 'gpo0:off');//led red off
                    $red = false;
                }
                $count++;
            } else {
                self::resetAlertReader($sock);
                break;
            }
            usleep(10000);
        }
    }

    public static function resetAlertReader($sock)
    {
        $commandGPO = ConnectRFID::CMD_SET_GPO;
        //send command off led red
        ConnectRFID::sendCommand($sock, $commandGPO, 'gpo0:off');//led red
        //send command off alarm
        ConnectRFID::sendCommand($sock, $commandGPO, 'gpo3:off');//alarm
        //send command on alarm
        //ConnectRFID::sendCommand($sock, $commandGPO, 'gpo2:on');//led green
        //send command start operation
        //ConnectRFID::sendCommand($sock, ConnectRFID::CMD_START_OPERATION);
    }

    /**
     * @param $data : content for write log
     *
     */
    public static function writeLog($data)
    {
        $dir = str_replace("\\", "/", base_path() . "/storage/logs/logScan.txt");
        $file = fopen($dir, "a");
        $str = "Action => " . $data['action'] . ":" . PHP_EOL;
        $str .= json_encode($data['data'], JSON_FORCE_OBJECT) . PHP_EOL;
        $str .= "---------------------------------------------" . PHP_EOL;
        fwrite($file, $str);
        fclose($file);
    }
}