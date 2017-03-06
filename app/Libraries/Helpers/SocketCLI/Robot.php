<?php

namespace App\Libraries\Helpers\SocketCLI;

use App\Libraries\Helpers\SocketCLI\RobotException;

/**
 * Robote hellper
 * Example
 * $socket = Robot::createSocket('172.168.1.185', 4096);
 * $data = Robot::getOnlineRobot($socket);
 * print_r ($data);
 * sleep(1);
 * $data = Robot::start($socket, "FF7A");
 * print_r($data);
 * Robot::disconnect($socket);
 */
class Robot 
{
    /**
     * Stop
     * @send string C0
     * @response string A0
     */
    const Stop = "C0";
    
    /**
     * Start
     * @send string C1
     * @response string A1
     */
    const Start = "C1";
    
    /**
     * Slow-down
     * @send string C2
     * @response string A2
     */
    const SlowDown = "C2";
    
    /**
     * Go from D to P
     * @send string C3
     * @response string A3
     */
    const GoD2P = "C3";
    
    
    /**
     * Go from P to G
     * @send string C4
     * @response string A4
     */
    const GoP2G = "C4";
    
    /**
     * Go from G to D; at Line Y Dock X
     * @send string C5
     * @response string A5
     */
    const GoG2D = "C5";
    
    
    /**
     * Go to D (Charging)
     * @send string C6
     * @response string A6
     */
    const Go2D = "C6";
    
    /**
     * Go to M (maintenance)
     * @send string C7
     * @response string A7
     */
    const Go2M = "C7";
    
    /**
     * Where are you ?
     * @send string C8
     * @response string A8
     */
    const GetPosition = "C8";
    
    
    /**
     * What is your battery level ?
     * @send string C9
     * @response string A9
     */
    const GetBateryLevel = "C9";
    
    /**
     * What is your status information ?
     * @send string C10
     * @response string A10
     */
    const GetStatusInfo = "C10";
    
    /**
     * Go to Put-way Pallet Matrix
     * @send string C11
     * @response string A11
     */
    const Go2PPalletMatrix = "C11";
    
    /**
     * Get online robot on net work
     * @param type $socketCLI
     * @return array
     * @example: 
     * array(1) {
     *   [0] =>
     *   array(5) {
     *     'no' =>
     *     string(3) "00."
     *     'dev' =>
     *     string(3) "FFD"
     *     'eui' =>
     *     string(16) "000D6F00024CEB0B"
     *     'id' =>
     *     string(4) "FF7A"
     *     'lqi' =>
     *     string(2) "FF"
     *   }
     * }
     */
    public function getOnlineRobot($socketCLI)
    {        
        $string = "AT+NTABLE:00,FF" . chr(13);  
        socket_write($socketCLI, $string);
        
        /**
         * handle socket error
         */
        $errorcode = socket_last_error($socketCLI);      
        if ($errorcode) {
            $errormsg = socket_strerror($errorcode);
            throw new RobotException($errormsg, $errorcode);
        }
        $splitFlg = hex2bin("0d0a");
        $data = [];
        $break = "ACK";
        $socketcontent = "";
        
        while ($block = socket_read($socketCLI, 10)) {
            //for debug
            //echo $block;            
            
            $socketcontent .= $block;

            if (strpos($socketcontent, $break) !== false || 
                strpos($socketcontent, "NACK") !== false
                ) {       
                break;
            }
        }
        
        //var_dump($socketcontent);
        
        $arrTmp1 = explode($splitFlg, $socketcontent);        
        
        foreach ($arrTmp1 as $item) {
            if ($item != "") {
                $data[] = $item;
            }
        }

        if (strpos($data[3], "Length:") !== false) {
            $length = (int) str_replace("Length:", "", $data[3]);
        }

        $i = 5;
        $onlineRobot  = [];
        while ($i < $length + 5) {
            $arrRow = explode("|", $data[$i]);
            $onlineRobot [] = [
                'no' => trim($arrRow[0]),
                'dev' => trim($arrRow[1]),
                'eui' => trim($arrRow[2]),
                'id' => trim($arrRow[3]),
                'lqi' => trim($arrRow[4]),
            ];    
            $i ++;
        }

        return $onlineRobot;
        
    }
    
    /**
     * Get robot status info
     * @param type $socketCLI
     * @param type $mac
     */
    public function GetStatusInfo($socketCLI, $mac = "")
    {
        $string = "AT+UCAST:" . $mac . "=" . self::GetStatusInfo  . chr(13);
        $rs = $this->sendCommand($socketCLI, $string, "A10");
        
        if (strpos($rs[2], 'NACK') !== false) {
            throw new RobotException("Invalid robot MAC");
        }
        
        if (empty($rs[3])) {
            return false;
        }
        
        return $rs[3];
    }
    
    /**
     * C1 start
     * @param socket $socketCLI
     * @param string $mac
     * @return bool
     */
    public function start($socketCLI, $mac) 
    {
        $string = "AT+UCAST:" . $mac . "=" . self::Start  . chr(13);
        $rs = $this->sendCommand($socketCLI, $string, "A1");
        
        if (strpos($rs[2], 'NACK') !== false) {
            throw new RobotException("Invalid robot MAC");
        }
        
        return true;
    }
    
