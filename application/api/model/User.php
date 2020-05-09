<?php

namespace app\api\model;

class User extends BaseModel
{
    /* 定义与user_address表的关联关系 */
    public function address()
    {
        return $this->hasOne('UserAddress', 'user_id', 'id');
    }

    public static function getByOpenID($openid)
    {
        $user = self::where('openid', '=', $openid)->find();
        return $user;
    }
}
