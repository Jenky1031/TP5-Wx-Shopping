<?php

namespace app\api\model;

use think\Model;

class BaseModel extends Model
{
    // 普通函数, 处理拼接 img_url 读取器 内部的逻辑
    protected function prefixImgUrl($value, $data) {
        // $value 为 url 的值, $data查询结果
        // 仅当 from = 1 时, 即数据库中的图片url路径是相对路径时, 
        //  才进行拼接, 否则返回原值
        $finalUrl = $value;
        if($data['from'] === 1) {
            $finalUrl = config('setting.img_prefix').$value;
        }
        return $finalUrl;
    }
}
