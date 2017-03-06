<?php
$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\Log',
    'middleware' => 'check-token',
], function ($app) {
    $app->get('whs/{whs_id}/access-log', 'LogController@getListAccessLog');
    $app->post('whs/{whs_id}/access-log', 'LogController@getListAccessLog');
    $app->get('whs/{whs_id}/log-event', 'LogController@getListLogEvent');
    $app->get('whs/{whs_id}/access-log', 'LogController@getListAccessLog');
    $app->post('whs/{whs_id}/access-log', 'LogController@getListAccessLog');
});