<?php

/**
 * API 返回值Body
 */
class APIResponseBody
{
    /**
     * API 返回值
     * code 0 请求成功
     * @var array
     */
    static $success = array(
        "code" => 0,
        "message" => "Request Success",
        "data" => array()
    );

    /**
     * API 返回值
     * code 1 参数错误
     * @var array
     */
    static $param_error = array(
        "code" => 1,
        "message" => "Bad Request Params",
    );

    /**
     * API 返回值
     * code 2 权限错误
     * @var array
     */
    static $privilege_error = array(
        "code" => 2,
        "message" => "No Privilege",
    );
}