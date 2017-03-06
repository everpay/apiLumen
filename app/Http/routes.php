<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
use App\Libraries\Helpers\SocketCLI\Robot;
use App\Libraries\Helpers\SocketCLI\RobotInBound;

$app->get('/test', function (Illuminate\Http\Request $request) {
    dd($request->header('Authorization'));
});
define('PREFIX', 'api/v1');

$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\Auth',
], function ($app) {
    $app->post('login', 'AuthController@postLogin');
    $app->post('logout', 'AuthController@postLogout');
    $app->get('whs/{whs_id}/user-checker-list', 'AuthController@getCheckerList');
    $app->put('whs/{whs_id}/check-checker-receiving/{id}', 'AuthController@getCheckCheckerRv');

    /**
     * Example to call robot
     * $app->post('test-robot', function () {
     * $rbib = new RobotInBound();
     * $host = '4096';
     * $port = '192.168.0.101';
     * $rbib->setHost($host, $port);
     * $mac = "000D6F000C469B5B";
     * $rbib->goFromDocking2Putaway($mac, "", "");
     *
     * });
     */

});

$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\Wap',
    //'middleware' => 'check-token',
], function ($app) {
    $app->post('/server/update-position', 'ServerController@positionUpdate');
    $app->post('/server/update-status', 'ServerController@statusUpdate');
});
$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\Wap',
    //'middleware' => 'check-token',
], function ($app) {
    $app->post('whs/{whs_id}/cus/{cus_id}/add-virtual-carton', 'ScanerController@updateCarton');
    $app->post('whs/{whs_id}/scan-pallet', 'ScanerController@scanPallet');
    $app->get('whs/{whs_id}/scan-rac', 'ScanerController@scanRac');
    $app->get('whs/{whs_id}/cus/{cus_id}/scan-rack', 'ScanerController@scanRack');

});


$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\Wap',
    //'middleware' => 'check-token',
], function ($app) {
    $app->get('/whs/{whs_id}/asns', 'AsnController@asnList');
    $app->post('/whs/{whs_id}/asns/update-status', 'AsnController@updateStatusRV');
    $app->get('/whs/{whs_id}/asns-list-detail', 'AsnController@asnListDetail');
    $app->get('whs/{whs_id}/cus-list', 'AsnController@cusList');
    $app->get('whs/{whsId}/containers', 'AsnController@containersList');
    $app->get('/whs/{whs_id}/cus/{cus_id}/asn-history/{asn_dtl_id}', 'AsnController@asnHistory');
    $app->get('whs/{whs_id}/cus/{cus_id}/asns/{asn_id}/containers/{ctnr_id}', 'AsnController@detailAsn');
    $app->put('whs/{whs_id}/cus/{cus_id}/goods-receipts', 'AsnController@updateCarton');
    $app->post('whs/{whs_id}/cus/{cus_id}/position/update-status', 'AsnController@updateStatus');
    $app->post('whs/{whs_id}/cus/{cus_id}/scan-pallet', 'AsnController@scanPallet');
    $app->get('whs/{whs_id}/cus/{cus_id}/location/staging/suggest-pallet', 'ServerController@suggestLocationRobot');
    $app->post('whs/{whs_id}/cus/{cus_id}/location/goods-receipts/put-away/pick-pallet', 'AsnController@pickPutAway');
    $app->get('whs/{whs_id}/cus/{cus_id}/location/rack/get-empty-location', 'AsnController@suggestLocationPallet');
    $app->put('whs/{whs_id}/cus/{cus_id}/asn-detail/{asn_dtl_id}/complete-sku', 'AsnController@asnDetail');
    $app->put('whs/{whs_id}/location/rack/put-pallet', 'AsnController@putPallet');
    $app->get('location/list', 'AsnController@listLocation');
    $app->get('whs/{whs_id}/cus/{cus_id}/location/rack/get-location-environs', 'AsnController@getEmptyLocationEnvironsRack');
    $app->put('whs/{whs_id}/cus/{cus_id}/carton/set-damage-carton', 'AsnController@setDamageCarton');
    $app->put('whs/{whs_id}/cus/{cus_id}/carton/delete-virtual-carton', 'AsnController@deleteVirtualCarton');
    $app->get('whs/{whs_id}/cus/{cus_id}/asns/{asn_id}/containers/{ctnr_id}/carton/list-virtual-carton', 'AsnController@listVirtualCarton');
    $app->put('whs/{whs_id}/cus/{cus_id}/asns/{asn_id}/containers/{ctnr_id}/goods-receipts/complete-good-receipt', 'AsnController@completeGoodReceipt');
    $app->post('whs/{whs_id}/cus/{cus_id}/asns/{asn_id}/containers/{ctnr_id}/goods-receipts/create-good-receipt', 'AsnController@createGoodReceipt');
    $app->get('/whs/{whs_Id}/cus/{cus_Id}/asn/status/{asn_dtll_Id}', 'AsnController@showAsnsDetail');
    $app->get('/whs/{whs_Id}/gate-code-list', 'AsnController@rfidList');
    $app->get('/whs/{whs_Id}/gate-code-receiving', 'AsnController@gateCodeReceivingUser');
    $app->get('/whs/{whs_Id}/get-carton-asn', 'AsnController@getCartonAsn');
    $app->get('/whs/{whs_Id}/get-asn-processing', 'AsnController@getAsnProcessing');

    $app->get('/whs/{whs_id}/reader-list', 'AsnController@getReaderList');
    $app->get('/whs/{whs_id}/pallet/{rfid}/location/rack/get-empty-locations', 'AsnController@getEmptyLocations');
    $app->post('/whs/{wh_id}/pallet/{pallet_rfid}/drop-location', 'AsnController@dropLocation');
    $app->get('/whs/{wh_id}/pallet/{pallet_rfid}/drop-location', 'AsnController@getDropLocation');
    $app->get('/whs/{whs_id}/refesh-sku-verification-page/{checker-id}', 'AsnController@getDataChecker');
    $app->put('/whs/{whs_id}/stop-asn/{checker-id}', 'AsnController@stopAsnToOh');

});

