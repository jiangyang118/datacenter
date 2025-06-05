<?php
/**
 * Time: 2024/8/8
 */

define('APP_PATH', __DIR__ . '/application/');
define("BIND_MODULE", "worker/WHeartRate");
// 加载环境变量
require __DIR__ . '/env.php';
// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';