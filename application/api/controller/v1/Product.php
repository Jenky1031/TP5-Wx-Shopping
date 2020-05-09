<?php


namespace app\api\controller\v1;

use app\api\model\Product as ModelProduct;
use app\api\validate\Count;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\ProductException;

class Product
{
    /*
     * 获取 最近新品
     * 由客户端决定 显示多少条 最近新品, 如果客户端不传, 则显示15条
     * @url /product/recent
     * @http GET
     * @$count 最近新品的商品数
     */
    public function getRecent($count = 15)
    {
        // 参数校验
        (new Count())->goCheck();
        // 查询获取 最近新品 的数据
        // 临时隐藏字段 summary 使用 数据集 的hidden方法
        $product = ModelProduct::getMostRecent($count)->hidden(['summary']);
        // 对返回的数据集 判空
        if ($product->isEmpty()) {
            throw new ProductException();
        }
        return $product;
    }
    /*
        获取某一分类下的所有商品信息
        @url /product/by_category
        @id  category的id号
    */
    public function getAllInCategory($id)
    {
        // 验证参数是否是正整数
        (new IDMustBePositiveInt())->goCheck();
        // 根据分类的id 获取所有商品详情
        // 临时隐藏字段 summary 使用 数据集 的hidden方法
        $products = ModelProduct::getProductsByCategory($id)->hidden(['summary']);
        if($products->isEmpty()){
            throw new ProductException();
        }
        return  $products;
    }

    /* 
        获取商品详情
    */
    public function getOne($id)
    {
        // 验证参数是否是正整数
        (new IDMustBePositiveInt())->goCheck();
        // 根据分类的id 获取所有商品详情
        $product = ModelProduct::getProductsDetail($id);
        if(!$product){
            throw new ProductException();
        }
        return $product;
    }
}
