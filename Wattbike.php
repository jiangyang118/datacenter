<?php
/**
 * Created by PengJu
 * User: RenPengJu
 * Motto: 现在的努力是为了小时候吹过的牛逼
 * Time: 2024/8/1/16:25
 */

define('APP_PATH', __DIR__ . '/application/');
define("BIND_MODULE", "worker/Wattbike");
// 加载环境变量
require __DIR__ . '/env.php';
// 加载框架引导文件
require __DIR__ . '/thinkphp/start.php';