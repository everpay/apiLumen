<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/21/2016
 * Time: 2:34 PM
 */


namespace App\Libraries\Helpers\SocketCLI;
use App\Console\Commands\ControlMultiGateCommand;
use App\Models\LogEvent;
use Illuminate\Support\Facades\Redis;

/**
 * Class SocketClients
 */

class ConnectRFID{

    const HOSTNAME = '172.168.1.139';
    const PORT = '5000';
    const HEAD_DATA = 6;
    const TAIL_DATA = 6;
    const HEAD_IN_DATA = 6;
    const TAIL_IN_DATA = 2;
    const LEN_ACK = 2;
    const LEN_BUFF = 1024;

    const CMD_CONNECTING_REQUEST = "01";
    const CMD_GET_CONFIGURATION = "02";
    const CMD_SET_CONFIGURATION = "03";
    const CMD_GET_RFID_CONFIGURATION = "04";
    const CMD_SET_RFID_CONFIGURATION = "05";
    const CMD_START_OPERATION = "06";
    const CMD_STOP_OPERATION = "07";
    const CMD_GET_PORT_PROPERTIES = "08";
    const CMD_SET_PORT_PROPERTIES = "09";
    const CMD_REQUEST_TAG_ID = "0A";
    const CMD_DISCONNECT = "0B";
    const CMD_SET_GPO = "22";
    const CON_SUCCESS = '0';
    const CON_FAIL = '1';
    const HEADER_SEND = 'e5';
    const HEADER_REC = 'e4';
    const STR_SEP_HEADER = '***';
    const STR_START_LIST_CARTON = 'EPC:';
    const TIME_RE_CONN = 4;
    const TIMEOUT_RELEASE_READER = 5;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public static function createSocket($hostip, $port, $reConnect = 0)
    {
        try {
            $socketCLI = socket_create(AF_INET, SOCK_STREAM, 0);
            socket_connect($socketCLI, $hostip, $port);
            LogEvent::saveLog(
                LogEvent::CALL_DEVICE_SUCCESSFUL,
                null,
                null,
                ['ip' =>$hostip,'port' => $port, 'device_name' => 'RFID reader']
            );
            return $socketCLI;
        } catch (\Exception $e) {
            // If cannot connect device => re-connect after 5 seconds
            sleep(2);
            if ($reConnect < self::TIME_RE_CONN) {
                $reConnect++;
                $sock = self::createSocket($hostip, $port, $reConnect);

                return $sock;
            }
            // Write log "Can not connect to device with host_ip and port"
            LogEvent::saveLog(
                LogEvent::CALL_DEVICE_FAILD,
                null,
                null,
                ['ip' =>$hostip,'port' => $port, 'device_name' => 'RFID reader']
            );
        }
    }

    public static function logConnectionFail($hostip, $port)
    {
        $dir = str_replace("\\", "/", base_path() . "/storage/logs/log-scan.txt");
        $file = fopen($dir, "a");
        $str = "Cannot connect device: host_ip => " . $hostip . " and port => " . $port . PHP_EOL;
        $str .= "------------------------------------------------------------------" . PHP_EOL;
        fwrite($file, $str);
        fclose($file);
    }

    public static function disconnect($socketCLI)
    {
        socket_close($socketCLI);
    }

    public static function requestConnection($socketCLI)
    {
        $encodeCommand = self::encodePackage(self::CMD_CONNECTING_REQUEST);
        socket_send($socketCLI, $encodeCommand, strlen($encodeCommand), 0);
        $res = socket_read($socketCLI, self::LEN_BUFF);
        $check = self::decodePackage([$res]);
        if ($check['ack'] == self::CON_SUCCESS) {
            return true;
        }
        return false;
    }

    public static function sendCommand($socketCLI, $command, $data = '')
    {
        $encodeCommand = self::encodePackage($command, $data);
        socket_send($socketCLI, $encodeCommand, strlen($encodeCommand), 0);
        $resData = self::receiveData($socketCLI);
        if ($command == self::CMD_REQUEST_TAG_ID) {
            $result = self::decodePackage($resData, true);
        } else {
            $result = self::decodePackage($resData);
        }
        if (!$result['data']) {
            return $result['ack'];
        }
        return $result['data'];
    }

