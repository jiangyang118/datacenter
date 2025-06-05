<?php

namespace app\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;

use app\api\logic\Polar as PolarLogic;

use app\model\EquipmentRelation as EquipmentRelationModel;
use app\model\EquipmentResult as EquipmentResultModel;
use app\model\EquipmentResultExtend as EquipmentResultExtendModel;
use app\model\EquipmentProcess as EquipmentProcessModel;
use app\model\EquipmentProcessExtend as EquipmentProcessExtendModel;

class SyncPolarData extends Command {
    public $polarLogic;
    public $equipmentRelationModel;
    public $equipmentResultModel;
    public $equipmentResultExtendModel;
    public $equipmentProcessModel;
    public $equipmentProcessExtendModel;
    public $resultRelationType = 57;
    public $processRelationType = 58;
    public $equipmentId = 30;
    public $teamTrainsMethod = 1;//0:全量，1：增量
    public function __construct($name = null) {
        parent::__construct($name);
        $this->polarLogic = new PolarLogic();
        $this->equipmentRelationModel = new EquipmentRelationModel();
        $this->equipmentResultModel = new EquipmentResultModel();
        $this->equipmentResultExtendModel = new EquipmentResultExtendModel();
        $this->equipmentProcessModel = new EquipmentProcessModel();
        $this->equipmentProcessExtendModel = new EquipmentProcessExtendModel();
    }
    
    protected function configure() {
        $this->setName('SyncPolarData')->setDescription('this api is for sync polar data')
            ->addArgument('business');//商户唯一标识;
    }
    