    /**
     * C0 Stop
     * @param socket $socketCLI
     * @param string $mac
     */
    public function stop($socketCLI, $mac)
    {
        $string = "AT+UCAST:" . $mac . "=" . self::Stop  . chr(13);
        $rs = $this->sendCommand($socketCLI, $string, "A0");
        
        if (strpos($rs[2], 'NACK') !== false) {
            throw new RobotException("Invalid robot MAC");
        }
        
        return true;
    }
    
     /**
     * C2 slowdown
     * @param socket $socketCLI
     * @param string $mac
     */
    public function slowDown($socketCLI, $mac)
    {
        $string = "AT+UCAST:" . $mac . "=" . self::SlowDown  . chr(13);
        $rs = $this->sendCommand($socketCLI, $string, "A2");
        
        if (strpos($rs[2], 'NACK') !== false) {
            throw new RobotException("Invalid robot MAC");
        }
        
        return true;
    }

     /**
     * C3 Go from Docking to Put away
     * @param socket $socketCLI
     * @param string $fromDockPosition
     * @param string $toPutPosition 
     * @param string $mac
     */
    public function goD2P($socketCLI , $mac, $fromDockPosition, $toPutPosition)
    {
        $string = "AT+UCAST:" . $mac . "=" . self::GoD2P 
                . "$fromDockPosition,$toPutPosition" . chr(13);
        
        $rs = $this->sendCommand($socketCLI, $string, "A3");
        
        if (strpos($rs[2], 'NACK') !== false) {
            throw new RobotException("Invalid robot MAC");
        }
        
        return true;
    }
    
    /**
     * C5 Go from Gathering to Dock
     * @param socket $socketCLI
     * @param string $mac
     */
    public function goG2D($socketCLI, $mac)
    {
        $string = "AT+UCAST:" . $mac . "=" . self::GoG2D  . chr(13);
        $rs = $this->sendCommand($socketCLI, $string, "A5");
        
        if (strpos($rs[2], 'NACK') !== false) {
            throw new RobotException("Invalid robot MAC");
        }
        
        return true;
    }

    /**
     * Create socket client to specify host
     * @param string $hostname
     * @param int $port
     * @return socket resource
     */
    public function createSocket($hostname, $port)
    {
        
        if ($hostname == "" || $port == "") {
            throw new RobotException("Host and port are not config yet", 0);
        }
        
        $socketCLI = socket_create(AF_INET, SOCK_STREAM, 0);
        socket_connect($socketCLI, $hostname, $port);
        
        if ($socketCLI === false) {
            $errorcode = socket_last_error($socketCLI);
            $errormsg = socket_strerror($errorcode);

            throw new RobotException($errormsg, $errorcode);
        }
        socket_clear_error($socketCLI);
        
        return $socketCLI;
    }
   
    /**
     * Disconnect host
     * @param type $socketCLI
     */
    public function disconnect($socketCLI)
    {
        socket_close($socketCLI);
    }
    
   /**
    * Send command to Zigbee gateway   
    * @param type $socketCLI
    * @param type $string
    * @param type $break
    * @param string $strSocketContent reference incase need to parse string response
    * @return array
    */
    public function sendCommand($socketCLI, $string, $break, &$strSocketContent = "") 
    {   
        sleep(1);
        socket_write($socketCLI, $string);
        
        /**
         * handle socket error
         */
        $errorcode = socket_last_error($socketCLI);
        if ($errorcode) {
            $errormsg = socket_strerror($errorcode);
            throw new RobotException($errormsg, $errorcode);
        }
        $splitFlg = hex2bin("0d0a");
        $count = 0;
        $data = [];
        $tmpData = "";
        $idx = "";

        $tmpBlock = "";
        $idxData = 0;
        $char1Flg = false;
        
        while ($block = socket_read($socketCLI, 2)) {
            
            if (strlen($block) == 1) {
                $block = mb_substr($tmpBlock, 1,1) . $block;
                $char1Flg = true;
            } else {
                $tmpBlock = $block;
                $char1Flg = false;
            }
            
            //for debug
            //echo $block;
            //echo $block . "--" . bin2hex($block) . "\n";

            if ($block === $splitFlg) {
                $count ++;

                if ($count % 2 == 1){
                    $tmpData = "";
                    continue;
                }
                if ($count % 2 == 0) {
                    $data[$idxData] = $tmpData;
                    $idxData ++;
                }
            }
            else {

                if ($char1Flg) {
                    $tmpData .= mb_substr ($block, 1,1); 
                }
                else {
                    $tmpData .= $block;
                }
            }    
            $lastItem = $idxData - 1;

            if ($lastItem > 0) {
                if (strpos($data[$lastItem], $break) !== false || 
                    strpos($data[$lastItem], "NACK") !== false
                    ) {       
                    break;
                }
            }            
        }
        
        return $data;
    }
}