    public static function receiveData($socketCLI)
    {
        $dataRep = [];
        while (true) {
            $buff = socket_read($socketCLI, self::LEN_BUFF);
            if($buff){
                array_push($dataRep, $buff);
                if (self::isFinalPackage($buff)) {
                    break;
                }
            }
        }

        return $dataRep;
    }

    public static function isFinalPackage($package)
    {
        $packHex = bin2hex($package);
        $strTruncate = substr($packHex, strlen($packHex) - 6, 4);
        if (hexdec($strTruncate) == 1) {
            return true;
        }
        return false;
    }

    public static function decodePackage($codePackage = [], $isCMTagID = false)
    {
        $dataPackageDecode = '';
        $dataPackageHex = '';
        //calc package in hex
        foreach ($codePackage as $key => $code) {
            //convert subpackage to hex code
            $packHex = bin2hex($code);
            //calculate length subpackage
            $lenData = strlen($packHex) - self::HEAD_DATA - self::TAIL_DATA;
            //get real data of subpackage
            $realBuffData = substr($packHex, self::HEAD_DATA, $lenData);
            $dataPackageHex .= $realBuffData;
        }
        //calculate length package
        $lenData = strlen($dataPackageHex) - self::HEAD_IN_DATA - self::TAIL_IN_DATA;
        //get real data of package
        $realData = substr($dataPackageHex, self::HEAD_IN_DATA, $lenData);
        if ($isCMTagID) {
            $ack = '';
            $metalData = $realData;
        } else {
            $ack = substr($realData, 0, self::LEN_ACK);
            $metalData = substr($realData, 2, strlen($realData) - self::LEN_ACK);
        }
        if ($metalData != '00' && $metalData != '01') {
            for ($i = 0; $i < strlen($metalData); $i++) {
                $temp = substr($metalData, $i, 2);
                $dataPackageDecode .= chr(hexdec($temp));
                $i++;
            }
        } else {
            $dataPackageDecode = hexdec($metalData);
        }


        return [
            'data' => $dataPackageDecode,
            'ack' => hexdec($ack)
        ];
    }

    public static function calLenFrame($len)
    {
        $c = $len / 2;
        if ($len % 2 != 0) {
            $c += 1;
        }
        $lenHex = str_pad(dechex($c), 4, '0', STR_PAD_LEFT);

        return $lenHex;
    }

    public static function convertDataToHex($data)
    {
        $dataHex = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $temp = substr($data, $i, 1);
            $dataHex .= dechex(ord($temp));
        }

        return $dataHex;
    }

    public static function encodePackage($command, $data = '')
    {
        $header = self::HEADER_SEND;
        $truncate = '0001';
        $dataEncode = '';
        if ($data) {
            $dataEncode = self::convertDataToHex($data);
        }
        //start child frame
        $lenDecChild = strlen($dataEncode) + strlen($command) + 4 + 2;
        $lenHexChild = self::calLenFrame($lenDecChild);
        $childStrToCheckSum = $command . $lenHexChild . $dataEncode;
        $sckChild = self::checkSum($childStrToCheckSum);
        $childFrame = $command . $lenHexChild . $dataEncode . $sckChild;
        //end child frame
        //start parent frame
        $lenDecParent = strlen($childFrame) + 4 + 4 + 2;
        $lenHexParent = self::calLenFrame($lenDecParent);
        $parentStrToCheckSum = $lenHexParent . $childFrame . $truncate;
        $sckParent = self::checkSum($parentStrToCheckSum);
        $parentFrame = hex2bin($header
            . $lenHexParent
            . $command
            . $lenHexChild
            . $dataEncode
            . $sckChild
            . $truncate
            . $sckParent);
        //end parent frame

        return $parentFrame;
    }

    public static function checkSum($data)
    {
        $sum = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            $temp = substr($data, $i, 2);
            $sum += hexdec($temp);
            $i++;
        }
        $sum = ~$sum + 1;

        return bin2hex(chr($sum));
    }
}