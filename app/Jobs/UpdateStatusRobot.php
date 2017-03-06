<?php

namespace App\Jobs;

use App\Models\AsnProcessing;
use App\Models\Warehouse;
use Log;
use App\Models\Position;
use App\Models\Device;
use App\Models\RobotProcessing;
use App\Libraries\MyHelper;
use App\Libraries\Clients;
use Illuminate\Http\Request;

class UpdateStatusRobot extends Job
{
    protected $mac;

    /**
     * Create a new job instance.
     *
     * @param $mac
     */
    public function __construct($mac)
    {
        $this->mac = $mac;
    }

    /**
     * Execute the job.
     *
     * @param
     */
    public function handle()
    {
        $mac = $this->mac;

        if(isset($mac)){
            $device = Device::getDeviceId($mac['data']['mac']);
            $deviceId = $device->device_id;
            $wshId = Warehouse::getWarehouseId();
            if($mac['data']['status'] == Device::STATUS_ROBOT_DROPPED){
                $cusId = AsnProcessing::getCusId();
                $statusDrop  = RobotProcessing::DROPPED;
                RobotProcessing::updatePositionStatus($deviceId,$statusDrop);
                // update Position
                $position = RobotProcessing::getRobotProcessing($deviceId);
                $positionDropId = $position->drop_position_id;
                $positionPickId = $position->pick_position_id;
                $positionSku = Position::getRefHexPositionId($positionPickId);
                if($positionDropId){
                    Position::updateStatusPositionId($positionDropId,Position::STATUS_RESERVED,$positionSku->sku_num);
                    Position::updateStatusPositionId($positionPickId,Position::STATUS_EMPTY,null);
                    $token = Clients::ConnectGetTokenWmsScan();
                    $request = new Request();
                    $request->headers->set('Authorization', 'Bearer ' . $token);
                    // Put Pallet
                    $urlWms = MyHelper::getUrlPutAwayPutPallet($wshId, $cusId);
                    $positionDrop = Position::getRefHexPositionId($positionDropId);
                    $refHex = $positionDrop->ref_hex;
                    $loc =Position::getPosition($refHex);
                    $dataWms = ['whs_id' => $wshId, 'cus_id' => $cusId,'loc-code'=>$loc->code,'pallet-rfid'=>$loc->rfid];
                    $data = Clients::ConnectWmsData('PUT',$urlWms, $dataWms,$request);
                    log::info('Dropped:' , ['data' => $data]);

                }
            }elseif($mac['data']['status'] == Device::STATUS_ROBOT_PICKING){
                $status = Device::ACTIVE;
                $statusPick = RobotProcessing::PICKING;
                RobotProcessing::updatePositionStatus($deviceId,$statusPick);

                // update Position
                $position = RobotProcessing::getRobotProcessing($deviceId);
                $positionPickId = $position->pick_position_id;
                $data = Position::updateStatusPositionIdEt($positionPickId,Position::STATUS_EMPTY);
                log::info(['data' => $data]);
            }
            Device::updateStatusDevice($mac['data']['mac'], $status);
        }
    }
}
