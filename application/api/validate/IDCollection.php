<?php

namespace app\api\validate;

class IDCollection extends BaseValidate
{
    // 固定的规则定义方式
    protected $rule = [
        'ids' => 'require|checkIDs'
    ];

    // 固定的检测不通过的信息提示 ids 不通过 则返回该 message
    protected $message  = [
        'ids' => 'ids参数必须是以逗号分隔的多个正整数'
    ];

    // @param $value 客户端传来的参数 ids=id1,id2...
    protected function checkIDs($value)
    {
        // 将 ids=id1,id2... 转为 数组
        $value = explode(',', $value);
        // 若 $value 为空, 说明 ids 格式不正确
        if (empty($value)) {
            return false;
        }
        // 判断 ids 值 是否是 正整数
        foreach ($value as $id) {
            // 调用父类BaseValidate的 isPostiveInterger() 方法
            if(!$this->isPositiveInteger($id)){
                return false;
            }
        }

        return true;
    }
}
