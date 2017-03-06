<?php
$app->group([
    'prefix' => PREFIX,
    'namespace' => 'App\Http\Controllers\Role',
    'middleware' => 'check-token',
], function ($app) {
    $app->get('whs/{whs_id}/role', 'IndexController@getList');
    $app->get('whs/{whs_id}/role/all', 'IndexController@getAllRole');
    $app->get('whs/{whs_id}/role/{id}', 'IndexController@getOne');
    $app->put('whs/{whs_id}/role/{id}', 'IndexController@updateRole');
});