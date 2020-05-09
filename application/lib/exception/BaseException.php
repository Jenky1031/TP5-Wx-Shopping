<?php

namespace app\lib\exception;

use Exception;

class BaseException extends Exception
{
    // 封装 统一描述错误: 状态码code 错误码error_code 错误信息msg 当前url
    // 赋初始值, 子类会覆盖初始值
    public $code = 400;
    public $msg = '参数错误';
    public $errorCode = 10000;

    public function __construct($params = [])
    {
        if (!is_array($params)) {
            // return ;
            throw new Exception('参数不是数组');
        }
        if (array_key_exists('code', $params)) {
            $this->code = $params['code'];
        }
        if (array_key_exists('msg', $params)) {
            $this->msg = $params['msg'];
        }
        if (array_key_exists('errorCode', $params)) {
            $this->errorCode = $params['errorCode'];
        }
    }
}
