<?php


namespace app\api\controller;

use think\Controller;
use app\api\service\Token as ServiceToken;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\CategoryException;

class BaseController extends Controller
{
    protected function checkPrimaryScope()
    {
        $result = ServiceToken::needPrimaryScope();
    }

    protected function checkExclusiveScope()
    {
        $result = ServiceToken::needExclusiveScope();
    }
}
