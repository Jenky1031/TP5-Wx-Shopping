<?php

namespace app\api\model;

class Image extends BaseModel
{
    // 隐藏字段
    protected $hidden = ['id','from','update_time','delete_time'];

    // 读取器---是一个特殊命名格式的方法
    // 作用: 读取 model 查询回来的数据字段, 修改该值, 再加入查询结果的返回大军
    // 命名方法: get + 字段名 + Attr
    // 第一个参数: 字段名对应的 值
    // 第二个参数: 当前模型对应的所有字段
    // 返回的值 是 这个字段名 对应的 新的值, 随着 model 的查询方法一起返回
    public function getUrlAttr($value, $data)
    {
        return $this->prefixImgUrl($value, $data);
    }
}