    public function getTopDepartmentUUId($business) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if(empty($business_config['createStaff']['polar_top_level_department_uuid'])) {
            throw new Exception("Polar默认顶级部门配置错误");
        }
        return $business_config['createStaff']['polar_top_level_department_uuid'];
    }
    
    /**
     * 生成部门code
     * @param type $department_uuid
     * @return type
     */
    public function getDepartmentCode($business_db, $department_uuid) {
        $project = $business_db->name("department")->where(['uuid'=>$department_uuid])->find();
        $code = "";
        //判断当前部门是否一级
        if (empty($department_uuid)) {
            $max = $business_db->name("department")->where('puuid',0)->field("max(code) code")->find();
            if(empty($max['code'])) {
                $code = "01";
            } else {
                $code = str_pad(intval($max['code']) + 1, 2, 0, STR_PAD_LEFT);
            }
        } else {
            $max = $business_db->name("department")->where('puuid', $department_uuid)->field("max(code) code")->find();
            if(empty($max['code'])) {
                $code = $project['code'] . "01";
            } else {
                $len = strlen($max['code']);
                $code = str_pad(intval($max['code']) + 1, $len, 0, STR_PAD_LEFT);
            }
        }
        
        return $code;
    }
    
    /**
     * 获取同步过的运动队
     * @param type $business_db
     * @return type
     */
    public function getSynchronizedDepartment($business_db) {
        return $business_db->name("department")->where(['polar_team_id'=>['<>', ''], 'is_show'=>1])->column("uuid,name,code,polar_team_id", "polar_team_id");
    }
    
    /**
     * 同步运动队
     * @param type $business_db
     * @param type $business
     * @param type $teams
     * @return boolean
     * @throws Exception
     */
    public function dealTeams($business_db, $business, $teams) {
        $syncTeamNum = 0;
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        $polar_department_uuid = empty($business_config['createStaff']['polar_top_level_department_uuid']) ? '' : $business_config['createStaff']['polar_top_level_department_uuid'];
        
        $existTeams = $this->getSynchronizedDepartment($business_db);
        
        $department_path = '';
        if(!empty($polar_department_uuid)) {
            $department_path = $polar_department_uuid;
        }
        foreach($teams as $k=>$team) {
            if(array_key_exists($team['id'], $existTeams)) {
                unset($teams[$k]);
                continue;
            }
            $teamsArr = [
                'uuid' => guid(),
                'puuid' => $polar_department_uuid,
                'code' => $this->getDepartmentCode($business_db, $polar_department_uuid),
                'name' => $team['name'],
                'department_path' => $department_path,
                'polar_team_id' => $team['id'],
                'create_time' => date("Y-m-d H:i:s"),
                'create_by' => "API"
            ];
            $business_db->name("department")->insertGetId($teamsArr);
            $syncTeamNum++;
            unset($teams[$k]);
        }
        
        return $syncTeamNum;
    }
    
    /**
     * 获取同步过的运动员
     * @param type $business_db
     * @return type
     */
    public function getSynchronizedAthletes($business_db) {
        return $business_db->name("staff")->where(['is_show'=>1, 'polar_player_id'=>['<>', '']])->column("uuid,name,polar_player_id", "polar_player_id");
    }
    
    /**
     * 获取未同步的运动员训练session
     * @param type $business_db
     * @return type
     */
    public function getNotSyncPlayerSession($business_db) {
        return $business_db->name("polar_team_train_participants")->where(['step'=>0])->field("player_id,player_session_id")->distinct(true)->select();
    }
    
    public function getPlayerCode($business_db) {
        $max = $business_db->name('staff')->field("max(personnel_umber) personnel_umber")->find();
        if(empty($max['personnel_umber'])) {
            $personnel_umber = "0001";
        } else {
            $personnel_umber = str_pad(intval($max['personnel_umber']) + 1, 4, 0, STR_PAD_LEFT);
        }
        return $personnel_umber;
    }
    
    /**
     * 同步运动员
     * @param type $business_db
     * @param type $business
     * @param type $department
     * @param type $athletes
     * @return boolean
     * @throws Exception
     */
    public function dealPlayers($business_db, $business, $department, $athletes) {
        $syncPlayerNum = 0;
        $create_by = "API";
        $create_time = date("Y-m-d H:i:s");
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        
        if(empty($business_config['createStaff']['athlete_station_uuid'])) {
            throw new Exception("人员默认岗位未配置");
        }
        $station_uuid = $business_config['createStaff']['athlete_station_uuid'];
        $department_uuid = $department['uuid'];
        
        $staffs = $this->getSynchronizedAthletes($business_db);
        foreach($athletes as $k=>$athlete) {
            if(array_key_exists($athlete['player_id'], $staffs)) {
                unset($athletes[$k]);
                continue;
            }
            
            $staff_uuid = guid();
            $staffArr = [
                'uuid'=>$staff_uuid, 
                'personnel_umber' => $this->getPlayerCode($business_db),
//                'name'=>$athlete['last_name'],
                'name'=>$athlete['last_name'].$athlete['first_name'],
                'polar_player_id' => $athlete['player_id'],
                'is_athlete' => 1,
                'station_uuid'=>$station_uuid,
                'create_time'=>$create_time,
                'create_by'=>$create_by
            ];
            $staffDepartments = [
                'staff_uuid'=>$staff_uuid,
                'department_uuid'=>$department_uuid,
                'create_time'=>$create_time,
                'create_by'=>$create_by
            ];
            
            try {
                $business_db->startTrans();
                $addStaffs = $business_db->name("staff")->insertGetId($staffArr);
                if(!$addStaffs) {
                    throw new Exception("添加人员失败");
                }
                $addStaffDepartments = $business_db->name("staff_department")->insertGetId($staffDepartments);
                if(!$addStaffDepartments) {
                    throw new Exception("人员与部门关联失败");
                }
                $stationUpdate = $business_db->name("station")->where(['uuid'=>$station_uuid])->setInc("quantity", 1);
                if($stationUpdate === false) {
                    throw new Exception("修改岗位人数失败");
                }
                $departmentUpdate = $business_db->name("department")->where(['uuid'=>$department_uuid])->update(['leaf_flag'=>1]);
                if($departmentUpdate === false) {
                    throw new Exception("修改部门叶子结点失败");
                }
                $business_db->commit();
            } catch(Exception $e) {
                $business_db->rollback();
                throw new Exception($e->getMessage());
            }
            $syncPlayerNum++;
            unset($athletes[$k]);
        }
        return $syncPlayerNum;
    }
    
    
    public function dealTeamTrainDetail($business_db, $business, $teamTrainDetail) {
        $existPolarTeamTrains = $business_db->name("polar_team_train")->column("name", "polar_plat_id");
        if(array_key_exists($teamTrainDetail['id'], $existPolarTeamTrains)) {
            return 0;
        }
        $teamTrainDetailArr = [
            'polar_plat_id' => $teamTrainDetail['id'],
            'team_id' => $teamTrainDetail['team_id'],
            'name' => !empty($teamTrainDetail['name']) ? $teamTrainDetail['name'] : '',
            'type' => !empty($teamTrainDetail['type']) ? $teamTrainDetail['type'] : '',
            'note' => !empty($teamTrainDetail['note']) ? $teamTrainDetail['note'] : '',
            'created' => !empty($teamTrainDetail['created']) ? $teamTrainDetail['created'] : '',
            'modified' => !empty($teamTrainDetail['modified']) ? $teamTrainDetail['modified'] : '',
            'record_start_time' => !empty($teamTrainDetail['record_start_time']) ? $teamTrainDetail['record_start_time'] : '',
            'record_end_time' => !empty($teamTrainDetail['record_end_time']) ? $teamTrainDetail['record_end_time'] : '',
            'start_time' => !empty($teamTrainDetail['start_time']) ? $teamTrainDetail['start_time'] : '',
            'end_time' => !empty($teamTrainDetail['end_time']) ? $teamTrainDetail['end_time'] : '',
            'latitude' => !empty($teamTrainDetail['latitude']) ? $teamTrainDetail['latitude'] : '0',
            'longitude' => !empty($teamTrainDetail['longitude']) ? $teamTrainDetail['longitude'] : '0',
            'sport' => !empty($teamTrainDetail['sport']) ? $teamTrainDetail['sport'] : '',
            'arena' => !empty($teamTrainDetail['arena']) ? $teamTrainDetail['arena'] : '',
        ];
        $teamTrainParticipants = [];
        if(!empty($teamTrainDetail['participants'])) {
            $teamTrainDetailArr['participants'] = json_encode($teamTrainDetail['participants']);
            foreach($teamTrainDetail['participants'] as $player) {
                $teamTrainParticipants[] = ['polar_plat_id'=>$teamTrainDetail['id'],'player_id'=>$player['player_id'],'player_session_id'=>$player['player_session_id']];
            }
        }
        if(!empty($teamTrainDetail['markers'])) {
            $teamTrainDetailArr['markers'] = json_encode($teamTrainDetail['markers']);
        }
        try {
            $business_db->startTrans();

            $addTeamTrain = $business_db->name("polar_team_train")->insertGetId($teamTrainDetailArr);
            if(!$addTeamTrain) {
                throw new Exception("添加运动队训练详情失败");
            }
            if($teamTrainParticipants) {
                $addTeamTrainParticipants = $business_db->name("polar_team_train_participants")->insertAll($teamTrainParticipants);
                if(!$addTeamTrainParticipants) {
                    throw new Exception("添加运动队训练运动员失败");
                }
            }

            $business_db->commit();
        } catch(Exception $e) {
            $business_db->rollback();
            throw new Exception($e->getMessage());
        }
        
        return 1;
    }
    
    /**
     * 处理运动员session
     * @param type $business_db
     * @param type $business
     * @param type $resultRelations
     * @param type $playerSessionSummary
     * @param type $processRelations
     * @param type $playerSessionDetail
     * @return boolean
     */
    public function dealPlayerTrain($business_db, $business, $player_staff, $resultRelations, $playerSessionSummary, $processRelations, $playerSessionDetail) {
        $player_staff_uuid = $player_staff['uuid'];
        $player_session_id = !empty($playerSessionSummary['player_session_id']) ? $playerSessionSummary['player_session_id'] : '';
        $existPlayerSessionIds = $this->equipmentResultModel->inquiryColumn(['business'=>$business,'equipment_id'=>$this->equipmentId,'relation_type'=>$this->resultRelationType,'staff_uuid'=>$player_staff_uuid], "equipment_id", "reference");
        if(array_key_exists($player_session_id, $existPlayerSessionIds)) {
            $business_db->name("polar_team_train_participants")->where(['player_session_id'=>$player_session_id])->update(['step'=>1]);
            return 0;
        }
        $department = $business_db->name("department")
                ->alias("a")
                ->join("staff_department b", "a.uuid = b.department_uuid", "left")
                ->where(["b.staff_uuid"=>$player_staff_uuid, "a.is_show"=>1])
                ->field("a.uuid,a.name")
                ->find();
        
        $datetime = date("Y-m-d H:i:s");
        $create_by = "API";
        $resultArr = [
            'business' => $business,
            'equipment_id' => $this->equipmentId,
            'relation_type' => $this->resultRelationType,
            'department_uuid' => !empty($department['uuid']) ? $department['uuid'] : '',
            'staff_uuid' => $player_staff_uuid,
            'date' => '',
            'record_time' => '',
            'reference' => '',
            'create_time' => $datetime,
            'update_time' => $datetime,
            'create_by' => $create_by
        ];
        $resultExtendArr = [];
        $resultOtherInfo = [];
        foreach($playerSessionSummary as $k=>$v) {
            switch ($k) {
                case 'player_session_id':
                    $resultArr['reference'] = $v;
                    break;
                case 'trimmed_start_time':
                    $resultArr['record_time'] = date("Y-m-d H:i:s", strtotime($v));
                    $resultArr['date'] = date("Y-m-d", strtotime($resultArr['record_time']));
                    break;
                case 'heart_rate_zones':
                case 'speed_zones_kmh':
                case 'acceleration_zones_ms2':
                case 'power_zones':
                    $resultOtherInfo[$k] = $v;
                    break;
                default :
                    break;
            }
            if(array_key_exists($k, $resultRelations)) {
                $resultIndex = trim($resultRelations[$k], "k");
                if($resultIndex <= 20) {
                    $resultArr[$resultRelations[$k]] = $v;
                } else {
                    $resultExtendArr[$resultRelations[$k]] = $v;
                }
            }
            unset($playerSessionSummary[$k]);
        }
        if($resultOtherInfo) {
            $resultExtendArr['other_info'] = json_encode($resultOtherInfo);
        }
        
        $playerTrainColumns = $business_db->name("polar_player_train")->getTableFields();
        $playerTrainArr = ['player_id'=>$player_staff['polar_player_id']];
        foreach($playerSessionDetail as $k1=>$v1) {
            if("id" == $k1) {
                $playerTrainArr['polar_plat_id'] = $v1;
            } elseif("samples" == $k1) {
                if(!empty($v1['fields'])) {
                    $playerTrainArr['sample_fields'] = json_encode($v1['fields']);
                }
                if(!empty($v1['values'])) {
                    $playerTrainArr['sample_values'] = $this->polarLogic->compressNested($v1['values']);
                }
            } elseif("rr_intervals" == $k1) {
                if(!empty($v1)) {
                    $playerTrainArr['rr_intervals'] = json_encode($this->polarLogic->compress($v1, "Unsigned", 16));
                }
            } elseif(in_array($k1, $playerTrainColumns)) {
                $playerTrainArr[$k1] = !empty($v1) ? $v1 : '';
            }
            unset($playerSessionDetail[$k1]);
        }
        $polar_team_train_participants = $business_db->name("polar_team_train_participants")->where(['player_session_id'=>$player_session_id])->field("id")->count();
        try {
            $this->equipmentResultModel->startTrans();
            
            $dcResultId = $this->equipmentResultModel->add($resultArr);
            if(!$dcResultId) {
                throw new Exception("新增数据中心结果表失败");
            }
            if($resultExtendArr) {
                $resultExtendArr['equipment_result_id'] = $dcResultId;
                $dcResultExtendId = $this->equipmentResultExtendModel->add($resultExtendArr);
                if(!$dcResultExtendId) {
                    throw new Exception("新增数据中心结果扩展表失败");
                }
            }
            $business_db->startTrans();
            
            $businessResultId = $business_db->name("equipment_result")->insertGetId($resultArr);
            if(!$businessResultId) {
                throw new Exception("新增业务系统结果表失败");
            }
            if($resultExtendArr) {
                $resultExtendArr['equipment_result_id'] = $businessResultId;
                $businessResultExtendId = $business_db->name("equipment_result_extend")->insertGetId($resultExtendArr);
                if(!$businessResultExtendId) {
                    throw new Exception("新增业务系统结果扩展表失败");
                }
            }
            
            $res = $business_db->name("polar_player_train")->insertGetId($playerTrainArr);
            if(!$res) {
                throw new Exception("保存训练明细失败");
            }
            
            if($polar_team_train_participants) {
                $team_train_participants_res = $business_db->name("polar_team_train_participants")->where(['player_session_id'=>$player_session_id])->update(['step'=>1]);
                if($team_train_participants_res === false) {
                    throw new Exception("修改运动员训练同步状态失败");
                }
            }
            
            $this->equipmentResultModel->commit();
            $business_db->commit();
        } catch (Exception $e) {
            $this->equipmentResultModel->rollback();
            $business_db->rollback();
            throw new Exception($e->getMessage());
        }
        
        return 1;
    }
    
    protected function execute(Input $input, Output $output) {
        set_time_limit(0);
        ini_set('memory_limit', '3072M');
        $business = '';
        try {
            $business = $input->getArgument('business');
            //业务系统数据连接
            $business_db = $this->getBusinessDb($business);
            //结果数据映射
            $resultRelations = $this->equipmentRelationModel->inquiryColumn(['type'=> $this->resultRelationType, 'is_del'=>0], "v", 'k');
            //过程数据映射
            $processRelations = $this->equipmentRelationModel->inquiryColumn(['type'=> $this->processRelationType, 'is_del'=>0], "v", 'k');
            
            $paginationQuery = ['page'=>0, 'per_page'=>100];
            //处理运动队
            $teamMore = false;
            $syncTeamTotal = $syncPlayerTotal = $syncTeamTrainTotal = $synPlayerTrainTotal = 0;
            do {
                $teamRes = $this->polarLogic->getTeams($business, $paginationQuery);
                if($teamRes['code']) {
                    throw new Exception($teamRes['message']);
                }
                $teams = !empty($teamRes['data']['data']) ? $teamRes['data']['data'] : [];
//                处理运动队
                $syncTeamNum = $this->dealTeams($business_db, $business, $teams);
                $syncTeamTotal += $syncTeamNum;
                $page = !empty($teamRes['data']['page']) ? $teamRes['data']['page'] : ['page_number'=>10000,'total_pages'=>0];
                if($page['page_number'] < $page['total_pages'] - 1) {
                    $paginationQuery['page']++;
                    $teamMore = true;
                } else {
                    $teamMore = false;
                }
                
            } while($teamMore);
            
            //释放内容
            if(!empty($teamRes)) {
                unset($teamRes);
            }
            if(!empty($teams)) {
                unset($teams);
            }
            
            //同步运动员
            $syncedDepartments = $this->getSynchronizedDepartment($business_db);
            foreach($syncedDepartments as $syncedDepartment) {
                $teamDetails = $this->polarLogic->getTeamDetails($business, $syncedDepartment['polar_team_id']);
                if($teamDetails['code']) {
                    throw new Exception($teamDetails['message']);
                }
                $players = !empty($teamDetails['data']['data']['players']) ? $teamDetails['data']['data']['players'] : [];
                if(empty($players)) {
                    continue;
                }
                $syncPlayerNum = $this->dealPlayers($business_db, $business, $syncedDepartment, $players);
                $syncPlayerTotal += $syncPlayerNum;
            }
            //释放内存
            if(!empty($teamDetails)) {
                unset($teamDetails);
            }
            if(!empty($players)) {
                unset($players);
            }
            
            //同步运动队训练sessions
            //获取最新一条
            foreach($syncedDepartments as $outer_k=>$syncedDepartment) {
                $paginationQuery = ['page'=>0, 'per_page'=>100];
                $since = null;
                //这段代码在初始化全量获取数据之后打开，每次只获取未同步的部分（因为返回数据是倒序排的）
                if($this->teamTrainsMethod) {
                    $lastestTeamTrain = $business_db->name("polar_team_train")->where(['team_id'=>$syncedDepartment['polar_team_id']])->field("team_id,record_start_time")->order("record_start_time desc")->find();
                    //获取所有历史数据后，放开此注释
                    if(!empty($lastestTeamTrain['record_start_time'])) {
                        $since = $lastestTeamTrain['record_start_time'];
                    }
                }
                
                $teamTrainMore = false;
                do {
                    $polarTeamTrainsRes = $this->polarLogic->getTeamTrainingSessions($business, $syncedDepartment['polar_team_id'], $since, null, $paginationQuery);
                    if($polarTeamTrainsRes['code']) {
                        throw new Exception($polarTeamTrainsRes['message']);
                    }
                    $polarTeamTrains = !empty($polarTeamTrainsRes['data']['data']) ? $polarTeamTrainsRes['data']['data'] : [];
                    //获取运动队训练session_details
                    foreach($polarTeamTrains as $inner_k=>$polarTeamTrain) {
                        $polarTeamTrainDetailRes = $this->polarLogic->getTeamTrainingSessionDetails($business, $polarTeamTrain['id']);
                        if($polarTeamTrainDetailRes['code']) {
                            throw new Exception($polarTeamTrainDetailRes['message']);
                        }
                        if(empty($polarTeamTrainDetailRes['data']['data'])) {
                            continue;
                        }
                        $syncTeamTrainNum = $this->dealTeamTrainDetail($business_db, $business, $polarTeamTrainDetailRes['data']['data']);
                        $syncTeamTrainTotal += $syncTeamTrainNum;
                        unset($polarTeamTrains[$inner_k]);
                    }
                    $page = !empty($polarTeamTrainsRes['data']['page']) ? $polarTeamTrainsRes['data']['page'] : ['page_number'=>10000,'total_pages'=>0];
                    if($page['page_number'] < $page['total_pages'] - 1) {
                        $paginationQuery['page']++;
                        $teamMore = true;
                    } else {
                        $teamMore = false;
                    }
                } while($teamTrainMore);
                //释放内存
                unset($syncedDepartments[$outer_k]);
                if(!empty($polarTeamTrainsRes)) {
                    unset($polarTeamTrainsRes);
                }
                if(!empty($polarTeamTrainDetailRes)) {
                    unset($polarTeamTrainDetailRes);
                }
            }
            
            //同步运动员session
            $syncedPlayers = $this->getSynchronizedAthletes($business_db);
            /**foreach($syncedPlayers as $outer_kk=>$syncedPlayer) {
                $paginationQuery = ['page'=>0, 'per_page'=>100];
                $lastPlayerTrain = $business_db->name("polar_player_train")->where(['player_id'=>$syncedPlayer['polar_player_id']])->field("player_id,start_time")->order("start_time desc")->find();
                $since = null;
                //获取所有历史数据后，放开此注释
//                if(!empty($lastPlayerTrain['start_time'])) {
//                    $since = $lastPlayerTrain['start_time'];
//                }
                $PlayerTrainMore = false;
                do {
                    //运动员训练session
                    $playerTrainsRes = $this->polarLogic->getPlayerTrainingSessions($business, $syncedPlayer['polar_player_id'], $since, null, 'all', $paginationQuery);
                    if($playerTrainsRes['code']) {
                        throw new Exception($playerTrainsRes['message']);
                    }
                    $playerTrains = !empty($playerTrainsRes['data']['data']) ? $playerTrainsRes['data']['data'] : [];
                    foreach($playerTrains as $inner_kk=>$playerTrain) {
                        //运动员session_summary
                        $playerSessionSummaryRes = $this->polarLogic->getPlayerTrainingSessionSummary($business, $playerTrain['id']);
                        if($playerSessionSummaryRes['code']) {
                            throw new Exception($playerSessionSummaryRes['message']);
                        }
                        $playerSessionSummary = !empty($playerSessionSummaryRes['data']['data']) ? $playerSessionSummaryRes['data']['data'] : [];
                        //运动员session_detail
                        $playerSessionDetailRes = $this->polarLogic->getPlayerTrainingSessionDetails($business, $playerTrain['id'], "all");
                        if($playerSessionDetailRes['code']) {
                            throw new Exception($playerSessionDetailRes['message']);
                        }
                        $playerSessionDetail = !empty($playerSessionDetailRes['data']['data']) ? $playerSessionDetailRes['data']['data'] : [];
                        $synPlayerTrainSum = $this->dealPlayerTrain($business_db, $business, $syncedPlayer, $resultRelations, $playerSessionSummary, $processRelations, $playerSessionDetail);
                        $synPlayerTrainTotal += $synPlayerTrainSum;
                        unset($playerTrains[$inner_kk]);
                    }
                    $page = !empty($playerTrainsRes['data']['page']) ? $playerTrainsRes['data']['page'] : ['page_number'=>10000,'total_pages'=>0];
                    if($page['page_number'] < $page['total_pages'] - 1) {
                        $paginationQuery['page']++;
                        $PlayerTrainMore = true;
                    } else {
                        $PlayerTrainMore = false;
                    }
                } while ($PlayerTrainMore);
                //释放内存
                unset($syncedPlayers[$outer_kk]);
                if(!empty($playerTrainsRes)) {
                    unset($playerTrainsRes);
                }
                if(!empty($playerSessionSummaryRes)) {
                    unset($playerSessionSummaryRes);
                }
                if(!empty($playerSessionSummary)) {
                    unset($playerSessionSummary);
                }
                if(!empty($playerSessionDetailRes)) {
                    unset($playerSessionDetailRes);
                }
                if(!empty($playerSessionDetail)) {
                    unset($playerSessionDetail);
                }
            }**/
            
            //同步运动员团体训练的session
            $notSyncPlayerSessions = $this->getNotSyncPlayerSession($business_db);
            foreach($notSyncPlayerSessions as $inner_kk=>$notSyncPlayerSession) {
                if(empty($syncedPlayers[$notSyncPlayerSession['player_id']])) {
                    continue;
                }
                //运动员session_summary
                $playerSessionSummaryRes = $this->polarLogic->getPlayerTrainingSessionSummary($business, $notSyncPlayerSession['player_session_id']);
                if($playerSessionSummaryRes['code']) {
                    throw new Exception($playerSessionSummaryRes['message']);
                }
                $playerSessionSummary = !empty($playerSessionSummaryRes['data']['data']) ? $playerSessionSummaryRes['data']['data'] : [];
                //运动员session_detail
                $playerSessionDetailRes = $this->polarLogic->getPlayerTrainingSessionDetails($business, $notSyncPlayerSession['player_session_id'], "all");
                if($playerSessionDetailRes['code']) {
                    throw new Exception($playerSessionDetailRes['message']);
                }
                $playerSessionDetail = !empty($playerSessionDetailRes['data']['data']) ? $playerSessionDetailRes['data']['data'] : [];
                //不明情况下会出现
                if(empty($playerSessionDetail['id'])) {
                    continue;
                }
                $synPlayerTrainSum = $this->dealPlayerTrain($business_db, $business, $syncedPlayers[$notSyncPlayerSession['player_id']], $resultRelations, $playerSessionSummary, $processRelations, $playerSessionDetail);
                $synPlayerTrainTotal += $synPlayerTrainSum;
                unset($notSyncPlayerSessions[$inner_kk]);
            }
            //释放内存
            if(!empty($playerSessionSummaryRes)) {
                unset($playerSessionSummaryRes);
            }
            if(!empty($playerSessionSummary)) {
                unset($playerSessionSummary);
            }
            if(!empty($playerSessionDetailRes)) {
                unset($playerSessionDetailRes);
            }
            if(!empty($playerSessionDetail)) {
                unset($playerSessionDetail);
            }
            
            if($syncTeamTotal || $syncPlayerTotal || $syncTeamTrainTotal || $synPlayerTrainTotal) {
                $details = "同步运动队数量：{$syncTeamTotal},同步运动员数量：{$syncPlayerTotal},同步运动队训练数量：{$syncTeamTrainTotal},同步运动员训练数量：{$synPlayerTrainTotal}";
                $msg = [
                    'device' => "Polar",
                    'business' => $business,
                    'details' => $details,
                    'message' => '同步成功'
                ];
                $this->sendMsg($business, $msg, "数据同步");
            }
            
        } catch (Exception $e) {
            $msg = [
                'device' => "Polar",
                'business' => $business,
                'message' => $e->getMessage()
            ];
            $this->sendMsg($business, $msg, "数据同步");
        }
    }
    
    /**
     * UTC时间转中国标准时间CST
     * @param type $datetime
     * @return type
     */
    public function utctocst($datetime) {
        // 创建一个DateTime对象，初始化为给定的UTC时间
        $utcTime = new \DateTime($datetime, new \DateTimeZone('UTC'));

        // 将时区设置为北京时间（CST，UTC+8）
        $utcTime->setTimezone(new \DateTimeZone('Asia/Shanghai'));
        return $utcTime->format('Y-m-d H:i:s');
    }
    
    /**
     * 业务系统数据
     * @param type $business
     * @return type
     * @throws Exception
     */
    public function getBusinessDb($business) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if($business == 'shjxkx') {
            if(empty($business_config['mysql_database']) || empty($business_config['mysql_hostname'])
                || empty($business_config['mysql_username']) || empty($business_config['mysql_password'])) {
                throw new Exception('商户配置错误');
            }
        } else {
            if(empty($business_config['mysql_database']) || empty($business_config['mysql_prefix']) || empty($business_config['mysql_hostname'])
                || empty($business_config['mysql_username']) || empty($business_config['mysql_password'])) {
                throw new Exception('商户配置错误');
            }
        }
        
        return get_db_mysql($business_config['mysql_database'], $business_config['mysql_prefix'], $business_config['mysql_hostname'], $business_config['mysql_username'], $business_config['mysql_password']);
    }
    
    /**
     * 发送机器人消息
     * @param type $business
     * @param type $msg
     * @param type $title
     * @throws Exception
     */
    public function sendMsg($business, $msg, $title) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            throw new Exception('商户配置错误');
        }
        \app\common\QwRobot::pushMsgFormat($msg, $business_config['name'].$title);
    }
}
