<?php

use think\Route;

/*
    参数一: API后缀, 参数二: 控制器方法的位置, 参数三: 请求方法
    Route::rule('hello', 'sample/test/hello','get|post',['https'=>true]);
    Route::get('hello/:id','sample/test/hello');
    模块名 控制器名 操作方法名
    错误写法
    Route::get('banner/:id','api/controller/v1/Banner/getBanner');
    Route::get('banner/:id','api/Banner/getBanner');
    因为多了 v1 子目录, tp5设计 v1.Banner表示控制器名
    Route::get('api/v1/banner/:id','api/v1.Banner/getBanner');
    使用:version变量, 根据传递过来的url的版本号, 动态对接不同版本控制器
*/
Route::get('api/:version/banner/:id', 'api/:version.Banner/getBanner');

Route::get('api/:version/theme', 'api/:version.Theme/getSimpleList');
Route::get('api/:version/theme/:id', 'api/:version.Theme/getComplexOne');

Route::get('api/:version/product/recent', 'api/:version.Product/getRecent');
Route::get('api/:version/product/by_category', 'api/:version.Product/getAllInCategory');
Route::get('api/:version/product/:id', 'api/:version.Product/getOne',[],['id'=>'\d+']);

Route::get('api/:version/category/all', 'api/:version.Category/getAllCategories');

Route::post('api/:version/token/user', 'api/:version.Token/getToken');
Route::post('api/:version/token/verify', 'api/:version.Token/verifyToken');

Route::post('api/:version/address', 'api/:version.Address/createOrUpdateAddress');
Route::get('api/:version/address', 'api/:version.Address/getUserAddress');

Route::post('api/:version/order', 'api/:version.Order/placeOrder');
Route::get('api/:version/order/:id', 'api/:version.Order/getDetail',[], ['id'=>'\d+']);

Route::get('api/:version/order/by_user', 'api/:version.Order/getSummaryByUser');


Route::post('api/:version/pay/pre_order', 'api/:version.Pay/getPreOrder');
Route::post('api/:version/pay/notify', 'api/:version.Pay/receiveNotify');

/* 路由分组 效率高一些 */
// Route::group('api/:version/product',function(){
//     Route::get('/by_category','api/:version.Product/getAllInCategory');
//     Route::get('/recent','api/:version.Product/getRecent');
//     Route::get('/:id','api/:version.Product/getOne',[],['id'=>'\d+']);
// });

Route::get('api/:version/order/changeStatus/:id', 'api/:version.Order/changeOrderStatus',[], ['id'=>'\d+']);

Route::post('api/:version/token/app', 'api/:version.Token/getAppToken');

Route::get('api/:version/order/paginate', 'api/:version.Order/getSummary');

// Route::get('api/:version/second', 'api/:version.Address/second');

//不想把所有查询都写在一起，所以增加by_user，很好的REST与RESTFul的区别



