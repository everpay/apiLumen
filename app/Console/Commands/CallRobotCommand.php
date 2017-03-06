<?php
namespace App\Console\Commands;

use App\Libraries\Clients;
use App\Libraries\Helpers\SocketCLI\RobotInBound;
use App\Libraries\MyHelper;
use App\Models\Device;
use App\Models\Position;
use App\Models\RobotProcessing;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class CallRobotCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'Robot:call';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Call: "php artisan Robot:call"';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        self::callRobot();
    }


    public static function callRobot()
    {
        // get whs
        $wshId = Warehouse::getWarehouseId();
        $count = count(Position::getPositionFull());
        if($count>0){
            $positionFull = Position::getPositionFull();
            $refHexFrom = $positionFull['ref_hex'];
            $pickPositionId = $positionFull['position_id'];
            $statusRobot = Device::ACTIVE;
            $macAddress = Device::getMacAddress(Device::TYPE_ROBOT, $statusRobot);
            $deviceId =  Device::getDeviceId($macAddress);
            if (isset($wshId) && isset($refHexFrom) && isset($macAddress)) {
                $device = Device::getDeviceId($macAddress);
                $host = $device->host_ip;
                $port = $device->port;
                $token = Clients::ConnectGetTokenWmsScan();
                $request = new Request();
                $request->headers->set('Authorization', 'Bearer ' . $token);
                $url = MyHelper::getUrlSuggestLocationRobot($wshId);
                $dataWms = ['whs_id' => $wshId];
                $dataResult = Clients::ConnectWmsData('GET', $url, $dataWms,$request);
                $dataResult = json_decode($dataResult);
                $dataResult = $dataResult->data;
                $locationCode = $dataResult->loc_code;
                $position = Position::getRefHex($locationCode);
                $refHexTo = $position->ref_hex;
                $dropPosition =  Position::getPosition($refHexTo);
                if($dropPosition){
                    RobotProcessing::UpdateOrInsert($deviceId->device_id,$pickPositionId,$dropPosition->position_id,$asnId="");
                }
                $robot = new RobotInBound();
                $robot->setHost($host, $port);
                $status = $robot->goFromDocking2Putaway($macAddress, $refHexFrom, $refHexTo);
                //Position::updateStatus($data['ref_hex'], Position::STATUS_EMPTY);
               // log::info(['Called robot:' => $status]);
            }
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [

        ];
    }
}
