<?php

namespace app\api\model;

class Category extends BaseModel
{
    // 隐藏某些客户端不需要的字段
    protected $hidden = ['update_time','delete_time','create_time'];

    // 关联 image表
    public function img() {
        return $this->belongsTo('Image','topic_img_id','id');
    }
}
