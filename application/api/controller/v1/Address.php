<?php

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\model\UserAddress;
use app\api\model\User as ModelUser;
use app\api\service\Token as ServiceToken;
use app\api\validate\AddressNew;
use app\lib\exception\SuccessMessage;
use app\lib\exception\UserException;

/* 调用前置方法必须该类必须继承Controller */
class Address extends BaseController
{

    /* 使用Controller基类的前置方法 */
    // protected $beforeActionList = [
    //     // second和third方法 只有first这个前置方法执行后才会执行
    //     'first' => ['only' => 'second,third']
    // ];

    // protected function first()
    // {
    //     echo 'first';
    // }

    // // 设置路由访问second, 查看是否是first方法先执行
    // public function second()
    // {
    //     echo 'second';
    // }

    /**
     * 前置操作方法列表
     * @var array $beforeActionList
     * @access protected
     */
    protected $beforeActionList = [
        'checkPrimaryScope' => ['only' => 'createOrUpdateAddress,getUserAddress'],
    ];

    /*
     * 新建或更新用户地址的接口
     * @url /address
     * @http post
     */
    public function createOrUpdateAddress()
    {
        $validate = new AddressNew();
        $validate->goCheck();
        // 根据token获取uid
        // 根据uid查找用户数据, 判断用户是否存在, 如果不存在, 抛出异常
        // 获取用户从客户端提交的地址信息
        // 根据用户地址信息是否存在, 判断是添加地址还是更新地址
        $uid = ServiceToken::getCurrentUid();
        // 模型的get方法查询
        $user = ModelUser::get($uid);
        if (!$user) {
            throw new UserException();
        }
        // 验证器无法验证客户端是否传了多余的参数字段
        // 如果客户端又传了一个uid, 将会覆盖之前从缓存中获取的uid
        // 解决方法: 只获取验证器验证过的参数--参数过滤
        // input('post.')获取post方式传递过来的地址信息
        $dataArray = $validate->getDataByRule(input('post.'));
        // 通过user模型定义的address关联关系直接读取user_address表
        $userAddress = $user->address;
        if (!$userAddress) {
            // 使用模型的关联关系新增记录
            // 新增时address模型需要括号
            $user->address()->save($dataArray);
            return json(new SuccessMessage([
                'msg' => '用户地址新建成功',
            ]), 201);
        } else {
            // 更新时,address模型不需要括号()
            $user->address->save($dataArray);
            return json(new SuccessMessage([
                'msg' => '用户地址更新成功',
            ]), 201);
        }
        // return $user;
    }

    /**
     * 获取用户地址信息
     * @url /address
     * @http get
     * @return UserAddress
     * @throws UserException
     */
    public function getUserAddress()
    {
        $uid = ServiceToken::getCurrentUid();
        $userAddress = UserAddress::where('user_id', $uid)
            ->find();
        if (!$userAddress) {
            throw new UserException([
                'msg' => '用户地址不存在',
                'errorCode' => 60001,
            ]);
        }
        return $userAddress;
    }
}
