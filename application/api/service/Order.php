<?php

namespace app\api\service;

use app\api\model\OrderProduct;
use app\api\model\UserAddress;
use app\api\model\Product;
use app\api\model\Order as ModelOrder;
use app\lib\exception\OrderException;
use app\lib\exception\UserException;
use think\Exception;
use think\Db;

/* 编写订单的业务 */
class Order
{
    // 订单的商品列表, 即客户端传递过来的products参数
    protected $oProducts;

    // 数据库查询出的订单对应的商品列表, 用于检测库存 ['id', 'price', 'stock', 'name', 'main_img_url']
    protected $products;

    protected $uid;

    /* 检测库存量 */
    public function place($uid, $oProducts)
    {
        $this->oProducts = $oProducts;
        // 从数据库中查询订单商品列表, 携带库存信息
        $this->products = $this->getProductsByOrder($oProducts);
        $this->uid = $uid;
        $status = $this->getOrderStatus();
        if (!$status['pass']) {
            $status['order_id'] = -1;
            return $status;
        }

        // 开始创建订单
        // 生成订单快照
        $orderSnap = $this->snapOrder($status);
        $order = $this->createOrder($orderSnap);
        return $order;
    }

    /* 生成订单 写入订单信息
    $orderNo 订单编号
     */
    private function createOrder($snap)
    {
        // 使用事务 保证数据表完整性
        Db::startTrans();
        // 对数据库的操作应该使用try-catch保护下
        try {
            $orderNo = $this->makeOrderNo();
            // 写order表
            // 实例化order模型
            $order = new ModelOrder();
            // 给模型字段赋值
            $order->user_id = $this->uid;
            $order->order_no = $orderNo;
            $order->total_price = $snap['orderPrice'];
            $order->total_count = $snap['totalCount'];
            $order->snap_img = $snap['snapImg'];
            $order->snap_name = $snap['snapName'];
            $order->snap_address = $snap['snapAddress'];
            // 将数组$snap['pStatus']转为json字符串
            $order->snap_items = json_encode($snap['pStatus']);
            // save 保存一条数据
            $order->save();

            // 写order_product表
            // 从order表中读取id和create_time
            $orderID = $order->id;
            $create_time = $order->create_time;
            // 给每个商品赋上 order_id 属性
            // 加上 & 才能对数组属性进行修改
            foreach ($this->oProducts as &$p) {
                $p['order_id'] = $orderID;
            }
            // 此时订单商品 $oProducts 的数据结构:
            //   [{product_id=>1, count=>3, order_id=x},{product_id=>2, count=>4, order_id=x}]
            // 实例化 OrderProduct 模型
            $orderProduct = new OrderProduct();
            // saveAll 保存一组数据
            $orderProduct->saveAll($this->oProducts);
            Db::commit();
            return [
                'order_no' => $orderNo,
                'order_id' => $orderID,
                'create_time' => $create_time,
                'pass' => true
            ];
        } catch (Exception $ex) {
            Db::rollback();
            throw $ex;
        }
    }

    /* 生成订单编号 尽量保证唯一
    intval() 转为整数值
    date() 函数
    d - 表示月里的某天（01-31）
    m - 表示月（01-12）
    Y - 表示年（四位数）
    1 - 表示周里的某天
    strtoupper() 转为大写字符串
    dechex() 转为十六进制
    substr(string,start,length) 函数返回字符串的一部分
    start 必需。规定在字符串的何处开始。
    正数 - 在字符串的指定位置开始
    负数 - 在从字符串结尾开始的指定位置开始
    0 - 在字符串中的第一个字符处开始
    time() 时间戳 整数 函数返回自 Unix 纪元（January 1 1970 00:00:00 GMT）起的当前时间的秒数
    microtime() 函数返回当前 Unix 时间戳的微秒数
    sprintf() 把百分号（%）符号替换成一个作为参数进行传递的变量
    '%02d' : 占两位 不足则使用0填充
     */
    public static function makeOrderNo()
    {
        // 使用大写字母代表年份
        $yCode = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $orderSn =
        $yCode[intval(date('Y')) - 2020] . strtoupper(dechex(date('m'))) . date(
            'd') . substr(time(), -5) . substr(microtime(), 2, 5) . sprintf(
            '%02d', rand(0, 99));
        return $orderSn;
    }

    /* 生成订单快照
    $snap 订单快照数据结构
    orderPrice 订单总金额
    totalCount 订单商品总件数
    pStauts 订单所有商品的具体信息  id haveStock count name totalPrice
    snapAddress 快照地址
    snapName 订单中第一个商品的名字和缩略图 用来代表此次订单
    snapImg
     */
    private function snapOrder($status)
    {
        $snap = [
            'orderPrice' => 0,
            'totalCount' => 0,
            'pStatus' => [],
            'snapAddress' => null,
            'snapName' => '',
            'snapImg' => '',
        ];

        $snap['orderPrice'] = $status['orderPrice'];
        $snap['totalCount'] = $status['totalCount'];
        $snap['pStatus'] = $status['pStatusArray'];
        // 将数组转为json字符串便于存入数据库
        $snap['snapAddress'] = json_encode($this->getUserAddress());
        $snap['snapName'] = $this->products[0]['name'];
        $snap['snapImg'] = $this->products[0]['main_img_url'];
        if (count($this->products) > 1) {
            $snap['snapName'] .= '等';
        }

        return $snap;
    }

