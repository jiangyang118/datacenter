<?php

namespace app\api\controller;

use app\base\ApiController;
use think\Exception;
use app\api\logic\Channel as ChannelLogic;

class Channel extends ApiController{
    private $logic;

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->logic = new ChannelLogic();
    }

    /**
     * 同步人员数据接口
     *
     * $student = [
     *    'icNumber' => null,//一卡通卡号
     *    'checkNumber' => '1-11-1-678-244-719',//编排后组号
     *    'sex_dictText' => '男',//性别 男/女
     *    'sex' => 1,//性别 1男2女【必要】
     *    'className' => '班级名称1',//班级名称【必要】
     *    'examNumber' => 'USS202209010004',//学号(学籍号)，唯一标示【必要】 (作为识别学生使用)
     *    'gradeNumber' => 11,//年级编号【必要】11~16:小学1~6年级 21~23:初中1~3年级 31~33:高中1~3年级 41~44:大学1~4年级
     *    'identityNumber' => null,//身份证号
     *    'studentName' => '学生1',//学生姓名【必要】
     *    'classNo' => '',//班级编号
     *    'planId' => $partnerDeviceId,//计划编码【必要】与partnerDeviceId相同
     *    'schoolName' => '海淀区第二小学',//学校名称【必要】
     *    'groupNo' => 1,//组别
     *    'studentPhoto' => ''//头像路径
     * ];
     */
    public function syncStaff() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            switch ($business) {
                case 'jxxxyy'://体健之星
                case 'tatx'://山东泰安体校体健之星
                    break;
                case 'tnxl';//业务系统
                    $res = $this->syncStaffTnxl();
                    return $res;
                    break;
                default:
                    throw new Exception('商户不存在');
                    break;
            }
            $deviceId = !empty($param['deviceId']) ? $param['deviceId'] : '';
            $partnerDeviceId = !empty($param['partnerDeviceId']) ? $param['partnerDeviceId'] : '';
            $pageNo = !empty($param['pageNo']) ? $param['pageNo'] : '1';
            $pageSize = !empty($param['pageSize']) ? $param['pageSize'] : '1000';
            if (empty($business) || empty($deviceId) || empty($partnerDeviceId)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            if ($partnerDeviceId != '1111') {
                throw new Exception('计划编码不存在');
            }
            $device = $this->logic->get_device($business_config, $deviceId);
            if (empty($device)) {
                throw new Exception('设备不存在');
            }
            $student_res = $this->logic->syncStaff($business_config, $pageNo, $pageSize);
            $total = [];
            $students = [];
            $records = [];
            if (!empty($student_res)) {
                $total = !empty($student_res[0][0]) ? $student_res[0][0] : [];
                $students = !empty($student_res[1]) ? $student_res[1] : [];
            }
            if (!empty($students)) {
                foreach ($students as $student) {
                    //sex：0女1男
                    $record = [
                        'icNumber' => null,//一卡通卡号
                        'checkNumber' => '',//编排后组号
                        'sex_dictText' => $student['sex'] == 1 ? '男' : '女',//性别 男/女
                        'sex' => $student['sex'] == 1 ? '1' : '2',//性别 1男2女【必要】
                        'className' => $student['ClassName'],//班级名称【必要】
                        'examNumber' => $student['student_id'],//学号(学籍号)，唯一标示【必要】 (作为识别学生使用)
                        'gradeNumber' => $student['grade_id'],//年级编号【必要】11~16:小学1~6年级 21~23:初中1~3年级 31~33:高中1~3年级 41~44:大学1~4年级
                        'identityNumber' => null,//身份证号
                        'studentName' => $student['name'],//学生姓名【必要】
                        'classNo' => $student['class_id'],//班级编号
                        'planId' => $partnerDeviceId,//计划编码【必要】与partnerDeviceId相同
                        'schoolName' => $student['FirmName'],//学校名称【必要】
                        'groupNo' => 1,//组别
                        'studentPhoto' => ''//头像路径
                    ];
                    $records[] = $record;
                }
            }
            $data = [];
            $data['records'] = $records;
            $data['total'] = !empty($total['Records']) ? $total['Records'] : 0;//总数据量【必要】
            $data['size'] = $pageSize;//当前页大小【必要】
            $data['current'] = $pageNo;//当前页【必要】
            $data['orders'] = [];
            $data['searchCount'] = true;
            $data['pages'] = !empty($total['TotalCount']) ? $total['TotalCount'] : 0;//总页数【必要】
            $res = [
                'success' => true,
                'message' => '获取成功',
                'data' => '',//头像地址前缀
                'code' => 200,
                'result' => $data,
                'students' => null,
                'response' => null,
                'otherData' => null,
                'timestamp' => time()
            ];
            $msg = [
                '客户' => $business_config['name'],
                '设备名称' => $device['name'],
                '同步人员总条数' => count($records),
                'business' => $business
            ];
            \app\common\QwRobot::pushMsgFormat($msg, '领康设备同步人员信息');
        } catch (Exception $ex) {
            $res = [
                'success' => false,
                'message' => $ex->getMessage(),
                'data' => '',//头像地址前缀
                'code' => 500,
                'result' => [
                    'records' => []
                ],
                'students' => null,
                'response' => null,
                'otherData' => null,
                'timestamp' => time()
            ];
        }
        return json($res);
    }

    /**
     * 测试结果上传接口
     *
     * 入参
     *{
     *   "androidVersion":"7.1.2",//安卓系统版本
     *   "appId":"1002",//固定 1002
     *   "appMac":"20:57:9e:57:c0:14",//设备 MAC 地址
     *   "appVersion":"2.85",//应用版本
     *   "checkSignCode":"760d4ec57eda06d0",//计划编码(加密后)第三方不需要关注
     *   "dataList":[//成绩数组
     *       {
     *          "achievement":"1703",//成绩
     *          "deviceNo":"233b7ea7e84703c209a95a76e86a3ecf3",//设备唯一标示
     *          "endTime":"2023-11-21 10:52:36",//测试结束时间
     *          "examNumber":"USS202209010004",//学生学号
     *          "planId":"1111",//测试计划编码
     *          "projectNumber":"E007",//项目编号
     *          "remark":"2023-11-21 10:52:36",//测试完成时间
     *          "startTime":"2023-11-21 10:52:07"//开始测试时间
     *       }
     *   ],
     *   "deviceId":"233b7ea7e84703c209a95a76e86a3ecf3",//设备唯一标示
     *   "firmwareVersion":"rk3288",//主板厂商型号 如:HUAWEI
     *   "osModel":"android",//系统
     *   "partnerDeviceId":"1111",//第三方平台校验使用
     *   "projectNumber":"E007",//项目编码 参考” 状态编码和成绩格式”中的编 码
     *   "timestamp":1700535156286,
     *   "type":"red",//设备类型
     *   "business":"jxxxyy"
     *}
     *
     */
    public function uploadUserData() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            switch ($business) {
                case 'jxxxyy'://体健之星
                case 'tatx'://山东泰安体校体健之星
                    break;
                case 'tnxl';//业务系统
                    $res = $this->uploadUserDataTnxl();
                    return $res;
                    break;
                default:
                    throw new Exception('商户不存在');
                    break;
            }
            $deviceId = !empty($param['deviceId']) ? $param['deviceId'] : '';
            $dataList = !empty($param['dataList']) ? $param['dataList'] : [];
            if (empty($business) || empty($deviceId) || empty($dataList)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            $device = $this->logic->get_device($business_config, $deviceId);
            if (empty($device)) {
                throw new Exception('设备不存在');
            }
            //解析需要的数据
            $data = [];
            foreach ($dataList as $item) {
                $data[] = [
                    'achievement' => !empty($item['achievement']) ? $item['achievement'] : '',//成绩
                    'deviceNo' => !empty($item['deviceNo']) ? $item['deviceNo'] : '',//设备唯一标示
                    'examNumber' => !empty($item['examNumber']) ? $item['examNumber'] : '',//学生学号
                    'projectNumber' => !empty($item['projectNumber']) ? $item['projectNumber'] : '',//项目编号
                    'remark' => !empty($item['remark']) ? $item['remark'] : '',//测试完成时间
                    'planId' => !empty($item['planId']) ? $item['planId'] : '',//测试计划编码
                    'startTime' => !empty($item['startTime']) ? $item['startTime'] : '',//开始测试时间
                    'endTime' => !empty($item['endTime']) ? $item['endTime'] : '',//测试结束时间
                    'business' => $business,
                ];
            }
            $res = $this->logic->uploadUserData($business_config, $data);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
            $msg = [
                '客户' => $business_config['name'],
                '设备名称' => $device['name'],
                '测试结果上传总条数' => count($data),
                'business' => $business
            ];
            \app\common\QwRobot::pushMsgFormat($msg, '领康设备测试结果上传');
        } catch (Exception $ex) {
            return json(['success' => false,'message' => $ex->getMessage(), 'data'=>[], 'code' => 2]);
        }
        return json(['success' => 'true', 'message' => '成绩正在处理', 'data' => '操作成功' ,
            'code'=>0,'result'=>null,'students'=>null,'response'=>null,'otherData'=>null,'timestamp'=>time()]);
    }
    /**
     * 同步人员数据接口-业务系统
     */
    public function syncStaffTnxl() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            $deviceId = !empty($param['deviceId']) ? $param['deviceId'] : '';
            $partnerDeviceId = !empty($param['partnerDeviceId']) ? $param['partnerDeviceId'] : '';
            $pageNo = !empty($param['pageNo']) ? $param['pageNo'] : '1';
            $pageSize = !empty($param['pageSize']) ? $param['pageSize'] : '1000';
            if (empty($business) || empty($deviceId) || empty($partnerDeviceId)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            if ($partnerDeviceId != '1111') {
                throw new Exception('计划编码不存在');
            }
            $device = $this->logic->get_device_tnxl($business_config, $deviceId);
            if (empty($device)) {
                throw new Exception('设备不存在');
            }
            $student_res = $this->logic->syncStaffTnxl($business_config, $pageNo, $pageSize);
            $total = $student_res['count'];
            $students = $student_res['rows'];
            $records = [];
            if (!empty($students)) {
                foreach ($students as $student) {
                    $record = [
                        'icNumber' => null,//一卡通卡号
                        'checkNumber' => '',//编排后组号
                        'sex_dictText' => $student['sex'] == 1 ? '男' : '女',//性别 男/女
                        'sex' => $student['sex'] == 1 ? '1' : '2',//性别 1男2女【必要】
                        'className' => '体能大比武班',//班级名称【必要】
                        'examNumber' => $student['code'],//学号(学籍号)，唯一标示【必要】 (作为识别学生使用)
                        'gradeNumber' => 41,//年级编号【必要】11~16:小学1~6年级 21~23:初中1~3年级 31~33:高中1~3年级 41~44:大学1~4年级
                        'identityNumber' => null,//身份证号
                        'studentName' => $student['name'],//学生姓名【必要】
                        'classNo' => '',//班级编号
                        'planId' => $partnerDeviceId,//计划编码【必要】与partnerDeviceId相同
                        'schoolName' => '体能大比武学校',//学校名称【必要】
                        'groupNo' => 1,//组别
                        'studentPhoto' => ''//头像路径
                    ];
                    $records[] = $record;
                }
            }
            $data = [];
            $data['records'] = $records;
            $data['total'] = $total;//总数据量【必要】
            $data['size'] = $pageSize;//当前页大小【必要】
            $data['current'] = $pageNo;//当前页【必要】
            $data['orders'] = [];
            $data['searchCount'] = true;
            $data['pages'] = ceil($total / $pageSize);//总页数【必要】
            $res = [
                'success' => true,
                'message' => '获取成功',
                'data' => '',//头像地址前缀
                'code' => 200,
                'result' => $data,
                'students' => null,
                'response' => null,
                'otherData' => null,
                'timestamp' => time()
            ];
            $msg = [
                '客户' => $business_config['name'],
                '设备名称' => $device['name'],
                '同步人员总条数' => count($records),
                'business' => $business
            ];
            \app\common\QwRobot::pushMsgFormat($msg, '领康设备同步人员信息');
        } catch (Exception $ex) {
            $res = [
                'success' => false,
                'message' => $ex->getMessage(),
                'data' => '',//头像地址前缀
                'code' => 500,
                'result' => [
                    'records' => []
                ],
                'students' => null,
                'response' => null,
                'otherData' => null,
                'timestamp' => time()
            ];
        }
        return json($res);
    }

    /**
     * 测试结果上传接口-业务系统
     */
    public function uploadUserDataTnxl() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            $deviceId = !empty($param['deviceId']) ? $param['deviceId'] : '';
            $dataList = !empty($param['dataList']) ? $param['dataList'] : [];
            if (empty($business) || empty($deviceId) || empty($dataList)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            $device = $this->logic->get_device_tnxl($business_config, $deviceId);
            if (empty($device)) {
                throw new Exception('设备不存在');
            }
            //解析需要的数据
            $data = [];
            foreach ($dataList as $item) {
                $data[] = [
                    'achievement' => !empty($item['achievement']) ? $item['achievement'] : '',//成绩
                    'deviceNo' => !empty($item['deviceNo']) ? $item['deviceNo'] : '',//设备唯一标示
                    'examNumber' => !empty($item['examNumber']) ? $item['examNumber'] : '',//学生学号
                    'projectNumber' => !empty($item['projectNumber']) ? $item['projectNumber'] : '',//项目编号
                    'remark' => !empty($item['remark']) ? $item['remark'] : '',//测试完成时间
                    'planId' => !empty($item['planId']) ? $item['planId'] : '',//测试计划编码
                    'startTime' => !empty($item['startTime']) ? $item['startTime'] : '',//开始测试时间
                    'endTime' => !empty($item['endTime']) ? $item['endTime'] : '',//测试结束时间
                    'business' => $business,
                ];
            }
            $res = $this->logic->uploadUserDataTnxl($business_config, $data, $device);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
            $msg = [
                '客户' => $business_config['name'],
                '设备名称' => $device['name'],
                '测试结果上传总条数' => count($data),
                'business' => $business
            ];
            \app\common\QwRobot::pushMsgFormat($msg, '领康设备测试结果上传');
        } catch (Exception $ex) {
            return json(['success' => false,'message' => $ex->getMessage(), 'data'=>[], 'code' => 2]);
        }
        return json(['success' => 'true', 'message' => '成绩正在处理', 'data' => '操作成功' ,
            'code'=>0,'result'=>null,'students'=>null,'response'=>null,'otherData'=>null,'timestamp'=>time()]);
    }

}
