<?php


namespace app\api\controller\v1;

use app\api\model\Banner as ModelBanner;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\BannerMissException;

class Banner
{
    /*
     * 获取指定 id 的 banner 信息
     * @url /banner/:id
     * @http GET
     * @id banner 的 id 号
     */
    public function getBanner($id)
    {
        // 验证 $id 是否为 正整数
        (new IDMustBePositiveInt())->goCheck();

        // 获取 banner 接口需要返回的数据
            $banner = ModelBanner::getBannerByID($id);

        // 如果$banner不存在  没有该id 的banner信息
        if (!$banner) {
            throw new BannerMissException();
        }

        return $banner;
    }
}
