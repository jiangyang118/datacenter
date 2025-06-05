<?php

namespace app\api\controller;

use app\base\ApiController;
use think\Exception;
use app\api\logic\Inbody770 as Inbody770Logic;

class Inbody770 extends ApiController {
    public $logic;
    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->logic = new Inbody770Logic();
    }
    
    /**
     * 会员查询接口
     * @return type
     */
    public function getUserInfo() {
        $params = $this->request->param();
//        $this->logic->writeLog($params, "inbody770-getUserInfo");
        $data = $this->logic->getUserInfo($params);
//        $this->logic->writeLog($data, "inbody770-getUserInfo");
        return json($data);
    }
    
    /**
     * 设备数据上传接口
     * @return type
     */
    public function  setInbodyData() {
        $params = $this->request->param();
//        $this->logic->writeLog($params, "inbody770-setInbodyData");
        
        $data = $this->logic->setInbodyData($params);
        
//        $this->logic->writeLog($data, "inbody770-setInbodyData");
        return json($data);
    }
    
    /**
     * 设备报告纸上传接口
     * @return type
     */
    public function setInbodyImage() {
//        $params = file_get_contents("php://input");
        $params = $this->request->param();
//        $this->logic->writeLog($params, "inbody770-setInbodyImage");
        
        $data = $this->logic->setInbodyImage($params);
        
//        $this->logic->writeLog($data, "inbody770-setInbodyImage");
        return json($data);
    }
}
