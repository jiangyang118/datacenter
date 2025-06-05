<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

$config = [
    // 数据库类型
    'type'            => 'mysql',
    // 服务器地址
    'hostname'        => '101.200.165.56',
    // 数据库名
    'database'        => 'data_center',
    // 用户名
    'username'        => 'wwwweb',
    // 密码
    'password'        => 'Cpt2018@)!(',
    // 端口
    'hostport'        => '3306',
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8',
    // 数据库表前缀
    'prefix'          => 'ydy_',
    // 数据库调试模式
    'debug'           => true,
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'deploy'          => 0,
    // 数据库读写是否分离 主从式有效
    'rw_separate'     => false,
    // 读写分离后 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 自动读取主库数据
    'read_master'     => false,
    // 是否严格检查字段是否存在
    'fields_strict'   => true,
    // 数据集返回类型
    'resultset_type'  => 'array',
    // 自动写入时间戳字段
    'auto_timestamp'  => false,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 是否需要进行SQL性能分析
    'sql_explain'     => false,
    //SQLServer
    'sqlsrv' => [
        // 数据库类型
        'type'            => 'sqlsrv',
        // 服务器地址
        'hostname'        => 'rm-2ze9w0z3tcr5lku2u4o.sqlserver.rds.aliyuncs.com',
        // 数据库名
        'database'        => 'tn_anhui_lis',
        // 用户名
        'username'        => 'rooot',
        // 密码
        'password'        => 'cpt2018)!(',
        // 端口
        'hostport'        => '1433',
        // 连接dsn
        'dsn'             => '',
        // 数据库连接参数
        'params'          => [
            PDO::ATTR_CASE              => PDO::CASE_NATURAL,
            PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 数据库表前缀
        'prefix'          => '',
        // 数据库调试模式
        'debug'           => false,
        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'deploy'          => 0,
        // 数据库读写是否分离 主从式有效
        'rw_separate'     => false,
        // 读写分离后 主服务器数量
        'master_num'      => 1,
        // 指定从服务器序号
        'slave_no'        => '',
        // 自动读取主库数据
        'read_master'     => false,
        // 是否严格检查字段是否存在
        'fields_strict'   => true,
        // 数据集返回类型
        'resultset_type'  => 'array',
        // 自动写入时间戳字段
        'auto_timestamp'  => false,
        // 时间字段取出后的默认时间格式
        'datetime_format' => 'Y-m-d H:i:s',
        // 是否需要进行SQL性能分析
        'sql_explain'     => false,
    ]
];
//根据环境变量加载配置文件
//商户标识
$business = '';
if (TP_ENV == 'prod' && defined(BUSINESS) && BUSINESS != 'BUSINESS_VALUE') {
    $business = '/'. BUSINESS;
}
$conf_extend = require APP_PATH . 'config/'. TP_ENV . $business . '/database.php';
return array_merge($config, $conf_extend);
