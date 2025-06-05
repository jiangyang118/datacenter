<?php
use think\Route;

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/*-- api路由 --*/
Route::group('api',function (){
    Route::group(['name' => 'action', 'prefix' => 'api/Action'], function (){});
    //领康控制器
    Route::group(['name' => 'channel', 'prefix' => 'api/Channel'], function (){
        //同步人员接口
        Route::post('/sync-user-data', '/syncStaff');
        //数据上传接口
        Route::post('/upload-user-data', '/uploadUserData');
    });
});
Route::rule([
    ':business/channel/sync-user-data' => 'api/Channel/syncStaff',
    ':business/channel/upload-user-data' => 'api/Channel/uploadUserData',
    ':business/emgmeter/userinfo' => 'api/Emgmeter/userInfo',
    ':business/emgmeter/userdata' => 'api/Emgmeter/userData',
    ':business/emgmeter/userfile' => 'api/Emgmeter/userFile',
    ':business/Inbody770/getUserInfo' => 'api/Inbody770/getUserInfo',
    ':business/Inbody770/setInbodyData' => 'api/Inbody770/setInbodyData',
    ':business/Inbody770/setInbodyImage' => 'api/Inbody770/setInbodyImage',
    ':business/tieren/userlogin' => 'api/Tieren/userLogin',
    ':business/tieren/facelogin' => 'api/Tieren/faceLogin',
    ':business/tieren/devicestatus' => 'api/Tieren/deviceStatus',
    ':business/tieren/deviceqrurl' => 'api/Tieren/deviceQrurl',
    ':business/tieren/deviceprocess' => 'api/Tieren/deviceProcess',
    ':business/tieren/deviceresult' => 'api/Tieren/deviceResult',
    ':business/tieren/devicetest' => 'api/Tieren/deviceTest',
    ':business/polar/code' => 'api/Polar/code',
    ':business/polar/getToken' => 'api/Polar/getToken',
    ':business/polar/getTeams' => 'api/Polar/getTeams',
    ':business/polar/getTeamDetails' => 'api/Polar/getTeamDetails',
    ':business/polar/getTeamTrainingSessions' => 'api/Polar/getTeamTrainingSessions',
    ':business/polar/getTeamTrainingSessionDetails' => 'api/Polar/getTeamTrainingSessionDetails',
    ':business/polar/getPlayerTrainingSessions' => 'api/Polar/getPlayerTrainingSessions',
    ':business/polar/getPlayerTrainingSessionDetails' => 'api/Polar/getPlayerTrainingSessionDetails',
    ':business/polar/getPlayerTrainingSessionSummary' => 'api/Polar/getPlayerTrainingSessionSummary',
]);

