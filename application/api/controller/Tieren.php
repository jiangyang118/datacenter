<?php
/**
 * 铁人设备相关接口
 */
namespace app\api\controller;

use app\base\ApiController;
use think\Exception;
use app\api\logic\Tieren as TierenLogic;

class Tieren extends ApiController {
    private $logic;

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->logic = new TierenLogic();
    }

    /**
     * 账号登录接口
     */
    public function userLogin() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            $userCode = !empty($param['userCode']) ? $param['userCode'] : '';
            $password = !empty($param['password']) ? $param['password'] : '';
            $signInType = !empty($param['signInType']) ? $param['signInType'] : '';
            if (empty($business) || empty($userCode) || empty($password)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            $user = $this->logic->inquiryOne($business_config, 'user', ['account' => $userCode]);
            if (empty($user)) {
                throw new Exception('账号或密码错误！');
            }
            if (empty($user['status'])) {
                throw new Exception('账号已禁用');
            }
            if (get_password($password, $user['salt']) != $user['pwd']) {
                throw new Exception('账号或密码错误');
            }
            //验证账号是否在有效期内
            $now_date = date('Y-m-d');
            if ($user['start_date'] || $user['end_date']) {
                if ($user['start_date'] && $user['end_date'] && $user['start_date'] != '0000-00-00' && $user['end_date'] != '0000-00-00') {
                    if ($now_date < $user['start_date'] || $now_date > $user['end_date']) {
                        throw new Exception('账号不在有效期内！');
                    }
                } else if ($user['start_date'] && $user['start_date'] != '0000-00-00') {
                    if ($now_date < $user['start_date']) {
                        throw new Exception('账号未到有效期！');
                    }
                } else if ($user['end_date'] && $user['end_date'] != '0000-00-00') {
                    if ($now_date > $user['end_date']) {
                        throw new Exception('账号已过期！');
                    }
                }
            }
            if (empty($user['staff_uuid'])) {
                throw new Exception('人员不存在');
            }
            $staff_where = [
                'uuid' => $user['staff_uuid'],
                'status' => 1,
                'del_flag' => 0,
            ];
            $staff = $this->logic->inquiryOne($business_config, 'staff', $staff_where);
            if (empty($staff)) {
                throw new Exception('人员信息不存在');
            }
            $userInfo = [
                'userCode' => $staff['id'],
                'nickName' => $staff['name'],
                'name' => $staff['name'],
                'avatarUrl' => $staff['head_img'],
                'sex' => intval($staff['sex']),
                'birthday' => $staff['birthday'],
                'heartRate' => 0,
                'height' => floatval($staff['height']),
                'weight' => floatval($staff['weight']),
                'phone' => $staff['mobile'],
                'email' => $staff['email'],
            ];
            $data = [
                'clientId' => $business_config['tieren']['clientId'],
                'user' => $userInfo,
            ];
            $msg = [
                'userCode' => $userInfo['userCode'],
                'username' => $userInfo['name'],
                'business' => $business,
            ];
            \app\common\QwRobot::pushMsgFormat($msg, $business_config['name'].'铁人设备账号登录');
        } catch (Exception $ex) {
            $result = ['success' => false, 'errCode' => 1, 'errMessage' => $ex->getMessage(), 'data'=> null];
            return json($result);
        }
        $result = ['success' => true, 'errCode' => null, 'errMessage' => null, 'data'=> $data];
        return json($result);
    }

    /**
     * 人脸登录接口
     */
    public function faceLogin() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            $faceUrl = !empty($param['faceUrl']) ? $param['faceUrl'] : '';
            $faceType = !empty($param['faceType']) ? $param['faceType'] : '';
            if (empty($business) || empty($faceUrl) || empty($faceType)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            //人脸识别
            $res = $this->logic->searchFace($business_config, $faceUrl, $faceType);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
            $staff = $res['data']['staff'];
            $userInfo = [
                'userCode' => $staff['id'],
                'nickName' => $staff['name'],
                'name' => $staff['name'],
                'avatarUrl' => $staff['head_img'],
                'sex' => intval($staff['sex']),
                'birthday' => $staff['birthday'],
                'heartRate' => 0,
                'height' => floatval($staff['height']),
                'weight' => floatval($staff['weight']),
                'phone' => $staff['mobile'],
                'email' => $staff['email'],
            ];
            $data = [
                'clientId' => $business_config['tieren']['clientId'],
                'user' => $userInfo,
            ];
            $msg = [
                'userCode' => $userInfo['userCode'],
                'username' => $userInfo['name'],
                'business' => $business,
            ];
            \app\common\QwRobot::pushMsgFormat($msg, $business_config['name'].'铁人设备人脸登录');
        } catch (Exception $ex) {
            $result = ['success' => false, 'errCode' => 1, 'errMessage' => $ex->getMessage(), 'data'=> null];
            return json($result);
        }
        $result = ['success' => true, 'errCode' => null, 'errMessage' => null, 'data'=> $data];
        return json($result);
    }

    /**
     * 设备状态接口
     */
    public function deviceStatus() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            $deviceKey = !empty($param['deviceKey']) ? $param['deviceKey'] : '';
            $deviceStatus = !empty($param['deviceStatus']) ? $param['deviceStatus'] : '';
            if (empty($business) || empty($deviceKey) || empty($deviceStatus)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            $data = [
                'clientId' => $business_config['tieren']['clientId']
            ];
        } catch (Exception $ex) {
            $result = ['success' => false, 'errCode' => 1, 'errMessage' => $ex->getMessage(), 'data'=> null];
            return json($result);
        }
        $result = ['success' => true, 'errCode' => null, 'errMessage' => null, 'data'=> $data];
        return json($result);
    }

    /**
     * 获取设备二维码接口
     */
    public function deviceQrurl() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            $deviceKey = !empty($param['deviceKey']) ? $param['deviceKey'] : '';
            if (empty($business) || empty($deviceKey)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            $device = $this->logic->inquiryOne($business_config, 'equipment_hardware', ['device_id' => $deviceKey, 'is_show' => 1]);
            $data = [
                'clientId' => $business_config['tieren']['clientId'],
                'qrUrl' => !empty($device['qrurl']) ? $business_config['domain'].$device['qrurl'] : '',
            ];
        } catch (Exception $ex) {
            $result = ['success' => false, 'errCode' => 1, 'errMessage' => $ex->getMessage(), 'data'=> null];
            return json($result);
        }
        $result = ['success' => true, 'errCode' => null, 'errMessage' => null, 'data'=> $data];
        return json($result);
    }

    /**
     * 瞬时数据接口
     */
    public function deviceProcess() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            $deviceKey = !empty($param['deviceKey']) ? $param['deviceKey'] : '';
            $deviceName = !empty($param['deviceName']) ? $param['deviceName'] : '';
            $userCode = !empty($param['userCode']) ? $param['userCode'] : '';
            $typeCode = !empty($param['typeCode']) ? $param['typeCode'] : '';
            $typeName = !empty($param['typeName']) ? $param['typeName'] : '';
            $data = !empty($param['data']) ? $param['data'] : '';
            $model = !empty($param['model']) ? $param['model'] : '';
            if (empty($business) || empty($deviceKey) || empty($deviceName) || empty($userCode) || empty($typeCode)
                || empty($typeName) || empty($data)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            $device_data = [
                'business' => $business,
                'deviceKey' => $deviceKey,
                'deviceName' => $deviceName,
                'userCode' => $userCode,
                'typeCode' => $typeCode,
                'typeName' => $typeName,
                'data' => $data,
                'model' => $model,
            ];
            $res = $this->logic->deviceProcess($business_config, $device_data);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
        } catch (Exception $ex) {
            $result = ['success' => false, 'errCode' => 1, 'errMessage' => $ex->getMessage()];
            return json($result);
        }
        $result = ['success' => true, 'errCode' => null, 'errMessage' => null];
        return json($result);
    }

    /**
     * 结果数据接口
     */
    public function deviceResult() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            $deviceKey = !empty($param['deviceKey']) ? $param['deviceKey'] : '';
            $deviceName = !empty($param['deviceName']) ? $param['deviceName'] : '';
            $userCode = !empty($param['userCode']) ? $param['userCode'] : '';
            $typeCode = !empty($param['typeCode']) ? $param['typeCode'] : '';
            $typeName = !empty($param['typeName']) ? $param['typeName'] : '';
            $data = !empty($param['data']) ? $param['data'] : '';
            $model = !empty($param['model']) ? $param['model'] : '';
            if (empty($business) || empty($deviceKey) || empty($deviceName) || empty($userCode) || empty($typeCode)
                || empty($typeName) || empty($data)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            $device_data = [
                'business' => $business,
                'deviceKey' => $deviceKey,
                'deviceName' => $deviceName,
                'userCode' => $userCode,
                'typeCode' => $typeCode,
                'typeName' => $typeName,
                'data' => $data,
                'model' => $model,
            ];
            $res = $this->logic->deviceResult($business_config, $device_data);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
            $msg = [
                'userCode' => $userCode,
                'username' => $res['data']['staff']['name'],
                'deviceKey' => $deviceKey,
                'deviceName' => $deviceName,
                'business' => $business,
            ];
            \app\common\QwRobot::pushMsgFormat($msg, $business_config['name'].'铁人设备结果数据');
        } catch (Exception $ex) {
            $result = ['success' => false, 'errCode' => 1, 'errMessage' => $ex->getMessage()];
            return json($result);
        }
        $result = ['success' => true, 'errCode' => null, 'errMessage' => null];
        return json($result);
    }

    /**
     * 体测数据接口
     */
    public function deviceTest() {
        try {
            $param = $this->request->param();
            $business = !empty($param['business']) ? $param['business'] : '';
            $deviceKey = !empty($param['deviceKey']) ? $param['deviceKey'] : '';
            $deviceName = !empty($param['deviceName']) ? $param['deviceName'] : '';
            $userCode = !empty($param['userCode']) ? $param['userCode'] : '';
            $data = !empty($param['data']) ? $param['data'] : '';
            $model = !empty($param['model']) ? $param['model'] : '';
            if (empty($business) || empty($deviceKey) || empty($deviceName) || empty($userCode)) {
                throw new Exception('缺少参数');
            }
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception('用户不存在');
            }
            //todo 体测数据入库
        } catch (Exception $ex) {
            $result = ['success' => false, 'errCode' => 1, 'errMessage' => $ex->getMessage()];
            return json($result);
        }
        $result = ['success' => true, 'errCode' => null, 'errMessage' => null];
        return json($result);
    }
}
