<?php

namespace app\api\service;
use think\Exception;
use app\lib\exception\WeChatException;
use app\lib\exception\TokenException;
use app\lib\enum\ScopeEnum;
use app\api\model\User as ModelUser;

class UserToken extends Token
{
    protected $code;
    protected $wxappID;
    protected $wxAppSecret;
    protected $wxLoginUrl;

    function __construct($code)
    {
        $this->code = $code;
        $this->wxappID = config('wx.app_id');
        $this->wxAppSecret = config('wx.app_secret');
        $this->wxLoginUrl = sprintf(config('wx.login_url'),$this->wxappID,$this->wxAppSecret,$this->code);
    }

    public function get()
    {
        // 向微信发送http请求, $result接收返回结果
        $result =  curl_get($this->wxLoginUrl);
        // json_decode true为 数组, false为 对象
        $wxResult = \json_decode($result, true);
        if(empty($wxResult)){
            throw new Exception('获取session_key及openID时异常, 微信内部错误');
        } else {
            $loginFail = \array_key_exists('errcode',$wxResult);
            if($loginFail){
                $this->processLoginError($wxResult);
            }else{
                // 成功调用微信服务器接口后, 调用授权令牌方法
                return $this->grantToken($wxResult);
            }
        }
    }


    // 颁发令牌
    private function grantToken($wxResult)
    {
        // 获取服务器返回的 openid
        // 检测数据库该 openid 是否已经存在.  
        // 存在, 用户已生成, 无需处理;
        // 不存在,用户未生成, 在数据库中新增一脚user用户记录;
        // 生成令牌, 准备缓存数据 
        // key: 令牌; value: wxResult uid scope; 写入缓存
        // 将令牌返回到客户端
        $openid = $wxResult['openid'];
        $user = ModelUser::getByOpenID($openid);
        if($user){
            $uid = $user->id;
        } else {
            $uid = $this->newUser($openid);
        }
        $cachedValue = $this->prepareCachedValue($wxResult, $uid);
        $token = $this->saveToCache($cachedValue);
        return $token;
    }

    // 写入缓存
    private function saveToCache($cachedValue)
    {
        $key = self::generateToken();
        // 将 $cachedValue 从数组转为 json字符串
        $value = json_encode($cachedValue);
        // 获取配置的过期时间
        $expire_in = config('setting.token_expire_in');
        // 写入tp5缓存
        $request = cache($key, $value, $expire_in);
        if(!$request){
            throw new TokenException([
                'msg' => '服务器缓存异常',
                'errorCode' => 10005
            ]);
        }
        return $key;
    }

    // 准备存入缓存的数据
    private function prepareCachedValue($wxResult, $uid)
    {
        $cachedValue = $wxResult;
        $cachedValue['uid'] = $uid;
        // $cachedValue['scope'] = 15;
        $cachedValue['scope'] = ScopeEnum::User;
        return $cachedValue;
    }

    // 往数据库中插入一条新的user记录
    private function newUser($openid)
    {
        // 使用 model 的 create 方法进行数据插入
        $user = ModelUser::create([
            'openid' => $openid
        ]);
        return $user->id;
    }

    private function processLoginError($wxResult)
    {
        throw new WeChatException([
            'msg' => $wxResult['errmsg'],
            'errorCode' => $wxResult['errcode']
        ]);
    }
 
}
