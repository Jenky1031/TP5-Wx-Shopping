<?php

namespace app\api\model;

class BannerItem extends BaseModel
{
    // 隐藏某些客户端不需要的字段
    protected $hidden = ['id','img_id','banner_id','update_time','delete_time'];

    public function img() {
        return $this->belongsTo('Image','img_id','id');
    }
}
