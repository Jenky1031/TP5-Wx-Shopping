<?php

namespace app\lib\exception;

use Exception;
use think\exception\Handle;
use think\exception\RouteNotFoundException;
use think\Log;
use think\Request;

// 覆盖 render 方法
// 框架会将需要处理的具体异常传入render方法作为参数
// 所有代码抛出异常都会通过render方法渲染, 决定返回客户端何种信息

class ExceptionHandler extends Handle
{
    private $code;
    private $msg;
    private $errorCode;

    public function render(Exception $e)
    {
        if ($e instanceof BaseException) {
            // 如果 $ex 是 baseException类 的实例---自定义异常
            //     代表需要 向客户端返回具体错误信息---不需要记录日志
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->errorCode = $e->errorCode;
        } elseif ($e instanceof RouteNotFoundException) {
            $this->code = 404;
            $this->msg = '请求的地址不存在或请求方式错误';
            $this->errorCode = 999;
        } else {
            // think\Config::get('app_debug')
            if (config('app_debug')) {
                return parent::render($e);
            } else {
                // 不需要让客户端知道的异常  状态码设置为 500
                $this->code = 500;
                $this->msg = '服务器内部错误, 不想告诉你~';
                $this->errorCode = 999;
                $this->recordErrorLog($e);
            }
        }
        $request = Request::instance();
        $result = [
            'msg' => $this->msg,
            'errorCode' => $this->errorCode,
            'request_url' => $request->url(),
        ];

        return json($result, $this->code);
    }

    private function recordErrorLog(Exception $e)
    {
        Log::init([
            'type' => 'File',
            'path' => LOG_PATH,
            'level' => ['error'],
        ]);

        Log::record($e->getMessage(), 'error');
    }
}
// 记录日志的方法
// 个人理解: 参数接收的是一个异常 所以前面需要加Exception

// 日志默认初始化功能已经关闭 即 'type' = 'test'
// 需要手动初始化 调用Log类的init()方法
//      level 只记录异常 error 以上级别

// 参数一 错误信息 参数二 日志级别
