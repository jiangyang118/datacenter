<?php

namespace app\api\controller;

use app\base\ApiController;
use think\Debug;
use think\Exception;
use app\api\logic\Keiser as KeiserLogic;
class Keiser extends ApiController{
    private $logic;

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->logic = new KeiserLogic();
    }

    /**
     * ocr同步数据
     */
    public function add() {
        try {
            $data = $this->request->post('data');
            $business = $this->request->post('business','sxkx_dev');
            $equipment_id = $this->request->post('equipment_id');
            if (empty($data)) {
                throw new Exception('缺少参数');
            }
            $data = json_decode($data, true);
            $res = $this->logic->add($data, $business, $equipment_id);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
        } catch (Exception $ex) {
            return json(['code' => 1,'message' => $ex->getMessage(), 'data'=>[]]);
        }
        return json(['code' => 0, 'message' => "同步成功", 'data' => []]);
    }
}
