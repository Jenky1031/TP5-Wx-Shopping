<?php

namespace app\api\validate;

use app\lib\exception\ParameterException;
use think\Request;
use think\Validate;

class BaseValidate extends Validate
{
    public function goCheck()
    {
        // 获取所有http参数 进行校验
        $params = Request::instance()->param();
        // 继承了Validate 就不需要实例化 可以直接调用它的check方法
        $result = $this->batch()->check($params);
        if (!$result) {
            // parameterException的实例$e 将具有 parameterException 的 code msg errorCode
            //   同时 隐式调用了parameterException的父类的构造函数
            // 传入'msg' => $this->getError() 到 构造函数 达到初始化 msg 的效果
            // $this 指向 BaseValidate getError() 获取不符合验证规则的错误提示
            $e = new ParameterException([
                'msg' => $this->getError(),
            ]);
            throw $e;
        } else {
            return true;
        }
    }

    // $value 参数值
    // $rule 规则
    // $data check传入的$data
    // $filed 参数名
    protected function isPositiveInteger($value, $rule = '', $data = '', $filed = '')
    {
        // 一定要 +0
        if (is_numeric($value) && is_int($value + 0) && ($value + 0 > 0)) {
            return true;
        } else {
            return false;
        }
    }

    protected function isNotEmpty($value, $rule = '', $data = '', $filed = '')
    {
        if (empty($value)) {
            return false;
        } else {
            return true;
        }
    }

    //没有使用TP的正则验证，集中在一处方便以后修改
    //不推荐使用正则，因为复用性太差
    //手机号的验证规则
    protected function isMobile($value)
    {
        $rule = '^1(3|4|5|7|8)[0-9]\d{8}$^';
        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
    protected function isTelNumber($value)
    {
        $numArr = str_split($value);
        $startNum = intval($numArr[0]);

        // 验证手机号
        if ($startNum === 1) {
            $rule = '^1(3|4|5|7|8)[0-9]\d{8}$^';
        }
        // 验证座机
        else if ($startNum === 0) {
            $rule = '^0\d{2,3}-\d{7,8}$^';
        } else {
            return false;
        }

        $result = preg_match($rule, $value);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $arrays 通常传入request.post变量数组--客户端传来的所有参数
     * @return array 按照规则key过滤后的变量数组
     * @throws ParameterException
     */
    public function getDataByRule($arrays)
    {
        if (array_key_exists('user_id', $arrays) | array_key_exists('uid', $arrays)) {
            // 不允许包含user_id或者uid，防止恶意覆盖user_id外键
            throw new ParameterException([
                'msg' => '参数中包含有非法的参数名user_id或者uid',
            ]);
        }
        $newArray = [];
        // 遍历验证器的rule
        foreach ($this->rule as $key => $value) {
            $newArray[$key] = $arrays[$key];
        }
        return $newArray;
    }
}
