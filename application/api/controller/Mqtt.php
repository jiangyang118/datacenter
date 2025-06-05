<?php
/**
 * mqtt
 */
namespace app\api\controller;

use app\base\ApiController;
use think\Exception;

class Mqtt extends ApiController {
    private $logic;

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);

    }

    public function cs() {
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

}
