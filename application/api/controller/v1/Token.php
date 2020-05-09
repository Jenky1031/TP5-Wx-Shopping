<?php


namespace app\api\controller\v1;

use app\api\model\Category as ModelCategory;
use app\api\service\UserToken;
use app\api\service\Token as ServiceToken;
use app\api\service\AppToken;
use app\api\validate\TokenGet;
use app\api\validate\AppTokenGet;
use app\lib\exception\CategoryException;
use app\lib\exception\ParameterException;

class Token
{
    /*
     * 通过code码获取令牌
     * @url /token/user
     * @http POST
     */
    public function getToken($code='')
    {
        (new TokenGet())->goCheck();
        // 将 $code 传入 UserToken 的构造函数
        $ut = new UserToken($code);
        $token = $ut->get();
        // 返回数组, 框架会默认将其序列化为json
        return [
            'token' => $token
        ];
    }

    /* 
        验证客户端的令牌是否有效
         @url /token/verify
         @http POST
    */
    public function verifyToken($token='')
    {
        if(!$token){
            throw new ParameterException([
                'token不允许为空'
            ]);
        }
        // $valid 布尔值
        $valid = ServiceToken::verifyToken($token);
        return [
            'isValid' => $valid
        ];
    }

    /**
     * 第三方应用获取令牌
     * /token/app
     * @url /app_token?
     * @POST ac=:ac se=:secret
     */
    public function getAppToken($ac='', $se='')
    {
        // header('Access-Control-Allow-Origin: *');
        // header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
        // header('Access-Control-Allow-Methods: GET');
        (new AppTokenGet())->goCheck();
        $app = new AppToken();
        $token = $app->get($ac, $se);
        return [
            'token' => $token
        ];
    }
}
