<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/17/2016
 * Time: 10:05 AM
 */

namespace App\Http\Controllers\Wap;

use App\Console\Commands\ControlMultiGateCommand;
use App\Http\Controllers\Controller;
use App\Libraries\Clients;
use App\Libraries\MyHelper;
use App\Models\AsnProcessing;
use App\Models\AsnProcessingFactory;
use App\Models\Device;
use App\Models\Location;
use App\Models\PalletProcessing;
use App\Models\RecProcessing;
use App\Models\WpProcessing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Process\Process;

class MultiGateController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /*
     * work like a leader to control all running conveyor, pallet gate
     */
    public static function controlGate($type)
    {
        switch ($type) {
            case ControlMultiGateCommand::CONVEYOR:
                $command = 'php /var/www/source/backend/artisan ChildProcess:scan-carton';
                //$command = 'php artisan ChildProcess:scan-carton';
                break;
            case ControlMultiGateCommand::PALLET:
                $command = 'php /var/www/source/backend/artisan ChildProcess:scan-pallet';
                //$command = 'php artisan ChildProcess:scan-pallet';
                break;
            case ControlMultiGateCommand::RACK:
                $command = 'php /var/www/source/backend/artisan ChildProcess:scan-rack';
                //$command = 'php artisan ChildProcess:scan-rack';
                break;
            case ControlMultiGateCommand::PALLET_OUTBOUND:
                $command = 'php /var/www/source/backend/artisan ChildProcess:scan-pallet-outbound';
                //$command = 'php artisan ChildProcess:scan-pallet-outbound';
                break;
            default:
                die('This type gate not exist!');
                break;
        }
        $arrGateProcessing = [];
        $arrProcess = [];
        while (true) {
            $arrCompare = self::compareDataReceivingFromWMS($arrGateProcessing, $type);
            if ($arrCompare['dataNew']['status'] || $arrCompare['dataRemove']['status']) {
                //Create new process in array
                if ($arrCompare['dataNew']['status']) {
                    foreach ($arrCompare['dataNew']['data'] as $val) {
                        $cmd = $command;
                        switch ($type) {
                            case ControlMultiGateCommand::CONVEYOR:
                                $cmd .= " " . $val['asn_hdr_id'];
                                $cmd .= " " . $val['asn_dtl_id'];
                                $cmd .= " " . $val['item_id'];
                                $cmd .= " " . $val['ctnr_id'];
                                $cmd .= " " . $val['cus_id'];
                                $cmd .= " " . $val['checker_id'];
                                //command for RFID reader 01
                                $cmd_reader_first = $cmd . " " . $val['rfid_reader_1'];
                                $cmd_reader_first .= " FIRST";
                                //command for RFID reader 02
                                $cmd_reader_second = $cmd . " " . $val['rfid_reader_2'];
                                $cmd_reader_second .= " SECOND";
                                $objectProcess = [
                                    'RFID_FIRST' => new Process($cmd_reader_first),
                                    'RFID_SECOND' => new Process($cmd_reader_second)
                                ];
                                break;
                            case ControlMultiGateCommand::PALLET_OUTBOUND:
                            case ControlMultiGateCommand::PALLET:
                                $cmd .= " " . $val['whs_id'];
                                $cmd .= " " . $val['host_ip'];
                                $cmd .= " " . $val['port'];
                                $objectProcess = new Process($cmd);
                                break;
                            case ControlMultiGateCommand::RACK:
                                $cmd .= " " . $val['rfid_reader_id'];
                                $cmd .= " " . $val['processor_id'];
                                $objectProcess = new Process($cmd);
                                break;
                            default:
                                break;
                        }
                        $arrProcess[] = $objectProcess;
                        $arrGateProcessing[] = $val;
                    }
                    foreach ($arrProcess as $process) {
                        if ($type == ControlMultiGateCommand::CONVEYOR) {
                            if (!$process['RFID_FIRST']->isStarted()) {
                                $process['RFID_FIRST']->start();
                            }
                            if (!$process['RFID_SECOND']->isStarted()) {
                                $process['RFID_SECOND']->start();
                            }
                        } else {
                            if (!$process->isStarted()) {
                                $process->start();
                            }
                        }
                        usleep(100000);
                    }
                }
                //Remove gate from $arrGateProcessing
                if ($arrCompare['dataRemove']['status']) {
                    foreach ($arrGateProcessing as $key => $val) {
                        if (in_array($val, $arrCompare['dataRemove']['data'])) {
                            unset($arrGateProcessing[$key]);
                            if ($type == ControlMultiGateCommand::CONVEYOR) {
                                $pidFirst = $arrProcess[$key]['RFID_FIRST']->getPid();
                                exec('kill -9 ' . $pidFirst);
                                exec('kill -9 ' . ($pidFirst + 1));
                                exec('kill -9 ' . ($pidFirst + 2));
                                exec('kill -9 ' . ($pidFirst + 3));
                                exec('kill -- -$(ps -o pgid= ' . $pidFirst . ' | grep -o [0-9]*)');
                                exec('kill -- -$(ps -o pgid= ' . ($pidFirst + 1) . ' | grep -o [0-9]*)');
                                /*$arrProcess[$key]['RFID_FIRST']->stop();
                                $arrProcess[$key]['RFID_SECOND']->stop();*/
                            } else {
                                $pid = $arrProcess[$key]->getPid();
                                exec('kill -9 ' . $pid);
                                exec('kill -9 ' . ($pid + 1));
                                exec('kill -- -$(ps -o pgid= ' . $pid . ' | grep -o [0-9]*)');
                                /*$arrProcess[$key]->stop();*/
                            }
                            usleep(100000);
                        }
                    }
                }
            } else {
                //have no any change
                echo "Have no change => watching all process!!!!!!!!!!!\n";
                var_dump($arrGateProcessing) . "\n";
                sleep(1);
            }
        }
    }

    /*
     * Get array receiving gate from WMS and compare with current array to find
     * new array and remove array
     *
     */
    public static function compareDataReceivingFromWMS($arrGateProcessing, $type)
    {
        $arrNew = [];
        $arrRemove = [];
        //get array ASN receiving from WMS
        $arrWMS = self::getReceivingGateWMS($type);
        //get array remove gate
        foreach ($arrGateProcessing as $val) {
            if (!in_array($val, $arrWMS)) {
                $arrRemove[] = $val;
            }
        }
        //get array new gate
        foreach ($arrWMS as $val) {
            if (!in_array($val, $arrGateProcessing)) {
                $arrNew[] = $val;
            }
        }
        //return array new receiving with new gate
        return [
            'dataNew' => [
                'status' => count($arrNew) > 0 ? true : false,
                'data' => $arrNew
            ],
            'dataRemove' => [
                'status' => count($arrRemove) > 0 ? true : false,
                'data' => $arrRemove
            ]
        ];
    }

    /*
     * get array receiving gate from WMS via API
     */
    public static function getReceivingGateWMS($type)
    {
        $arrWMS = [];
        switch ($type) {
            case ControlMultiGateCommand::CONVEYOR:
                $arrWMS = self::getAllProcessingConveyor();
                break;
            case ControlMultiGateCommand::PALLET:
                $arrWMS = self::getAllReceivingPallet();
                break;
            case ControlMultiGateCommand::PALLET_OUTBOUND:
                $arrWMS = self::getAllReceivingGatewayOutbound();
                break;
            case ControlMultiGateCommand::RACK:
                $arrWMS = self::getAllActiveForklift();
                break;
            default:
                break;
        }

        return $arrWMS;
    }

    /*
     *Get all receiving conveyor
     */
    public static function getAllProcessingConveyor()
    {
        $query = AsnProcessing::leftJoin('asn_detail_processing as adp', 'adp.asn_processing_id', '=', 'asn_processing.asn_processing_id')
            ->leftJoin('rec_processing', 'rec_processing.asn_detail_processing_id', '=', 'adp.asn_detail_processing_id')
            ->where('rec_processing.status', '=', RecProcessing::STATUS_RECEIVING)
            ->select(
                'asn_processing.asn_id as asn_hdr_id',
                'asn_processing.ctnr_id',
                'adp.asn_dtl_id',
                'adp.item_id',
                'asn_processing.cus_id',
                'rec_processing.rfid_reader_1',
                'rec_processing.rfid_reader_2',
                'rec_processing.checker_id'
            )
            ->get();
        if (count($query) > 0) {
            return $query->toArray();
        } else {
            return [];
        }
    }

    /*
     * get all receiving putaway gate
     */
    public static function getAllReceivingPallet()
    {
        $arrRes = [];
        $query = AsnProcessing::where('status', AsnProcessing::STATUS_RECEIVING)
            ->get();
        if (count($query) > 0) {
            $infoGateScanPallet = Device::getDetailDevice('RFID_GW_01');
            if (!empty($infoGateScanPallet)) {
                $arrRes[] = [
                    'whs_id' => $infoGateScanPallet['warehouse_id'],
                    'host_ip' => $infoGateScanPallet['host_ip'],
                    'port' => $infoGateScanPallet['port']
                ];
            }
        }

        return $arrRes;
    }

    /*
     * get all receiving putaway gate outbound
     */
    public static function getAllReceivingGatewayOutbound()
    {
        $arrRes = [];
        $query = WpProcessing::where('status', WpProcessing::STATUS_PICKING)
            ->get();
        if (count($query) > 0) {
            $infoGateScanPallet = Device::getDetailDevice('RFID_GW_02');
            if (!empty($infoGateScanPallet)) {
                $arrRes[] = [
                    'whs_id' => $infoGateScanPallet['warehouse_id'],
                    'host_ip' => $infoGateScanPallet['host_ip'],
                    'port' => $infoGateScanPallet['port']
                ];
            }
        }

        return $arrRes;
    }

    public static function getAllActiveForklift()
    {
        $query = PalletProcessing::where('status', '!=', PalletProcessing::STATUS_DISCONNECT)
            ->select(
                'pallet_processing.rfid_reader_id',
                'pallet_processing.processor_id'
            )
            ->get();
        if (count($query) > 0) {
            return $query->toArray();
        } else {
            return [];
        }
    }

}
