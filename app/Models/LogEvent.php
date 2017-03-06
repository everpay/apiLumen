<?php

namespace App\Models;

class LogEvent extends LogEventFactory
{
    protected $table = 'log_event';
    protected $primaryKey = 'log_event_id';

    const LOGIN_SUCCESS = 'LE001';
    const LOGIN_FAILD = 'LE002';
    const LOG_OUT = 'LE003';
    const CREATE_USER = 'LE004';
    const EDIT_USER = 'LE005';
    const DELETE_USER = 'LE006';

    const CALL_WMS_SUCCESSFUL = 'LE007';
    const CALL_WMS_FAILD = 'LE008';
    const CALL_DEVICE_SUCCESSFUL = 'LE009';

    const CALL_DEVICE_FAILD = 'LE010';
    const PROCESS_ASN = 'LE011';
    const VERIFY_SKU = 'LE012';

    const SCAN_CARTON_ON_RF_READER_1 = 'LE013';
    const SCAN_CARTON_ON_RF_READER_2 = 'LE014';
    const SCAN_PALLET_INBOUND = 'LE015';

    const PUT_PALLET_TO_RACK = 'LE016';
    const PROCESS_WAVE_PICK = 'LE017';
    const SCAN_PALLET_OUTBOUND = 'LE018';

    const ASSIGN_CARTON_TO_ORDER = 'LE019';
    const ASSIGN_CARTON_TO_PALLET = 'LE020';

    const DELETE_CARTON  = 'LE021';
    const SET_DAMAGE_FOR_CARTON = 'LE022';


    public static function getLogEventByCode($code)
    {
        return self::where('code', $code)->first();
    }


}
