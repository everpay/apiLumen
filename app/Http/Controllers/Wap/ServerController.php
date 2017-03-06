<?php

namespace App\Http\Controllers\Wap;

use App\Jobs\CallRobot;
use App\Jobs\UpdateStatusRobot;
use App\Libraries\Helpers\SocketCLI\RobotInBound;
use App\Models\Location;
use App\Models\User;
use App\Libraries\MyHelper;
use App\Libraries\Clients;
use Hash;
use JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Mockery\CountValidator\Exception;
use App\Models\Position;
use App\Models\Device;
use Illuminate\Support\Facades\DB;
use App\Models\RobotProcessing;

class ServerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function positionUpdate()
    {
        try {
            $mac = Input::all();
            if (isset($mac)) {
                $positionId = Position::getPositionId($mac['data']['position']);
                if ($positionId) {
                    $device = Device::updatePositionDevice($mac['data']['mac'], $positionId);
                }
            }
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }

    }


    public function statusUpdate()
    {
        try {
            $mac = Input::all();
            dispatch((new UpdateStatusRobot($mac))->onQueue('update_status_robot'));
            return MyHelper::response(true, "successful", 200);
        } catch (\Exception $e) {
            return MyHelper::response(false, $e->getMessage(), 400);
        }
    }

    public static function getStatusRobot($avaiable)
    {
        $robot = new RobotInBound();
        $status = $robot->getRobotByStatus($avaiable);
        return $status;

    }

    public static function setLocationRobot($host, $port, $mac, $fromDockPosition, $toPutPosition)
    {
        $robot = new RobotInBound();
        $robot->setHost($host, $port);
        $status = $robot->goFromDocking2Putaway($mac, $fromDockPosition, $toPutPosition);

        return $status;

    }



}

