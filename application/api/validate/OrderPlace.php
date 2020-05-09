<?php

namespace app\api\validate;

use app\lib\exception\ParameterException;

class OrderPlace extends BaseValidate
{
    // produts [{product_id=>1, count=>3},{product_id=>2, count=>4}]
    protected $rule = [
        'products' => 'checkProducts'
    ];

    // 只有一个$rule才是自动执行的验证规则, 这个$singleRule无法自动验证, 需要调用
    protected $singleRule = [
        'product_id' => 'require|isPositiveInteger',
        'count' => 'require|isPositiveInteger',
    ];

    protected function checkProducts($values)
    {
        // 判断是否是数组
        if (!is_array($values)) {
            throw new ParameterException([
                'msg' => '商品订单列表必须是数组',
            ]);
        }
        // 判空
        if(empty($values)){
            throw new ParameterException([
                'msg' => '商品列表不能为空'
            ]);
        }
        foreach ($values as $value)
        {
            $this->checkProduct($value);
        }
        return true;
    }

    private function checkProduct($value)
    {
        $validate = new BaseValidate($this->singleRule);
        $result = $validate->check($value);
        if(!$result){
            throw new ParameterException([
                'msg' => '商品订单列表参数错误(商品id或数量不存在或不是正整数)',
            ]);
        }
    }
}
