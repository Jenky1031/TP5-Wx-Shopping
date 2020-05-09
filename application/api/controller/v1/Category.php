<?php


namespace app\api\controller\v1;

use app\api\model\Category as ModelCategory;
use app\api\validate\IDMustBePositiveInt;
use app\lib\exception\CategoryException;

class Category
{
    /*
     * 获取 所有商品分类
     * @url /category/all
     * @http GET
     */
    public function getAllCategories()
    {
        // 查询获取所有商品分类信息
        // $categories = ModelCategory::with('img')->select();
        $categories = ModelCategory::all([], 'img');
        if($categories->isEmpty()){
            throw new CategoryException();
        }
        return $categories;
    }
}
