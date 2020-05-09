<?php

namespace app\api\model;

class Banner extends BaseModel
{
    // 模型 的 hidden 方法, 隐藏查询返回的客户端却不需要的某些字段
    protected $hidden = ['update_time','delete_time'];
    // 关联----不是一个普通的函数
    public function items(){
        // $this --- banner
        // hasMany --- 有很多个 BannerItem 模型
        // 它们之间通过 关联模型的外键banner_id 和 本模型的主键id 连接
        // 参数: 关联模型的模型名 关联模型上的外键 本模型的主键
        return $this->hasMany('BannerItem','banner_id','id');
    }
    public static function getBannerByID($id)
    {   
        // 1.根据 $id 获取 banner 信息
        // 2. ModelBanner 继承了 Model, 具有 Model 的查询方法 get all find select
        // 3.推荐 静态方法 调用 模型的查询方法
        // 4.使用了with()调用关联关系后, 
        //      不可再使用 all get 模型查询方法, 
        //      只可以使用 find select DB查询方法
        // 5.self 代表 本模型
        $banner = self::with(['items.img'])->find($id);
        return $banner;
    }
}
