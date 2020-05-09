<?php

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\model\Order as ModelOrder;
use app\api\service\Order as ServiceOrder;
use app\api\service\Token as ServiceToken;
use app\api\validate\IDMustBePositiveInt;
use app\api\validate\OrderPlace;
use app\api\validate\PagingParameter;
use app\lib\exception\OrderException;

class Order extends BaseController
{
    /*
    用户在选择商品后, 下单, 向API提交所选商品的相关信息
    API在接收到信息后, 检查订单商品库存量(商品详情页面未实时刷新, 可能下单时, 商品已没有库存)
    有库存--将订单数据存入数据库--下单成功, 返回客户端可以支付了的消息
    小程序调用服务器的支付接口进行支付
    检测库存量(因为下单成功并未减库存, 其他用户也可能将其下单成功并提前支付成功)
    服务器调用微信的预订单接口, 微信返回支付参数
    服务器再将支付参数返回到小程序
    小程序使用支付参数调用小程序内部的支付API进行支付, 支付参数正确, 支付界面将被拉起
    支付后, 微信会返回客户端一个支付结果, 并将在一定时段内不间断地异步发送支付结果返回到服务器, 直到服务器处理
    库存量检测(因为返回到服务端的支付结果是异步的, 可能虽然返回成功支付, 但商品被其他用户先支付成功造成没有库存了)
    支付成功, 则减库存;
     */

    /**
     * 前置操作方法列表
     * @var array $beforeActionList
     * @access protected
     */
    protected $beforeActionList = [
        'checkExclusiveScope' => ['only' => 'placeOrder'],
        'checkPrimaryScope' => ['only' => 'getDetail,getSummaryByUser'],
        'checkSuperScope' => ['only' => 'delivery,getSummary'],
    ];

    /*
     * 订单接口
     * @url /order
     * @http POST
     * @params array 数组的子元素是: 某个商品的id和数量count
     * products 订单所有商品 数据结构[{product_id=>1, count=>3},{product_id=>2, count=>4}]
     * status 创建出的订单信息 数组 order_no order_id create_time pass
     */
    public function placeOrder()
    {
        // 验证器
        (new OrderPlace())->goCheck();
        /* 从方式为post的http请求中获取products数组信息
        post方式 json数组 键名为products
        助手函数input获取数组参数需要加上/a */
        $products = input('post.products/a');
        $uid = ServiceToken::getCurrentUid();
        $order = new ServiceOrder();
        $status = $order->place($uid, $products);
        return $status;
    }

    /**
     * 接口: 获取订单列表
     * 根据用户id分页获取订单列表（简要信息）
     * @url /order/by_user
     * @param int $page 指定哪一页
     * @param int $size 指定一页多少条
     * @return array
     * @throws \app\lib\exception\ParameterException
     */
    public function getSummaryByUser($page = 1, $size = 15)
    {
        (new PagingParameter())->goCheck();
        // 根据用户携带的token查询uid
        $uid = ServiceToken::getCurrentUid();
        // 从order模型中查询出第几页的订单信息
        // $pagingOrders 是 Paginator对象, 对象应该使用 isEmpty() 判空
        $pagingOrders = ModelOrder::getSummaryByUser($uid, $page, $size);
        if ($pagingOrders->isEmpty()) {
            // 如果查询分页信息是空, 那返回空数组 data 和 当前页码
            // Paginator对象 的 currentPage() 方法 可以获得当前页码
            return [
                'current_page' => $pagingOrders->currentPage(),
                'data' => [],
            ];
        }
        //    $collection = collection($pagingOrders->items());
        //            $data = $collection->hidden(['snap_items', 'snap_address'])
        //                ->toArray();

        // Paginator对象 具有 toArray 转为数组 和 hidden 隐藏字段方法
        $data = $pagingOrders->hidden(['snap_items', 'snap_address', 'prepay_id'])
            ->toArray();
        return [
            'current_page' => $pagingOrders->currentPage(),
            'data' => $data,
        ];

    }

    /**
     * 获取全部订单简要信息（分页）
     * @param int $page
     * @param int $size
     * @return array
     * @throws \app\lib\exception\ParameterException
     */
    public function getSummary($page = 1, $size = 20)
    {
        (new PagingParameter())->goCheck();
        // $uid = Token::getCurrentUid();
        $pagingOrders = ModelOrder::getSummaryByPage($page, $size);
        if ($pagingOrders->isEmpty()) {
            return [
                'current_page' => $pagingOrders->currentPage(),
                'data' => [],
            ];
        }
        $data = $pagingOrders->hidden(['snap_items', 'snap_address'])
            ->toArray();
        return [
            'current_page' => $pagingOrders->currentPage(),
            'data' => $data,
        ];
    }

    /**
     * 获取订单详情
     * @url /order/:id
     * @param $id
     * @return static
     * @throws OrderException
     * @throws \app\lib\exception\ParameterException
     */
    public function getDetail($id)
    {
        (new IDMustBePositiveInt())->goCheck();
        // 调用模型自带的get方法
        $orderDetail = ModelOrder::get($id);
        if (!$orderDetail) {
            throw new OrderException();
        }
        return $orderDetail
            ->hidden(['prepay_id']);
    }

    /* 模拟支付成功后, 修改订单状态
    @url /order/changeStatus/:id
     */
    public function changeOrderStatus($id)
    {
        (new IDMustBePositiveInt())->goCheck();
        ModelOrder::where('id', '=', $id)->update(['status' => '2']);
        return true;
    }
}
