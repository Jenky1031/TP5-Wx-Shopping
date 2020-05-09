<?php

namespace app\api\model;

class Theme extends BaseModel
{
    // 隐藏字段
    protected $hidden = ['head_img_id','topic_img_id','update_time','delete_time'];

    // 建立 Theme 与 Image 模型 的关联关系
    public function topicImg()
    {
        return $this->belongsTo('Image','topic_img_id','id');
    }

    public function headImg()
    {
        return $this->belongsTo('Image','head_img_id','id');
    }

    /* 
        含有 中间表 的 模型关联关系 
            参数: 关联模型名 中间表名 与关联模型相关的外键 与本模型相关的外键
    */
    public function products()
    {
        return $this->belongsToMany('Product','theme_product','product_id','theme_id');
    }

    /* 
        
    */
    public static function getThemesByIDs($ids)
    {
        $themes = self::with('topicImg,headImg')->select($ids);
        return $themes;
    }

    /* 
        查询某一个 theme 的对应的 products 信息
    */
    public static function getThemeWithProducts($id)
    {
        // 使用关联模型查询
        $theme = self::with('products,topicImg,headImg')->find($id);
        return $theme;
    }
}
