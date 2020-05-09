<?php

namespace app\api\model;

class ProductImage extends BaseModel
{
    // 隐藏字段
    protected $hidden = ['img_id','delete_time','product_id'];

    /* 
        与 image 的关联关系
    */
    public function imgUrl()
    {
        return $this->belongsTo('Image','img_id','id');
    }
 
}
