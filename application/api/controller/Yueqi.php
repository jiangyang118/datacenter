<?php


namespace app\api\controller;

use app\base\ApiController;
use think\Exception;

use app\api\logic\Yueqi as Logic;
/**
 * 悦琦血压计
 */
class Yueqi extends ApiController {
    public $logic;
    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->logic = new Logic();
    }
    
    
    public function postDataSTM32() {
        $params = $this->request->param();
        $this->logic->writeLog($params, "yueqi");
        $data = $this->logic->postDataSTM32($params);
        $this->logic->writeLog($data, "yueqi");
        return json($data);
    }
}