    /* 获取用户地址 */
    private function getUserAddress()
    {
        // $userAddress 模型对象
        $userAddress = UserAddress::where('user_id', '=', $this->uid)->find();
        if (!$userAddress) {
            throw new UserException([
                'msg' => '用户收货地址不存在, 无法创建订单快照, 下单失败',
                'errorCode' => 60001,
            ]);
        }
        // 返回数组
        return $userAddress->toArray();
    }

    /* 库存量检测
        在 getOrderStatus 方法基础上给Pay_service提供库存检测
        getOrderStatus 需要 oProducts 某个订单商品列表 products 数据库中订单商品信息
            oProducts 用户下单时, 已经在数据库order_product表中插入了订单信息, 可以从中获取
            products 从数据库中查询, 在Order_service已有该方法 getProductsByOrder
    */
    public function checkOrderStock($orderID)
    {
        $oProducts = OrderProduct::where('order_id','=',$orderID)-select();
        $this->oProducts = $oProducts;
        $this->products = $this->getProductsByOrder($oProducts);
        $status = $this->getOrderStatus();
        return $status;
    }

    /* 获取订单中所有商品的状态
    pass 标志位 订单是否通过库存验证
    orderPrice 订单商品价格总和
    totalCount 订单商品总件数
    pStatusArray 保存订单所有商品的具体商品信息--用于订单快照 id haveStock count name totalPrice
    --因为订单信息只有id和count--[{product_id=>1, count=>3},{product_id=>2, count=>4}]
    $oProducts 数据结构[{product_id=>1, count=>3},{product_id=>2, count=>4}]
     */
    private function getOrderStatus()
    {
        $status = [
            'pass' => true,
            'orderPrice' => 0,
            'totalCount' => 0,
            'pStatusArray' => [],
        ];
        /* 库存量检测
        循环对比每个商品库存*/
        foreach ($this->oProducts as $oProduct) {
            $pStatus = $this->getProductsStatus($oProduct['product_id'], $oProduct['count'], $this->products);
            if (!$pStatus['haveStock']) {
                $status['pass'] = false;
            }
            $status['orderPrice'] += $pStatus['totalPrice'];
            $status['totalCount'] += $pStatus['counts'];
            array_push($status['pStatusArray'], $pStatus);
        }
        return $status;
    }

    /* 获取订单中某个商品的状态
    参数
    $oPID 订单某个商品的id
    $oCount 订单中某个商品的数量
    $products 订单对应的数据库商品信息, 包含['id', 'price', 'stock', 'name', 'main_img_url']
    变量
    $pIndex 保存某个商品id--$oPID在$products数组中的序号
    $pStatus 保存订单的某个商品的详细信息
    id 商品id
    haveStock 是否有库存
    counts 该商品需要的数量
    price 该商品单价
    name 商品名
    totalPrice 该商品总价 = 该商品的数量counts * 商品单价price
     */
    private function getProductsStatus($oPID, $oCount, $products)
    {
        $pIndex = -1;
        $pStatus = [
            'id' => null,
            'haveStock' => false,
            'counts' => 0,
            'price' => 0,
            'name' => '',
            'totalPrice' => 0,
            'main_img_url' => null
        ];
        // 获取$oPID在$products数组中的序号
        for ($i = 0; $i < count($products); $i++) {
            if ($oPID === $products[$i]['id']) {
                $pIndex = $i;
            }
        }
        // 考虑数据库中没有订单中的某个商品信息
        if ($pIndex === -1) {
            // 客户端传来的product_id可能不存在
            throw new OrderException([
                'msg' => 'ID为' . $oPID . '的商品不存在, 订单创建失败, 请检查商品ID',
            ]);
        } else {
            $product = $products[$pIndex];
            $pStatus['id'] = $product['id'];
            $pStatus['name'] = $product['name'];
            $pStatus['counts'] = $oCount;
            $pStatus['price'] = $product['price'];
            $pStatus['main_img_url'] = $product['main_img_url'];
            $pStatus['totalPrice'] = $oCount * $product['price'];
            if ($product['stock'] >= $oCount) {
                $pStatus['haveStock'] = true;
            }
        }
        return $pStatus;
    }

    /* 根据订单信息从数据库查询订单商品列表, 携带库存 */
    private function getProductsByOrder($oProducts)
    {
        /* 通过循环, 获取订单的所有商品id---$oPIDs, 再一次性完成数据库查询
        $oProducts的数据结构 [{product_id=>1, count=>3},{product_id=>2, count=>4}] */
        $oPIDs = [];
        foreach ($oProducts as $item) {
            array_push($oPIDs, $item['product_id']);
        }
        /* 调用Produts模型, all方法
        visible方法 只显示某写字段
        toArray方法 将数据集转为数组 */
        $products = Product::all($oPIDs)
            ->visible(['id', 'price', 'stock', 'name', 'main_img_url'])
            ->toArray();
        return $products;
    }
}