$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\Socket',
    //'middleware' => 'check-token'
], function ($app) {
    /*$app->get('get-rfid-config', 'SocketRfidController@getRFIDConfiguration');
    //$app->get('get-gateway-info', 'SocketRfidController@call');
    $app->post('start-operation', 'SocketRfidController@startOperation');
    $app->post('stop-operation', 'SocketRfidController@stopOperation');
    $app->post('get-port-properties', 'SocketRfidController@getPortProperties');
    $app->post('get-tag-id', 'SocketRfidController@requestTagID');

    $app->get('set-gateway-info', 'SocketRfidController@setGatewayInfo');
    $app->post('set-port-properties', 'SocketRfidController@setPortProperties');*/

    $app->get('test-socket', 'SocketRfidController@call');

    //scan palet
    $app->get('test-scan-pallet', 'SocketRfidController@callScanPallet');

    //conveyer
    $app->get('test-scan-carton', 'SocketRfidController@callScanCarton');
    $app->get('test-scan-rac', 'SocketRfidController@callScanRac');

    $app->get('call-call-rfId-config', 'SocketRfidController@callGetRfIdConfig');
});


$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\Wap',
], function ($app) {
    $app->get('/{whs_id}/wave', 'WaveController@wavePickList');
    $app->get('/{whs_id}/location/put-away/get-empty-location', 'WaveController@getEmptyLocationPA');
    $app->get('/{whs_id}/order', 'WaveController@getOrderList');
    $app->get('/{whs_id}/order/{order_id}', 'WaveController@getOrderId');
    $app->get('whs/{whs_id}/wave/sku/{wv_dtl_id}', 'WaveController@wavePickDetail');
    $app->get('/{whs_id}/order/sku/{order_dtl_id}', 'WaveController@getDetailSku');
    $app->put('/{whs_id}/order/sku/{order_dtl_id}/carton', 'WaveController@assignFullCarton');
    $app->get('/{whs_id}/order/sku/{ctn_rfid}/carton', 'WaveController@assignFullCartonRfid');
    $app->put('/{whs_id}/order/sku/{order_dtl_id}/cartons', 'WaveController@assignFullCartons');

    $app->put('/{whs_id}/order/sku/{order_dtl_id}/pieces', 'WaveController@pickPiece');
    $app->get('/{whs_id}/order-shipping', 'WaveController@getOrderShipping');
    $app->get('/{whs_id}/location-put-away-list', 'WaveController@locationPutAwayList');
    $app->get('/{whs_id}/location-picking-list', 'WaveController@locationPickingList');
    $app->get('/{whs_id}/wave/sku/{wv_dtl_id}', 'WaveController@wavePickSkuLocation');
    $app->put('/{whs_id}/wave/sku/{wv_dtl_id}/pallet', 'WaveController@wavePickSkuPickPallet');
    $app->put('/{whs_id}/wave/sku/{wv_dtl_id}/carton', 'WaveController@pickFullCarton');
    $app->put('/{whs_id}/order/{odr_id}/cartons', 'WaveController@assignCarton');
    $app->get('/{whs_id}/order/{odr_id}/cartons', 'WaveController@getAssignCarton');
    $app->get('/{whs_id}/carton/rfid/{rfid}', 'WaveController@scanCarton');
    $app->put('/{whs_id}/order/carton/rfid/{rfid}', 'WaveController@scanCarton');
    $app->post('/{whs_id}/wave/update-status', 'WaveController@saveWavePick');
    $app->get('/{whs_Id}/scan-carton-outbound', 'ScanerController@updateCartonOutbound');
    $app->get('/scan-container-outbound', 'ScanerController@scanContainer');
    $app->get('/{whs_id}/waves', 'WaveController@pickWaveList');

    $app->get('/{whs_id}/waves', 'WaveController@pickWaveList');
    $app->put('/{whs_id}/pallet/assign-cartons', 'WaveController@AssignPackedCartonsToPallet');
    $app->put('/{whs_id}/pallet/shipping', 'WaveController@PutDropPalletOnShippingLane');
   //new
    $app->post('/whs/{whs_id}/wave/{wv_dtl_id}/active-location', 'WaveController@getActiveLocation');
    $app->post('/whs/{whs_id}/wave/{wv_dtl_id}/more-location', 'WaveController@getMoreSuggestLocation');
    $app->get('/whs/{whs_id}/pallet/{lpn}/cartons', 'WaveController@getCartonsOfaPallet');
    $app->get('/whs/{whs_id}/wave/{wv_id}/next-sku/{current_wv_dtl_id}', 'WaveController@getNextSku');

    $app->get('/index', 'AsnController@scanRackOutbound');

});
require 'Routes/role.php';
require 'Routes/user.php';
require 'Routes/log.php';
