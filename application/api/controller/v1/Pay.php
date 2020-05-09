<?php

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\service\Pay as PayService;
use app\api\validate\IDMustBePositiveInt;
use app\api\service\WxNotify;

class Pay extends BaseController
{
    /* 权限控制 */
    protected $beforeActionList = [
        'checkExclusiveScope' => ['only' => 'getPreOrder'],
    ];

    /*请求微信服务器的预订单信息
    需要用户的openid和能够唯一代表用户订单的信息 order表的id主键或order_no订单编号
    order_id代表订单, 参数获取
    openid代表用户, 缓存获取
     * 获取
     * @url /pay/pre_order
     * @http POST
     * @params $id 订单id
     */
    public function getPreOrder($id = '')
    {
        // 验证 $id 是否为 正整数
        (new IDMustBePositiveInt())->goCheck();
        $pay= new PayService($id);
        return $pay->pay();
    }

    /* 提供微信调用的接口, 接收微信的支付结果通知 */
    public function receiveNotify()
    {
        // 1.检测库存量, 超卖 小概率
        // 2.更新该订单status状态 order表的status
        // 3.减库存
        // 如果成功处理, 返回微信成功处理的消息, 否则返回没有成功处理
        // 微信调用我们接口的特点 post方式, 微信携带的参数是xml格式, 不会在url上携带参数
        // 可使用微信SDK获取xml格式的参数

        // $xmlData = file_get_contents('php://input');
        // Log::error($xmlData);

        // 实例化WxNotify类
        $notify = new WxNotify();
        // 调用基类 WxPayNotify 的 handle 方法, 触发重写的 NotifyProcess 方法
        $notify->handle();
    }
}
