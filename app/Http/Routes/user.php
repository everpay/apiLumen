<?php
$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\User',
    'middleware' => 'check-token',
], function ($app) {
    $app->get('whs/{whs_id}/list-user-wms', 'ManageController@getListUserWms');
    $app->get('whs/{whs_id}/get-user-detail/{id}', 'ManageController@getUserDetail');
    $app->get('whs/{whs_id}/get-list-user', 'ManageController@getListUser');
    $app->put('whs/{whs_id}/update-user/{id}', 'ManageController@putUpdateUser');
    $app->post('whs/{whs_id}/create-user', 'ManageController@createUser');
});

$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\User',
], function ($app) {
    $app->post('whs/{whs_id}/admin-login', 'ManageController@postLogin');
});