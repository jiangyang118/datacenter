<?php


namespace app\api\controller;

use app\base\ApiController;
use think\Exception;
use think\Config;

use app\api\logic\Polar as PolarLogic;
class Polar extends ApiController {
    public $logic;
    public $business;
    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->business = $this->request->param('business', '');
        $this->logic = new PolarLogic();
        
    }
    
    /**
     * 授权回调
     * @return type
     */
    public function code() {
        $params = $this->request->param();
        $res = $this->logic->code($params);
        return json($res);
    }
    
    /**
     * 获取token
     * @return type
     */
    public function getToken() {
        $res = $this->logic->getToken($this->business);
        return json($res);
    }
    
    /**
     * 获取运动队
     * @return type
     */
    public function getTeams() {
        $page = $this->request->param('page', 0);
        $per_page = $this->request->param('per_page', 100);
        $res = $this->logic->getTeams($this->business, ['page'=>$page, 'per_page'=>$per_page]);
        return json($res);
    }
    
    /**
     * 获取运动队详情
     * @return type
     */
    public function getTeamDetails() {
        $res = $this->logic->getTeamDetails($this->business, $this->request->param('team_id'));
        return json($res);
    }
    
    /**
     * 获取运动队训练sessions
     * @return type
     */
    public function getTeamTrainingSessions() {
        $team_id = $this->request->param('team_id');
        $since = $this->request->param('since');
        $until = $this->request->param('until');
        $page = $this->request->param('page', 0);
        $per_page = $this->request->param('per_page', 100);
        $res = $this->logic->getTeamTrainingSessions($this->business, $team_id, $since, $until, ['page'=>$page, 'per_page'=>$per_page]);
        return json($res);
    }
    
    /**
     * 获取运动队训练session明细
     * @return type
     */
    public function getTeamTrainingSessionDetails() {
        $training_session_id = $this->request->param('training_session_id');
        $res = $this->logic->getTeamTrainingSessionDetails($this->business, $training_session_id);
        return json($res);
    }
    
    /**
     * 获取运动员训练sessions
     * @return type
     */
    public function getPlayerTrainingSessions() {
        $player_id = $this->request->param('player_id');
        $since = $this->request->param('since');
        $until = $this->request->param('until');
        $type = $this->request->param('type');
        $res = $this->logic->getPlayerTrainingSessions($this->business, $player_id, $since, $until, $type);
        return json($res);
    }
    
    /**
     * 获取运动员session明细
     * @return type
     */
    public function getPlayerTrainingSessionDetails() {
        $player_session_id = $this->request->param('player_session_id');
        $samples = $this->request->param('samples');
        $res = $this->logic->getPlayerTrainingSessionDetails($this->business, $player_session_id, $samples);
        return json($res);
    }
    
    /**
     * 获取运动员session总结
     * @return type
     */
    public function getPlayerTrainingSessionSummary() {
        $player_session_id = $this->request->param('player_session_id');
        $res = $this->logic->getPlayerTrainingSessionSummary($this->business, $player_session_id);
        return json($res);
    }
}
