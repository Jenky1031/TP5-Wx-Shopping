<?PHP

namespace app\lib\exception;

class OrderException extends BaseException
{
    public $code = 404;
    public $msg = '订单不存在, 请检查商品ID';
    public $errorCode = 80000;
}
