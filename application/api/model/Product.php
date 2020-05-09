<?php

namespace app\api\model;

class Product extends BaseModel
{
    // 隐藏字段
    protected $hidden = ['main_img_id','pivot','from','category_id','update_time','delete_time','create_time'];

    /* 
        读取器 读取 main_img_url 拼接并返回完整的 url
    */
    public function getMainImgUrlAttr($value, $data)
    {
        return $this->prefixImgUrl($value, $data);
    }

    /* 
        与 product_image 的关联关系
    */
    public function imgs()
    {
        return $this->hasMany('ProductImage','product_id','id');
    }

    /* 
        与 product_property 的关联关系
    */
    public function properties()
    {
        return $this->hasMany('ProductProperty','product_id','id');
    }

    /* 
        查询 最近新品
    */
    public static function getMostRecent($count)
    {
        // limit指定数量限制 order根据某个字段排序 desc倒序 
        $products = self::limit($count)->order('create_time desc')->select();
        return $products;
    }

    /* 
        根据分类的id 获取所有商品列表
    */
    public static function getProductsByCategory($categoryID)
    {
        $products = self::where('category_id','=',$categoryID)->select();
        return $products;
    }

    /* 
        根据商品id 获取商品详情
    */
    public static function getProductsDetail($id)
    {
        // $product = self::with(['imgs.imgUrl'])->with(['properties'])->find($id);
        $product = self::with([
            'imgs' => function($query){
                $query->with(['imgUrl'])
                ->order('order','asc');
            }
        ])
            ->with(['properties'])
            ->find($id);
        return $product;
    }
}
