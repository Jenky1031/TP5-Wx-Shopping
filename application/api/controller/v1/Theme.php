<?php


namespace app\api\controller\v1;

use app\api\validate\IDCollection;
use app\api\validate\IDMustBePositiveInt;
use app\api\model\Theme as ModelTheme;
use app\lib\exception\ThemeException;

class Theme
{
    /*
        获取 theme 列表的 简要信息
        @url /theme?ids=id1,id2,id3...
        @return 一组 theme 模型
    */
    public function getSimpleList($ids='')
    {
        // 验证参数
        (new IDCollection())->goCheck();
        // 将 ids 转为 数组
        $ids = explode(',', $ids);
        // 查询 theme 列表
        $themes = ModelTheme::getThemesByIDs($ids);
        // 判空 抛出异常
        if ($themes->isEmpty()) {
            throw new ThemeException();
        }
        return $themes;
    }

    /*
        获取 theme 其中一个的 详情信息
        @url theme/:id
    */
    public function getComplexOne($id)
    {
        // 验证参数是否是正整数
        (new IDMustBePositiveInt())->goCheck();
        // 查询 某个 theme 对应的 products 商品信息
        $theme =  ModelTheme::getThemeWithProducts($id);
        if (!$theme) {
            throw new ThemeException();
        }
        return $theme;
    }
}
