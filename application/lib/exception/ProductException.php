<?php

namespace app\lib\exception;

class ProductException extends BaseException
{
    public $code = 404;
    public $msg = '指定商品不存在, 请检查参数count';
    public $errorCode = 20000;
}
