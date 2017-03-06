<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 10/19/2016
 * Time: 7:19 PM
 */


if ( ! function_exists('config_path'))
{
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}


if ( ! function_exists('app_path'))
{
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function app_path($path = '')
    {

        return app()->basePath() . '/app' . ($path ? '/' . $path : $path);
    }
}
define('MWS_URL_LOGIN','http://wms2.local.seldatdirect.com/core/master-service');
define('WMS_URL_API','http://wms2.local.seldatdirect.com/core/wap/inbound');
define('WMS_URL_OUTBOUND_API','http://wap.wms2.local.seldatdirect.com/core/wap/outbound-bk');
define('WMS_URL_OUTBOUND_API2','http://wms2.local.seldatdirect.com/core/wap/outbound');
define('TIME_24H',86400);
define('USER_WMS_PROCESS',"citwau");




