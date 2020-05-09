<?php

namespace app\api\validate;

use think\Validate;

class TokenGet extends BaseValidate
{
    // 验证规则 是作为 成员属性 定义 可见性--protected  固定变量名--$rule--数组
    protected $rule = [
        'code' => 'require|isNotEmpty'
    ];

    protected $message = [
        'code' => '没有code休想获得Token!'
    ];
}
