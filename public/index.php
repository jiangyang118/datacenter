<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

//if(!empty($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_HOST'])) {
//    if(strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false) {
//        throw new Exception("拒绝跨站点请求");
//    }
//}
if($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']=='sxtyjdev.kxunpt.cn:8081/') {
    header("Location:http://sxtyjdev.kxunpt.cn:8081/dist");
    exit();
}
// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 加载环境变量
require __DIR__ . '/../env.php';
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
