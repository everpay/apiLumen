<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/17/2016
 * Time: 10:05 AM
 */

namespace App\Http\Controllers\Socket;


use JWTAuth;
use App\Http\Controllers\Controller;
use App\Libraries\Helpers\SocketCLI\ConnectRFID;
use App\Libraries\Helpers\SocketCLI\ConnectRobot;


class SocketRfidController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function callScanCarton()
    {
        //echo "test scan carton\n";die;
        $hostip = '172.168.1.139';
        $port = '5000';
        $rs = $this->requestTagID($hostip, $port);
        
        print_r($rs);
        
        echo "\n Scan carton done!!!!!!\n";
    }

    public function callScanPallet()
    {
        //echo "test scan pallet\n";die;
        $hostip = '172.168.1.139';
        $port = '5000';
        var_dump($this->requestTagID($hostip, $port));
        echo "\n Scan pallet done!!!!!!\n";
    }

    public function callScanRac()
    {
        echo "test scan rac\n";die;
        $hostip = '192.168.0.107';
        $port = '5000';
        var_dump($this->requestTagID($hostip, $port));
        echo "\n Scan rac done!!!!!!\n";
    }
    
    public function callGetRfIdConfig() 
    {
        echo 'get rfid config' . "\n";
        $hostip = '172.168.1.139';
        $port = '5000';
        
        //$rs = $this->getRFIDConfiguration($hostname, $port);
        $rs = $this->getPortProperties($hostip, $port);
        
        print_r($rs);
        
        echo 'done ' . "\n";
    }

    public function call()
    {
        //echo "fasdfasdfas";die;
        $hostip = '172.168.1.139';//carton
        //$hostname = '172.168.1.139';//pallet
        //$hostname = '192.168.0.107';//Rack
        $port = '5000';
        var_dump($this->getGatewayInfo($hostip, $port));
//        sleep(2);
//        echo "===========================================================================\n";
//        var_dump($this->getRFIDConfiguration($hostname, $port));
//        sleep(2);
//        echo "===========================================================================\n";
//        var_dump($this->getPortProperties($hostname, $port));die;
    }

    public static function getRFIDConfiguration($hostip, $port)
    {
        $command = ConnectRFID::CMD_GET_RFID_CONFIGURATION;
        $res = self::handleCommandCommon($hostip, $port, $command);

        return $res;
    }

    public static function getGatewayInfo($hostip, $port)
    {
        $command = ConnectRFID::CMD_GET_CONFIGURATION;
        $res = self::handleCommandCommon($hostip, $port, $command);

        return $res;
    }

    public static function startOperation($hostip, $port)
    {
        $command = ConnectRFID::CMD_START_OPERATION;
        $res = self::handleCommandCommon($hostip, $port, $command);

        return $res;
    }

    public static function stopOperation($hostip, $port)
    {
        $command = ConnectRFID::CMD_STOP_OPERATION;
        $res = self::handleCommandCommon($hostip, $port, $command);

        return $res;
    }

    public static function getPortProperties($hostip, $port)
    {
        $command = ConnectRFID::CMD_GET_PORT_PROPERTIES;
        $res = self::handleCommandCommon($hostip, $port, $command);

        return $res;
    }

    public static function setPortProperties($hostip, $port)
    {
        $data = '';
        $command = ConnectRFID::CMD_SET_PORT_PROPERTIES;
        $res = self::handleCommandCommon($hostip, $port, $command, $data);

        return $res;
    }

    public static function setGatewayInfo($hostip, $port)
    {
        $data = '';
        $command = ConnectRFID::CMD_SET_CONFIGURATION;
        $res = self::handleCommandCommon($hostip, $port, $command, $data);

        return $res;
    }

    /**
     * For scan carton: conveyer, pallet, forklift
     * @param type $hostname
     * @param type $port
     * @return type
     */
    public static function requestTagID($hostip, $port)
    {
        $sock = ConnectRFID::createSocket($hostip, $port);
        $check = ConnectRFID::requestConnection($sock);
        if ($check) {
            $resStart = ConnectRFID::sendCommand($sock, ConnectRFID::CMD_START_OPERATION);
            if ($resStart == '0') {
                $resID = ConnectRFID::receiveData($sock);
                $result = ConnectRFID::decodePackage($resID, true);
                if($resID){
                    ConnectRFID::sendCommand($sock, ConnectRFID::CMD_STOP_OPERATION);
                }
            }
            ConnectRFID::sendCommand($sock, ConnectRFID::CMD_DISCONNECT);
            ConnectRFID::disconnect($sock);
        } else {
            return [
                'status' => false,
                'message' => 'Can not connect device'
            ];
        }

        return [
            'status' => true,
            'data' => self::parseData($result['data'], ConnectRFID::CMD_REQUEST_TAG_ID)
        ];
    }

    public static function scanRFID($hostip, $port)
    {
        $sock = ConnectRFID::createSocket($hostip, $port);
        $check = ConnectRFID::requestConnection($sock);
        if ($check) {
            $resStartOperation = ConnectRFID::sendCommand($sock, ConnectRFID::CMD_START_OPERATION);
            if ($resStartOperation == '0') {
                while (true) {
                    $resData = ConnectRFID::receiveData($sock);
                    if($resData){
                        $dataDecode = ConnectRFID::decodePackage($resData, true);
                        $dataParse = self::parseData($dataDecode['data'], ConnectRFID::CMD_REQUEST_TAG_ID);
                        // Do something to update carton to WMS or database WAP
                    }
                    if (self::checkStopOperation()) {
                        ConnectRFID::sendCommand($sock, ConnectRFID::CMD_STOP_OPERATION);
                        break;
                    }
                }
            }
            ConnectRFID::sendCommand($sock, ConnectRFID::CMD_DISCONNECT);
            ConnectRFID::disconnect($sock);
        }
    }

    /**
     * common sense
     * @param type $hostname
     * @param type $port
     * @param type $command
     * @param type $data
     * @return type
     */
    public static function handleCommandCommon($hostip, $port, $command, $data = '')
    {
        $sock = ConnectRFID::createSocket($hostip, $port);
        $check = ConnectRFID::requestConnection($sock);
        if ($check) {
            $res = ConnectRFID::sendCommand($sock, $command, $data);
            ConnectRFID::sendCommand($sock, ConnectRFID::CMD_DISCONNECT);
            ConnectRFID::disconnect($sock);
        } else {
            return [
                'status' => false,
                'message' => 'Can not connect device'
            ];
        }

        return [
            'status' => true,
            'data' => self::parseData($res, $command)
        ];
    }

    /**
     * for self::parseData
     * @param type $dataLine
     * @param type $charSeparate
     * @return type
     */
    public static function explodeDataLine($dataLine, $charSeparate)
    {
        $dataLineExp = explode($charSeparate, $dataLine);
        $val = substr($dataLine, strlen($dataLineExp[0]) + 1, strlen($dataLine) - (strlen($dataLineExp[0]) + 1));

        return [
            'key' => trim($dataLineExp[0]),
            'val' => trim($val)
        ];
    }

    /**
     * Str to array
     * @param type $data
     * @param type $command
     * @return type
     */
    public static function parseData($data, $command)
    {
        $dataRes = [];
        switch ($command) {
            case ConnectRFID::CMD_GET_CONFIGURATION:
                $dataExp = explode("\n", $data);
                foreach ($dataExp as $dataLine) {
                    if (trim($dataLine) != '') {
                        $expTemp = self::explodeDataLine($dataLine, "=");
                        $dataRes[$expTemp['key']] = $expTemp['val'];
                    }
                }
                if ($dataRes) {
                    array_shift($dataRes);
                }
                break;
            case ConnectRFID::CMD_GET_PORT_PROPERTIES:
                $dataExp = explode("=", $data);
                $properties = $dataExp[0];
                $dataExp = explode("\n", $data);
                foreach ($dataExp as $dataLine) {
                    if (trim($dataLine) != '') {
                        $expTemp = self::explodeDataLine($dataLine, "=");
                        $dataRes[$properties][$expTemp['key']] = $expTemp['val'];
                    }
                }
                if ($dataRes) {
                    array_shift($dataRes[$properties]);
                    array_pop($dataRes[$properties]);
                }
                break;
            case ConnectRFID::CMD_GET_RFID_CONFIGURATION:
                $dataExp = explode("\n", $data);
                foreach ($dataExp as $dataLine) {
                    if (trim($dataLine) != '') {
                        $expTemp = self::explodeDataLine($dataLine, "=");
                        $dataRes[$expTemp['key']] = $expTemp['val'];
                    }
                }
                break;
            case ConnectRFID::CMD_REQUEST_TAG_ID:
                $dataExp = explode("\n", $data);
                $startList = false;
                foreach ($dataExp as $key => $dataLine) {
                    if ($startList) {
                        $dataLineExp = explode("\t", $dataLine);
                        if (trim($dataLineExp[0]) != '') {
                            $dataRes[] = trim($dataLineExp[0]);
                        }
                    }
                    if (strchr($dataLine, ConnectRFID::STR_START_LIST_CARTON)) {
                        $startList = true;
                    }
                }
                break;
            default:
                break;
        }

        return $dataRes;
    }
}


