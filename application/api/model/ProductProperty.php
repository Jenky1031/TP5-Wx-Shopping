<?php

namespace app\api\model;

class ProductProperty extends BaseModel
{
    // 隐藏字段
    protected $hidden = ['id','delete_time','product_id'];
}
