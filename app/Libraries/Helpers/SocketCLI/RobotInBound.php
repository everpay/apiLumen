<?php

namespace App\Libraries\Helpers\SocketCLI;

class RobotInBound extends Robot
{
    public $host = "";
    public $port = "";
    public $fakeData = true;

    public function __construct($host = "", $port = "") 
    {
        $this->fakeData = env('FAKE_SOKET_DATA', true);
        $this->host = $host;
        $this->port = $port;
    }
    
    /**
     * Chang zinbee gateway setting
     * @param string $host
     * @param string $port
     */
    public function setHost($host = "192.168.0.106", $port = "4096") 
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * Get robots by status
     * 1: Ready
     * 2: Charging
     * 3: Working
     * 4: Picked
     * 5: Dropped
     * @return array
     */
    
    public function getRobotByStatus($status = "Ready")
    {
        if ($this->fakeData) {
            return [
                '000D6F000C469B5B'
            ];
        }
        
        $socketCLI = $this->createSocket($this->host, $this->port);
        /**
         * 1 get online robot
         * 2 check evey robot
         *   which one have status = input status  them add to array
         * 3 return array
         */
        $onlineRobots = $this->getOnlineRobot($socketCLI);
        if (count ($onlineRobots) == 0) {
            return [];
        }
        
        $result = [];
        $status = "Ready";
        
        foreach ($onlineRobots as $robot) {
            sleep(1);
            $mac = $robot['eui'];
            
            $rs = $this->GetStatusInfo($socketCLI, $mac);
            if (strpos($rs, $status)) {
                $result[] = $mac;
            }
        }        
        $this->disconnect($socketCLI);
        return ['000D6F000C469B5B'];
        return $result;
    }
    
    /**
     * Go from docking to putaway
     * @param type $mac
     * @param type $fromDockPosition
     * @param type $toPutPosition
     * @return boolean
     */
    public function goFromDocking2Putaway($mac = "", 
        $fromDockPosition = "",             
        $toPutPosition = "")
    {
        if ($this->fakeData) {
            return true;
        }
        
        try {
            //debug:
            //var_dump($this->host, $this->port); exit;
            $socketCLI = $this->createSocket($this->host, $this->port);     
            $rs = $this->goD2P($socketCLI, $mac, $fromDockPosition, $toPutPosition);
            $this->disconnect($socketCLI);
            
        } catch (RobotException $ex) {
            return $ex->getMessage();
        }
        
        return true;
    }
}
