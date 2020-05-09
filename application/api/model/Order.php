<?php

namespace app\api\model;

class Order extends BaseModel
{
    // 隐藏字段
    protected $hidden = ['user_id','delete_time','update_time'];

    // 使用tp5模型的自动写入时间戳
    // 当监测到写入数据库就会自动生成create_time 更新updata_time 软删除就会delete_time 
    protected $autoWriteTimestamp = true;

    /* 使用读取器将snapitem字符串转为数组 */
    public function getSnapItemsAttr($value)
    {
        if(empty($value)){
            return null;
        }
        return json_decode($value);
    }

    /* 使用读取器将snapaddress字符串转为数组 */
    public function getSnapAddressAttr($value){
        if(empty($value)){
            return null;
        }
        return json_decode(($value));
    }
    
    public static function getSummaryByUser($uid, $page=1, $size=15)
    {
        // 查询链式方法中的链式查询
        //    paginate() 每页数量 是简洁模式 ['page'=>$page当前页]
        //      返回值 Paginator 对象 , 等同调用了find select 有了查询功能
        $pagingData = self::where('user_id', '=', $uid)
            ->order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }

    public static function getSummaryByPage($page=1, $size=20){
        $pagingData = self::order('create_time desc')
            ->paginate($size, true, ['page' => $page]);
        return $pagingData ;
    }

    public function products()
    {
        return $this->belongsToMany('Product', 'order_product', 'product_id', 'order_id');
    }
}
