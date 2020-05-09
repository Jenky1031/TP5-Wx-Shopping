<?php

namespace app\api\service;

use think\Exception;
use app\lib\exception\TokenException;
use app\lib\exception\ForbiddenException;
use app\lib\enum\ScopeEnum;
use app\api\model\User as ModelUser;
use think\Request;
use think\Cache;

/* 基类 */

class Token
{
    //创建令牌--32个字符组成一组无意义随机字符串
    public static function generateToken()
    {
        // 用三组字符串,进行md5加密
        // 字符串一: 获取 32 位随机字符
        $randChars = getRandChar(32);
        // 字符串二: 获取时间戳
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        // 字符串三: 盐
        $salt = config('secure.token_salt');
        return md5($randChars . $timestamp . $salt);
    }

    /* 获取缓存中token对应的某个值 */
    public static function getCurrentTokenVar($key)
    {
        // 从http头获取用户的token
        $token = Request::instance()->header('token');
        $vars = Cache::get($token);
        if (!$vars) {
            throw new TokenException();
        } else {
            if (!is_array($vars)) {
                // 将vars转为数组
                $vars = json_decode($vars, true);
            }
            if (array_key_exists($key, $vars)) {
                return $vars[$key];
            } else {
                throw new Exception('缓存中不存在正在尝试获取的Token变量');
            }
        }
    }

    /* 获取当前用户的uid */
    public static function getCurrentUid()
    {
        $uid = self::getCurrentTokenVar('uid');
        return $uid;
    }

    /* 只有用户和管理员具有权限 */
    public static function needPrimaryScope()
    {
        $scope = self::getCurrentTokenVar('scope');
        if ($scope) {
            if ($scope>=ScopeEnum::User) {
                return true;
            } else {
                throw new ForbiddenException();
            }
        } else {
            throw new TokenException();
        }
    }

    /* 用户专有权限 */
    public static function needExclusiveScope()
    {
        $scope = self::getCurrentTokenVar('scope');
        if ($scope) {
            if ($scope==ScopeEnum::User) {
                return true;
            } else {
                throw new ForbiddenException();
            }
        } else {
            throw new TokenException();
        }
    }

    /* 检测是否是一个合法操作
        当token令牌对应缓存的uid 与 被检测的uid(如: 传递过来的订单号查询出的user_id)不一致时, 认为不合法
    */
    public static function isValidOperate($checkUID)
    {
        if(!$checkUID){
            throw new Exception('检查UID时, 必须传入一个被检测的UID');
        }
        $currentOperateUID = self::getCurrentUid();
        if($currentOperateUID===$checkUID){
            return true;
        }
        return false;
    }

    /* 验证客户端的令牌是否有效 */
    public static function verifyToken($token)
    {
        $exist = Cache::get($token);
        if($exist){
            return true;
        }
        else{
            return false;
        }
    }
}
