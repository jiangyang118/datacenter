<?php

namespace app\api\controller;

use app\base\ApiController;
use think\Exception;
use app\api\logic\Emgmeter as EmgmeterLogic;

class Emgmeter extends ApiController{
    private $logic;

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->logic = new EmgmeterLogic();
    }

    /**
     * 获取用户信息接口
     */
    public function userInfo() {
        try {
            $param = $this->request->param();
            $this->writeLog($param, "userinfo");
            $business = !empty($param['business']) ? $param['business'] : '';//商户唯一编号
            $storeid = !empty($param['storeid']) ? $param['storeid'] : '';//项目ID【必填】
            $testsiteid = !empty($param['testsiteid']) ? $param['testsiteid'] : '';//地点ID【必填】
            $devicetype = !empty($param['devicetype']) ? $param['devicetype'] : '';//上报数据的设备类型
            $devicecode = !empty($param['devicecode']) ? $param['devicecode'] : '';//设备唯一编号
            $memberid = !empty($param['memberid']) ? $param['memberid'] : '';//平台方用户ID
            if (empty($business) || empty($devicecode) || empty($memberid)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            //判断设备是否存在
            $device = $this->logic->get_device($business_config, $devicecode);
            if (empty($device)) {
                throw new Exception('设备不存在');
            }
            //查询用户信息
            $user_res = $this->logic->getStaff($business_config, $memberid);
            if (empty($user_res) || empty($user_res[0]) || empty($user_res[0][0])) {
                throw new Exception('查询用户不存在');
            }
            $user = $user_res[0][0];
            $data = [
                'memberid' => $memberid,
                'name' => $user['name'],
                'sex' => $user['sex'],
                'height' => $user['height'],
                'weight' => $user['weight'],
                'birthday' => $user['birthday']
            ];
            $msg = [
                'device' => $device['name'],
                'devicecode' => $devicecode,
                'memberid' => $memberid,
                'business' => $business,
            ];
            \app\common\QwRobot::pushMsgFormat($msg, $business_config['name'].'同步人员信息');
        } catch (Exception $ex) {
            $result = ['code' => 1, 'message' => '查询失败', 'details' => $ex->getMessage(), 'userinfo'=> null];
            $this->writeLog($result, "userinfo");
            return json($result);
        }
        $result = ['code' => 0, 'message' => '查询成功', 'details' => '查询成功', 'userinfo'=> $data];
        $this->writeLog($result, "userinfo");
        return json($result);
    }

    /**
     * 设备上传数据接口
     */
    public function userData() {
        try {
            $param = $this->request->param();
            $this->writeLog($param, "result");
            $business = !empty($param['business']) ? $param['business'] : '';//商户唯一编号
            $storeid = !empty($param['storeid']) ? $param['storeid'] : '';//项目ID【必填】
            $testsiteid = !empty($param['testsiteid']) ? $param['testsiteid'] : '';//地点ID【必填】
            $devicetype = !empty($param['devicetype']) ? $param['devicetype'] : '';//上报数据的设备类型
            $devicecode = !empty($param['devicecode']) ? $param['devicecode'] : '';//设备唯一编号
            $memberid = !empty($param['memberid']) ? $param['memberid'] : '';//平台方用户ID
            $intelId = !empty($param['intelId']) ? $param['intelId'] : '';//患者检查单id【必填】
            $name = !empty($param['name']) ? $param['name'] : '';//患者姓名【必填】
            $phone = !empty($param['phone']) ? $param['phone'] : '';//手机号
            $cardNo = !empty($param['cardNo']) ? $param['cardNo'] : '';//身份证号
            $age = !empty($param['age']) ? $param['age'] : '';//年龄
            $user = !empty($param['user']) ? $param['user'] : '';//患者病历号【必填】
            $sex = !empty($param['sex']) ? $param['sex'] : '';//性别【1男 2女】
            $height = !empty($param['height']) ? $param['height'] : '';//身高【单位:cm】
            $weight = !empty($param['weight']) ? $param['weight'] : '';//体重【单位:kg】
            $bmi = !empty($param['bmi']) ? $param['bmi'] : '';//bmi
            $birthday = !empty($param['birthday']) ? $param['birthday'] : '';//出生日期【格式:yyyy-MM-dd】
            $docname = !empty($param['docname']) ? $param['docname'] : '';//检查医生姓名
            $hosname = !empty($param['hosname']) ? $param['hosname'] : '';//检查机构名称
            $resultJson = !empty($param['resultJson']) ? $param['resultJson'] : '';//测试结果json字符串
            if (empty($business) || empty($devicecode) || empty($memberid)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            //判断设备是否存在
            $device = $this->logic->get_device($business_config, $devicecode);
            if (empty($device)) {
                throw new Exception('设备不存在');
            }
            //查询用户信息
            $user_res = $this->logic->getStaff($business_config, $memberid);
            if (empty($user_res) || empty($user_res[0]) || empty($user_res[0][0])) {
                throw new Exception('查询用户不存在');
            }
            $data = [
                'business' => $business,
                'storeid' => $storeid,
                'testsiteid' => $testsiteid,
                'devicetype' => $devicetype,
                'devicecode' => $devicecode,
                'memberid' => $memberid,
                'intelId' => $intelId,
                'name' => $name,
                'phone' => $phone,
                'cardNo' => $cardNo,
                'age' => $age,
                'user' => $user,
                'sex' => $sex,
                'height' => $height,
                'weight' => $weight,
                'bmi' => $bmi,
                'birthday' => $birthday,
                'docname' => $docname,
                'hosname' => $hosname,
                'resultJson' => $resultJson,
            ];
            //插入数据库
            $res = $this->logic->uploadUserData($business_config, $data);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
            $examid = $res['examid'];
            $msg = [
                'device' => $device['name'],
                'business' => $business,
                'memberid' => $memberid,
                'examid' => $examid,
            ];
            \app\common\QwRobot::pushMsgFormat($msg, $business_config['name'].'测试结果上传');
        } catch (Exception $ex) {
            $result = ['code' => 1, 'message' => '上传失败', 'details' => $ex->getMessage(), 'examid'=> null];
            $this->writeLog($result, "result");
            return json($result);
        }
        $result = ['code' => 0, 'message' => '上传成功', 'details' => '上传成功', 'examid'=> $examid];
        $this->writeLog($result, "result");
        return json($result);
    }

    /**
     * 用户报告上传接口
     */
    public function userFile() {
        try {
            $file = $this->request->file('file');
            $param = $this->request->param();
            $this->writeLog($param, "report");
            $business = !empty($param['business']) ? $param['business'] : '';//商户唯一编号
            $storeid = !empty($param['storeid']) ? $param['storeid'] : '';//项目ID【必填】
            $testsiteid = !empty($param['testsiteid']) ? $param['testsiteid'] : '';//地点ID【必填】
            $devicetype = !empty($param['devicetype']) ? $param['devicetype'] : '';//上报数据的设备类型
            $devicecode = !empty($param['devicecode']) ? $param['devicecode'] : '';//设备唯一编号
            $examid = !empty($param['examid']) ? $param['examid'] : '';//测试结果唯一编号
            if (empty($business) || empty($devicecode) || empty($examid)) {
                throw new Exception('缺少参数');
            }
            if(empty($file)) {
                throw new Exception('缺少文件参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            //判断设备是否存在
            $device = $this->logic->get_device($business_config, $devicecode);
            if (empty($device)) {
                throw new Exception('设备不存在');
            }
            $data = [
                'business' => $business,
                'storeid' => $storeid,
                'testsiteid' => $testsiteid,
                'devicetype' => $devicetype,
                'devicecode' => $devicecode,
                'examid' => $examid,
            ];
            $res = $this->logic->addFile($file, $data, $business_config);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
            $msg = [
                'device' => $device['name'],
                'business' => $business,
                'examid' => $examid,
            ];
            \app\common\QwRobot::pushMsgFormat($msg, $business_config['name'].'测试报告上传');
        } catch (Exception $ex) {
            $result = ['code' => 1, 'message' => '上传失败', 'details' => $ex->getMessage()];
            $this->writeLog($result, "report");
            return json($result);
        }
        $result = ['code' => 0, 'message' => '上传成功', 'details' => '上传成功'];
        $this->writeLog($result, "report");
        return json($result);
    }
    
    /**
     * 记录日志
     * @param type $text
     * @param type $filename
     */
    public function writeLog($text, $filename='emgmeter') {
        if(is_array($text)) {
            $text = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $dir = $filePath = ROOT_PATH . 'public' . DS . 'static' . DS . 'upload' . DS .'emgmeter' . DS . date("Y-m-d");
        !is_dir($dir) AND mkdir($dir, 0777, true);
        $s = date("Y-m-d H:i:s") . "\t". $text . "\r\n";
        file_put_contents($dir . "/{$filename}.txt", $s, FILE_APPEND );
   }
}
