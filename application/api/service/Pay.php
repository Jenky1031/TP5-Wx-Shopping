<?php

namespace app\api\service;

use app\api\model\Order as ModelOrder;
use app\api\service\Order as ServiceOrder;
use app\api\service\Token as ServiceToken;
use app\lib\enum\OrderStatusEnum;
use app\lib\exception\OrderException;
use app\lib\exception\TokenException;
use think\Loader;

//extend/WxPay/WxPay.Api.php
/* 三个参数: 文件夹+文件名开头 extend的文件目录 文件名结尾+后缀 */
Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');

class Pay
{
    private $orderID;
    private $orderNO;

    public function __construct($orderID)
    {
        if (!$orderID) {
            throw new Exception('订单号不允许为NULL--Pay_service');
        }
        $this->orderID = $orderID;
    }

    /* 支付主方法 */
    public function pay()
    {
        // 客户端传来的订单号可能不存在
        // 订单号存在, 但与当前用户不匹配---对比 order_id查询出的user_id 与 令牌对应的uid
        // 订单有可能已被支付 order表的status=1表示未支付
        $this->checkOrderValid();
        // 库存量检测
        $orderService = new ServiceOrder();
        $status = $orderService->checkOrderStock($this->orderID);
        if (!$status['pass']) {
            // 库存量检测未通过, 返回订单状态信息 中断支付
            return $status;
        }
        return $this->makeWxPreOrder($status['orderPrice']);
    }

    /* 微信预订单生成方法 */
    private function makeWxPreOrder()
    {
        $openid = ServiceToken::getCurrentTokenVar('openid');
        if (!$openid) {
            throw new TokenException([
                'msg' => 'openid获取失败',
            ]);
        }
        // 使用SDK调用微信预订单接口
        // 向微信发送预订单请求
        // 调用 WxPay.Data.php 的 统一下单输入对象 WxPayUnifiedOrder方法
        $wxOrderData = new \WxPayUnifiedOrder();
        $wxOrderData->SetOut_trade_no($this->orderNo);
        $wxOrderData->SetTrade_type('JSAPI');
        // 微信金额单位是分, 我们服务器的单位是元 所以要*100
        $wxOrderData->SetTotal_fee($totalPrice * 100);
        $wxOrderData->SetBody('零食狂欢');
        $wxOrderData->SetOpenid($openid);
        // 接收微信支付结果
        $wxOrderData->SetNotify_url(config('secure.pay_back_url'));

        return $this->getPaySignature($wxOrderData);
    }

    //向微信请求订单号并生成签名
    private function getPaySignature($wxOrderData)
    {
        // 调用WxPay.Api.php 的 unifiedOrder 微信接口
        $wxOrder = \WxPayApi::unifiedOrder($wxOrderData);
        // 失败时不会返回result_code
        if ($wxOrder['return_code'] != 'SUCCESS' || $wxOrder['result_code'] != 'SUCCESS') {
            // 失败时, 记录日志
            Log::record($wxOrder, 'error');
            Log::record('获取预支付订单失败', 'error');
            //throw new Exception('获取预支付订单失败');
        }
        $this->recordPreOrder($wxOrder);
        // 为小程序调用微信接口拉起微信支付准备参数, 使用签名保证这些参数不会被篡改
        $signature = $this->sign($wxOrder);
        return $signature;
    }

    /* 使用更新操作, 保存prepay_id到数据库, 用于CMS和小程序拉起支付的参数之一 */
    private function recordPreOrder($wxOrder)
    {
        // 必须是update，每次用户取消支付后再次对同一订单支付，prepay_id是不同的
        ModelOrder::where('id', '=', $this->orderID)
            ->update(['prepay_id' => $wxOrder['prepay_id']]);
    }

    // 签名
    private function sign($wxOrder)
    {
        // 使用SDK的WxPayJsApiPay类 提交JSAPI输入对象
        $jsApiPayData = new \WxPayJsApiPay();
        $jsApiPayData->SetAppid(config('wx.app_id'));
        // php内置函数 time() 生成时间戳 并使用(string)转为字符串
        $jsApiPayData->SetTimeStamp((string) time());
        // 生成随机字符串 mt_rand(0, 1000) 从0-1000取随机数
        $rand = md5(time() . mt_rand(0, 1000));
        $jsApiPayData->SetNonceStr($rand);
        $jsApiPayData->SetPackage('prepay_id=' . $wxOrder['prepay_id']);
        $jsApiPayData->SetSignType('md5');
        // 生成签名
        $sign = $jsApiPayData->MakeSign();
        // 客户端需要的是数组参数 将这些数据对象转为原始数组
        $rawValues = $jsApiPayData->GetValues();
        // 将签名加上
        $rawValues['paySign'] = $sign;
        // 将appId删除
        unset($rawValues['appId']);
        return $rawValues;
    }

    /* 验证客户端传来的订单号是否存在 */
    public function checkOrderValid()
    {
        $order = ModelOrder::where('id', '=', $this->orderID)->find();
        if (!$order) {
            throw new OrderException([
                'msg' => '订单不存在, 请检查商品ID Pay_service',
            ]);
        }
        if (!ServiceToken::isValidOperate($this->orderID)) {
            throw new TokenException([
                'msg' => '订单与用户不匹配',
                'errorCode' => 10003,
            ]);
        }
        if ($order->status !== OrderStatusEnum::UNPAID) {
            throw new OrderException([
                'msg' => '订单已支付过啦',
                'errorCode' => 80003,
                'code' => 400,
            ]);
        }
        $this->$orderNO = $order->order_no;
        return true;
    }
}
