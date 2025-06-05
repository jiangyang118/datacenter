<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

return [
    'app\cron\command\Syncbc5385crpData',
    'app\cron\command\SyncTableData',//tablestore数据同步
    'app\cron\command\OCRCheck',
    'app\cron\command\SyncData',//数据同步
    'app\cron\command\SyncSqlData',//SqlServer数据同步
    'app\cron\command\SyncSqlDataV1',//SqlServer数据同步：按输入样本号与人员对应
    'app\cron\command\SyncAnalysisForceTxt',//数据同步
    'app\cron\command\Monitor',//--监控消息
    'app\cron\command\SyncKX21NData',//--kx21n同步
    'app\cron\command\SyncCentaurData',//--西门子ADVIA Centaur CP同步
    'app\cron\command\SyncHitachiData',//--日立7100同步
    'app\cron\command\SyncK5Data',//--k5心肺设备同步
    'app\cron\command\SyncCortex',//--心肺功能测试仪德国Cortex，xml数据解析
    'app\cron\command\SyncOmegaWaveData',//--omegawave竞技状态综合诊断系统
    'app\cron\command\AmqpTest',//--amqp测试
    'app\cron\command\SyncHighermed',//--瀚雅心肺功能测试仪 excel解析
    'app\cron\command\SyncFirstbeatData',//--firstbeat设备对接
    'app\cron\command\SyncSqlDataGuizhou',//--贵州机能设备-希森美康、P8088
    'app\cron\command\SyncBreezingPro',//--BreezingPro能量代谢测试仪，csv数据解析
    'app\cron\command\SyncPolarData',//--Polar
    'app\cron\command\SyncDxh500Data',//--贝克曼全自动血液分析仪DxH500-秦皇岛
    'app\cron\command\SyncUrit8400Data',//--优利特全自动生化分析仪8400-秦皇岛
];
