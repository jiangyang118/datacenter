<?php

namespace app\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use app\base\FirstbeatBase as FirstbeatBaseApi;

use app\model\EquipmentRelation as EquipmentRelationModel;
use app\model\EquipmentResult as EquipmentResultModel;
use app\model\EquipmentResultExtend as EquipmentResultExtendModel;
use app\model\EquipmentProcess as EquipmentProcessModel;
use app\model\EquipmentProcessExtend as EquipmentProcessExtendModel;

class SyncFirstbeatData extends Command {
    public $firstbeatBaseApi;
    private $equipmentId = 169;
    private $equipmentMark = "firstbeat";
    private $thirdType = 1;
    private $resultRelationType = 51;
    private $processRelationType = 52;
    public $equipmentRelationModel;
    public $equipmentResultModel;
    public $equipmentResultExtendModel;
    public $equipmentProcessModel;
    public $equipmentProcessExtendModel;
    public function __construct($name = null) {
        parent::__construct($name);
        $this->firstbeatBaseApi = new FirstbeatBaseApi();
        $this->equipmentRelationModel = new EquipmentRelationModel();
        $this->equipmentResultModel = new EquipmentResultModel();
        $this->equipmentResultExtendModel = new EquipmentResultExtendModel();
        $this->equipmentProcessModel = new EquipmentProcessModel();
        $this->equipmentProcessExtendModel = new EquipmentProcessExtendModel();
        
    }
    protected function configure() {
        $this->setName('SyncFirstbeatData')->setDescription('this api is for sync firstbeat data')
            ->addArgument('business');//商户唯一标识;
    }
    
    /**
     * 一次性代码，存储firstbeat消费者账号
     */
    public function dealAccounts($business_db, $data) {
        $accounts = json_decode($data, 1);
        if(empty($accounts['accounts'])) {
            return true;
        }
        $oldAccounts = $business_db->name("third_account")->where(['type'=>1])->column("accountId");
        $addAccounts = [];
        foreach($accounts['accounts'] as $account) {
            $tmp = [
                'type' => 1, 
                'accountId' => $account['accountId'],
                'name' => $account['name'],
                'authorizedBy' => json_encode($account['authorizedBy'])
            ];
            if(in_array($account['accountId'], $oldAccounts)) {
                $business_db->name("third_account")->where(['accountId'=>$account['accountId'], 'type'=>1])->update($tmp);
            } else {
                $addAccounts[] = $tmp;
            }
        }
        if($addAccounts) {
            $business_db->name("third_account")->insertAll($addAccounts);
        }
        
        return true;
    }
    
    /**
     * 同步运动员
     * @param type $business_db
     * @param type $business
     * @param type $athletes
     * @param type $accountId
     * @return boolean
     * @throws Exception
     */
    public function dealAthletes($business_db, $business, $athletes, $accountId) {
        $create_by = "api";
        $create_time = date("Y-m-d H:i:s");
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        
        if(empty($business_config['createStaff'])) {
            throw new Exception("人员默认部门与默认岗位未配置");
        }
        $createStaff = $business_config['createStaff'];
        if(empty($createStaff['station_uuid'])) {
            throw new Exception("人员默认岗位未配置");
        }
        if(empty($createStaff['department_uuid'])) {
            throw new Exception("人员默认部门未配置");
        }
        $station_uuid = $createStaff['station_uuid'];
        $department_uuid = $createStaff['department_uuid'];
        
        $staffs = $business_db->name("staff")->where(['del_flag'=>0])->column("uuid,name,firstbeatId","name");
        $firstbeatIds = array_column($staffs, "firstbeatId", "firstbeatId");
        $staffArr = [];
        $staffDepartments = [];
        foreach($athletes as $athlete) {
            $staff_name = trim($athlete['firstName']).trim($athlete['lastName']);
            $firstbeatId = trim($athlete['athleteId']);
            $email = !empty($athlete['email']) ? trim($athlete['email']) : "";
            
            if(array_key_exists($firstbeatId, $firstbeatIds)) {
                continue;
            }
            if(array_key_exists($staff_name, $staffs)) {
                if(!empty($staffs[$staff_name]['firstbeatId'])) {//已关联firstbeatId
                    continue;
                }
                //存在人名，但未关联firstbeatId，做关联
                $res = $business_db->name("staff")->where(['uuid'=>$staffs[$staff_name]['uuid']])->update(['firstbeatId'=>$athlete['athleteId'], 'firstbeatAccountId'=>$accountId]);
                if($res === false) {
                    throw new Exception("人员关联firstbeatId失败【{$staff_name}】");
                }
                continue;
            }
            
            //不存在的人名
            $staff_uuid = guid();
            $staffArr[] = [
                'uuid'=>$staff_uuid, 
                'name'=>$staff_name, 
                'email'=>$email, 
                'station_uuid'=>$station_uuid,
                'firstbeatId'=>$firstbeatId, 
                'firstbeatAccountId'=>$accountId,
                'create_time'=>$create_time,
                'create_by'=>$create_by
            ];
            $staffDepartments[] = [
                'staff_uuid'=>$staff_uuid,
                'department_uuid'=>$department_uuid,
                'create_time'=>$create_time,
                'create_by'=>$create_by
            ];
        }
        
        if(empty($staffArr)) {
            return true;
        }
        
        try {
            $business_db->startTrans();
            $addStaffs = $business_db->name("staff")->insertAll($staffArr);
            if(!$addStaffs) {
                throw new Exception("添加人员失败");
            }
            $addStaffDepartments = $business_db->name("staff_department")->insertAll($staffDepartments);
            if(!$addStaffDepartments) {
                throw new Exception("人员与部门关联失败");
            }
            $stationUpdate = $business_db->name("station")->where(['uuid'=>$station_uuid])->setInc("quantity", count($addStaffs));
            if($stationUpdate === false) {
                throw new Exception("修改岗位人数失败");
            }
            $business_db->commit();
        } catch(Exception $e) {
            $business_db->rollback();
            throw new Exception($e->getMessage());
        }
        
        return true;
    }
    
    /**
     *测试代码，勿删
     * @var type 
     */
//    public $aaa = 0;
//    public function test() {
//        $this->aaa++;
//        var_dump($this->aaa);
//        if($this->aaa < 3) {
//            return '{"more":true,"athletes":[{"firstName":"胡","lastName":"凯","email":"506465987@qq.com","athleteId":461390},{"firstName":"郭","lastName":"连根","email":"506465987@qq.com","athleteId":461391},{"firstName":"姚","lastName":"千寻","email":"506465987@qq.com","athleteId":461393},{"firstName":"刘","lastName":"俊","athleteId":461395},{"firstName":"田","lastName":"翔宇","email":"506465987@qq.com","athleteId":461396}]}';
//        }elseif($this->aaa == 3) {
//            return '{"more":false,"athletes":[{"firstName":"胡","lastName":"凯","email":"506465987@qq.com","athleteId":461390},{"firstName":"郭","lastName":"连根","email":"506465987@qq.com","athleteId":461391},{"firstName":"姚","lastName":"千寻","email":"506465987@qq.com","athleteId":461393},{"firstName":"刘","lastName":"俊","athleteId":461395},{"firstName":"田","lastName":"翔宇","email":"506465987@qq.com","athleteId":461396}]}';
//        }
//    }
    
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
     * 处理测试记录
     * @param type $business_db
     * @param type $business
     * @param type $measurements
     * @throws Exception
     */
    public function dealMeasurements($business_db, $business, $measurements, $accountId) {
        //结果数据映射
        $resultRelations = $this->equipmentRelationModel->inquiryAll(['type'=> $this->resultRelationType, 'is_del'=>0], [], ['k','v','unit']);
        $resultKVS = [];
        foreach($resultRelations as $k=>$v) {
            $v['index'] = trim($v['v'], 'k');
            $resultKVS[$v['k']] = $v;
        }
        
        //运动员基本信息
        $athletes = $business_db->name("staff")
                ->alias("a")
                ->join("staff_department b", "a.uuid = b.staff_uuid", "left")
                ->join("department c", "b.department_uuid = c.uuid")
                ->where(['a.firstbeatId'=>['<>',''], 'c.status'=>1, 'c.del_flag'=>0])
                ->field("c.uuid department_uuid,a.uuid staff_uuid,a.firstbeatId athleteId,a.height,a.weight,a.birthday")
                ->select();
        if($athletes) {
            $athletes = array_column($athletes, null, "athleteId");
        }
        $exists_measurement_ids = $business_db->name("equipment_result")->where(['equipment_id'=>$this->equipmentId,'relation_type'=>$this->resultRelationType,'is_del'=>0])->column("k1");
        $equipment_results = [];
        foreach($measurements as $measurement) {
            //已同步过的
            if(in_array($measurement['measurementId'], $exists_measurement_ids)) {
                continue;
            }
            $tmp = [
                'business' => $business,
                'equipment_id' => $this->equipmentId,
                'equipment_mark' => $this->equipmentMark,
                'relation_type' => $this->resultRelationType,
                'department_uuid' => !empty($athletes[$measurement['athleteId']]['department_uuid']) ? $athletes[$measurement['athleteId']]['department_uuid'] : '',
                'staff_uuid' => !empty($athletes[$measurement['athleteId']]['staff_uuid']) ? $athletes[$measurement['athleteId']]['staff_uuid'] : '',
                'staff_height' => !empty($athletes[$measurement['athleteId']]['height']) ? $athletes[$measurement['athleteId']]['height'] : 0,
                'staff_weight' => !empty($athletes[$measurement['athleteId']]['weight']) ? $athletes[$measurement['athleteId']]['weight'] : 0,
                'staff_age' => !empty($athletes[$measurement['athleteId']]['birthday']) ? get_age($athletes[$measurement['athleteId']]['birthday']) : 0,
                'date' => date("Y-m-d"),
                'record_time' => date("Y-m-d H:i:s"),
                'step' => 0,
                'firstbeatAccountId' => $accountId
            ];
            foreach($measurement as $field=>$value) {
                if($field == 'startTime' || $field == 'endTime') {
                    $measurement[$field] = $value = $this->utctocst($value);
                }
                if(array_key_exists($field, $resultKVS)) {
                    $relation_key = $resultKVS[$field]['v'];
                    $tmp[$relation_key] = $value;
                }
            }
            if(!empty($measurement['laps'])) {
                $tmp['lap_ids'] = implode(",", array_column($measurement['laps'], "lapId"));
            } else {
                $tmp['lap_ids'] = '';
            }
            //测试日期转化
            if(!empty($measurement['startTime'])) {
                $tmp['date'] = date("Y-m-d", strtotime($measurement['startTime']));
                $tmp['record_time'] = $measurement['startTime'];
            }
            $equipment_results[] = $tmp;
        }
        
        try {
            $this->equipmentResultModel->startTrans();
            $business_db->startTrans();
            
            //数据中心
            $dcResult = $this->equipmentResultModel->insertAll($equipment_results);
            if(!$dcResult) {
                throw new Exception("数据中心同步测试记录失败");
            }
            //业务系统
            $businessResult = $business_db->name("equipment_result")->insertAll($equipment_results);
            if(!$businessResult) {
                throw new Exception("业务系统同步测试记录失败");
            }
            
            $this->equipmentResultModel->commit();
            $business_db->commit();
        } catch (Exception $e) {
            $this->equipmentResultModel->rollback();
            $business_db->rollback();
            throw new Exception($e->getMessage());
        }
        
        return true;
    }
    
    /**
     * 处理测试记录结果
     * @param type $business_db
     * @param type $business
     * @param type $measurementResult
     * @param type $measurement
     * @return boolean
     * @throws Exception
     */
    public function dealMeasurementResult($business_db, $business, $measurementResult, $measurement) {
        if(empty($measurementResult['variables'])) {
            return true;
        }
        //结果数据映射
        $resultRelations = $this->equipmentRelationModel->inquiryAll(['type'=> $this->resultRelationType, 'is_del'=>0], [], ['k','v','unit']);
        $resultKVS = [];
        foreach($resultRelations as $k=>$v) {
            $v['index'] = trim($v['v'], 'k');
            $resultKVS[$v['k']] = $v;
        }
        $result_extend = [];
        $not_exists = [];
        foreach($measurementResult['variables'] as $variable) {
            if(!empty($variable['name'])) {
                $real_k = $variable['name'];
                $real_v = $variable['value'];
                if(array_key_exists($real_k, $resultKVS)) {
                    $result_extend[$resultKVS[$real_k]['v']] = $real_v;
                } else {
                    $not_exists[] = $variable;
                }
            }
        }
        $result_extend['other_info'] = json_encode($not_exists);
        $dc_measurement = $this->equipmentResultModel->inquiryOne(['k1'=>$measurement['measurementId'],'business'=>$business,'equipment_id'=>$this->equipmentId,'relation_type'=>$this->resultRelationType,'is_del'=>0], [], "id as equipment_result_id,firstbeatAccountId,k2 as athleteId, k1 as measurementId");
        //数据中心
        try {
            if(empty($dc_measurement['equipment_result_id'])) {
                throw new Exception("数据中心数据错误【measurementId:{$measurement['measurementId']}】");
            }
            
            $this->equipmentResultExtendModel->startTrans();
            $business_db->startTrans();
            
            //数据中心
            $result_extend['equipment_result_id'] = $dc_measurement['equipment_result_id'];//数据中心的测试记录id
            $dcResultExtend = $this->equipmentResultExtendModel->add($result_extend);
            if(!$dcResultExtend) {
                throw new Exception("数据中心同步测试结果失败");
            }
            
            $dcResult = $this->equipmentResultModel->modifyById($dc_measurement['equipment_result_id'], ['step'=>1]);
            if($dcResult === false) {
                throw new Exception("数据中心同步测试结果失败.");
            }
            
            //业务系统
            $result_extend['equipment_result_id'] = $measurement['equipment_result_id'];//业务系统的测试记录id
            $businessResultExtend = $business_db->name("equipment_result_extend")->insert($result_extend);
            if(!$businessResultExtend) {
                throw new Exception("业务中心同步测试结果失败");
            }
            
            $businessResult = $business_db->name("equipment_result")->where(['id'=>$measurement['equipment_result_id']])->update(['step'=>1]);
            if($businessResult === false) {
                throw new Exception("业务中心同步测试结果失败.");
            }
            
            $this->equipmentResultExtendModel->commit();
            $business_db->commit();
        } catch (Exception $e) {
            $this->equipmentResultExtendModel->rollback();
            $business_db->rollback();
            throw new Exception($e->getMessage());
        }
        
        return true;
    }
    
    /**
     * 处理过程数据
     * @param type $business_db
     * @param type $business
     * @param type $measurementLapResult
     * @param type $measurement
     * @return boolean
     */
    public function dealMeasurementLapResult($business_db, $business, $measurementLapResult, $measurement) {
        $variables = [];
        if(!empty($measurementLapResult['variables'])) {
            $variables = $measurementLapResult['variables'];
            unset($measurementLapResult['variables']);
        }
        //结果数据映射
        $resultRelations = $this->equipmentRelationModel->inquiryAll(['type'=> $this->processRelationType, 'is_del'=>0], [], ['k','v','unit']);
        $resultKVS = [];
        foreach($resultRelations as $k=>$v) {
            $v['index'] = trim($v['v'], 'k');
            $resultKVS[$v['k']] = $v;
        }
        
        //过程表
        $process = [];
        foreach($measurementLapResult as $field=>$value) {
            if(array_key_exists($field, $resultKVS)) {
                $process[$resultKVS[$field]['v']] = $value;
            }
        }
        if(empty($process)) {
            return true;
        }
        
        
        //过程扩展表
        $process_extend = [];
        $not_exists = [];
        foreach($variables as $variable) {
            if(!empty($variable['name'])) {
                $real_k = $variable['name'];
                $real_v = $variable['value'];
                if(array_key_exists($real_k, $resultKVS)) {
                    $process_extend[$resultKVS[$real_k]['v']] = $real_v;
                } else {
                    $not_exists[] = $variable;
                }
            }
        }
        $process_extend['other_info'] = json_encode($not_exists);
        $dc_measurement = $this->equipmentResultModel->inquiryOne(['k1'=>$measurement['measurementId'],'business'=>$business,'equipment_id'=>$this->equipmentId,'relation_type'=>$this->resultRelationType,'is_del'=>0], [], "id as equipment_result_id,firstbeatAccountId,k2 as athleteId, k1 as measurementId");
        
        try {
            if(empty($dc_measurement['equipment_result_id'])) {
                throw new Exception("数据中心数据错误【measurementId:{$measurement['measurementId']}】");
            }
            
            $this->equipmentProcessModel->startTrans();
            $business_db->startTrans();
            
            //数据中心
            $process['equipment_result_id'] = $dc_measurement['equipment_result_id'];
            $dcProcessId = $this->equipmentProcessModel->add($process);
            if(!$dcProcessId) {
                throw new Exception("数据中心同步测试过程记录失败");
            }
            
            $process_extend['equipment_process_id'] = $dcProcessId;
            $dcProcessExtendId = $this->equipmentProcessExtendModel->add($process_extend);
            if(!$dcProcessExtendId) {
                throw new Exception("数据中心同步测试过程记录指标失败");
            }
            $dcResult = $this->equipmentResultModel->modifyById($dc_measurement['equipment_result_id'], ['step'=>2]);
            if($dcResult === false) {
                throw new Exception("数据中心同步测试过程失败.");
            }
            
            //业务系统
            $process['equipment_result_id'] = $measurement['equipment_result_id'];
            $businessProcessId = $business_db->name('equipment_process')->insertGetId($process);
            if(!$businessProcessId) {
                throw new Exception("业务系统同步测试过程记录失败");
            }
            
            $process_extend['equipment_process_id'] = $businessProcessId;
            $businessProcessExtendId = $business_db->name('equipment_process_extend')->insertGetId($process_extend);
            if(!$businessProcessExtendId) {
                throw new Exception("业务系统同步测试过程记录指标失败");
            }
            $businessResult = $business_db->name("equipment_result")->where(['id'=>$measurement['equipment_result_id']])->update(['step'=>2]);
            if($businessResult === false) {
                throw new Exception("业务系统同步测试过程失败.");
            }
            
            $this->equipmentProcessModel->commit();
            $business_db->commit();
        } catch (Exception $e) {
            $this->equipmentProcessModel->rollback();
            $business_db->rollback();
            throw new Exception($e->getMessage());
        }
        
        
        return true;
    }
    
    /**
     * 调通接口
     * @param Input $input
     * @param Output $output
     */
//    protected function execute(Input $input, Output $output) {
//        $business = $input->getArgument('business');
//        //获取账号
//        $accounts = $this->firstbeatBaseApi->getAccounts($business);
//        var_dump($accounts);die;
//        //获取运动员列表
//        $accountId = '3-3991';
//        $athletes = $this->firstbeatBaseApi->getAthletesByAccountId($business, $accountId, 0);
//        var_dump($athletes);die;
//        //获取运动员
//        $athleteId = '461396';
//        $athlete = $this->firstbeatBaseApi->getAthleteById($business, $accountId, $athleteId);
//        var_dump($athlete);die;
//        //获取测试记录
//        $measurements = $this->firstbeatBaseApi->getAthleteMeasurements($business, $accountId, $athleteId, "true", 0);
//        var_dump($measurements);die;
//        //Get athlete measurement results
//        $measurementId = "7898929";
//        $measurementResult = $this->firstbeatBaseApi->getAthleteMeasurementResults($business, $accountId, $athleteId, $measurementId, "list", "");
//        var_dump($measurementResult);die;
//
//        //Get athlete measurement lap results
//        $lapId = "925767";
//        $measurementLapResult = $this->firstbeatBaseApi->getAthleteMeasurementLapResults($business, $accountId, $athleteId, $measurementId, $lapId,  "list", "");
//        var_dump($measurementLapResult);die;
//
//        //Get coaches
//        $coaches = $this->firstbeatBaseApi->getCoachesByAccountId($business, $accountId, 0);
//        var_dump($coaches);die;
//        $coachId = "448138";
//        $coach = $this->firstbeatBaseApi->getCoachById($business, $accountId, $coachId);
//        var_dump($coach);die;
//
//        //Get teams
//        $teams = $this->firstbeatBaseApi->getTeamsByAccountId($business, $accountId, 0);
//        var_dump($teams);die;
//
//        //Get team
//        $teamId = "20843";
//        $team = $this->firstbeatBaseApi->getTeamById($business, $accountId, $teamId);
//        var_dump($team);die;
//
//        //Get team athletes
//        $athletesInTeam = $this->firstbeatBaseApi->getAthletesInteam($business, $accountId, $teamId, 0);
//        var_dump($athletesInTeam);die;
//
//        //Get team sessions
//        $teamSessions = $this->firstbeatBaseApi->getTeamSessions($business, $accountId, $teamId, 0, null, null, null, "true");
//        var_dump($teamSessions);die;
//
//        //Get session results
//        $sessionId = "810790";
//        $sessionResults = $this->firstbeatBaseApi->getSessionResults($business, $accountId, $teamId, $sessionId, "", "");
//        var_dump($sessionResults);
//
//        $lapId = "925767";
//        $sessionLapResults = $this->firstbeatBaseApi->getSessionLapResults($business, $accountId, $teamId, $sessionId, $lapId, "", "");
//        var_dump($sessionLapResults);die;
//    }

    /**
     * 处理业务
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output) {
        set_time_limit(0);
        $business = '';
        try {
            $business = $input->getArgument('business');
            //业务系统数据连接
            $business_db = $this->getBusinessDb($business);
            
            //同步账号。执行一次,获取授权账号
            $accountFlag = false;
            $thirdAccounts = $business_db->name("third_account")->where(['type'=>$this->thirdType])->select();
            if(empty($thirdAccounts)) {
                $accounts = $this->firstbeatBaseApi->getAccounts($business);
                $this->dealAccounts($business_db, $accounts);
                $accountFlag = true;
            }
            
            //同步运动员
            if($accountFlag) {
                $thirdAccounts = $business_db->name("third_account")->where(['type'=>$this->thirdType])->select();
            }
            foreach($thirdAccounts as $account) {
                $athleteMore = false;
                $athleteNum = $business_db->name("staff")->where(['firstbeatId'=>['<>', ''], 'firstbeatAccountId'=>$account['accountId']])->count();
                do {
                    $athletesJson = $this->firstbeatBaseApi->getAthletesByAccountId($business, $account['accountId'], $athleteNum);
//                    $athletesJson = $this->test();
                    $athletes = json_decode($athletesJson, 1);
                    if(!empty($athletes['more'])) {
                        $athleteMore = $athletes['more'];
                    } else {
                        $athleteMore = false;
                    }
                    if(!empty($athletes['athletes'])) {
                        $athleteNum += count($athletes['athletes']);
                        $this->dealAthletes($business_db, $business, $athletes['athletes'], $account['accountId']);
                    }
                    
                } while($athleteMore);
            }
            $recordCount = 0;
            //同步测试测试记录
            $accountAthletes = $business_db->name("staff")->where(['firstbeatId'=>['<>','']])->field(['firstbeatAccountId','firstbeatId'])->select();
            foreach($accountAthletes as $accountAthlete) {
                $measurementMore = false;
                //k2=>thleteId
                $measurementNum = $business_db->name("equipment_result")->where(['k2'=>$accountAthlete['firstbeatId']])->count();
                do {
                    //Get athlete measurements
                    $measurementsJson = $this->firstbeatBaseApi->getAthleteMeasurements($business, $accountAthlete['firstbeatAccountId'], $accountAthlete['firstbeatId'], 'true', $measurementNum);
                    $measurements = json_decode($measurementsJson, 1);
                    if(!empty($measurements['more'])) {
                        $measurementMore = $measurements['more'];
                    } else {
                        $measurementMore = false;
                    }
                    if(!empty($measurements['measurements'])) {
                        $measurementNum += count($measurements['measurements']);
                        $this->dealMeasurements($business_db, $business, $measurements['measurements'], $accountAthlete['firstbeatAccountId']);
                        $recordCount += count($measurements['measurements']);
                    }
                } while($measurementMore);
            }
            
            //同步测试记录结果数据
            $measurements_0 = $business_db->name("equipment_result")->where(['equipment_id'=>$this->equipmentId,'relation_type'=>$this->resultRelationType,'step'=>0,'is_del'=>0])->field("id as equipment_result_id,firstbeatAccountId,k2 as athleteId, k1 as measurementId")->select();
            foreach($measurements_0 as $measurement_0) {
                $measurementResultJson = $this->firstbeatBaseApi->getAthleteMeasurementResults($business, $measurement_0['firstbeatAccountId'], $measurement_0['athleteId'], $measurement_0['measurementId'], "list", "");
                $measurementResult = json_decode($measurementResultJson, 1);
                if(!empty($measurementResult)) {
                    $this->dealMeasurementResult($business_db, $business, $measurementResult, $measurement_0);
                }
            }
            
            //同步测试记录过程指标数据
            $measurements_1 = $business_db->name("equipment_result")->where(['equipment_id'=>$this->equipmentId,'relation_type'=>$this->resultRelationType,'step'=>1,'is_del'=>0])->field("id as equipment_result_id,firstbeatAccountId,k2 as athleteId, k1 as measurementId, lap_ids")->select();
            foreach($measurements_1 as $measurement_1) {
                if(empty($measurement_1['lap_ids'])) {
                    continue;
                }
                $lap_ids = explode(",", $measurement_1['lap_ids']);
                foreach($lap_ids as $lap_id) {
                    $measurementLapResultJson = $this->firstbeatBaseApi->getAthleteMeasurementLapResults($business, $measurement_1['firstbeatAccountId'], $measurement_1['athleteId'], $measurement_1['measurementId'], $lap_id, "list", "");
                    $measurementLapResult = json_decode($measurementLapResultJson, 1);
                    if(!empty($measurementLapResult)) {
                        $this->dealMeasurementLapResult($business_db, $business, $measurementLapResult, $measurement_1);
                    }
                }
            }
            if($recordCount) {
                $msg = [
                    'device' => $this->equipmentMark,
                    'business' => $business,
                    'message' => '同步成功'
                ];
                $this->sendMsg($business, $msg, "数据同步");
            }
            
        } catch (Exception $e) {
            $msg = [
                'device' => $this->equipmentMark,
                'business' => $business,
                'message' => $e->getMessage()
            ];
            $this->sendMsg($business, $msg, "数据同步");
        }
        
        return true;
    }
    
    /**
     * 处理时间向量
     * @param type $series
     * @return type
     */
    public function decompress($series) {
        // 解码Base64
        $decodedData = base64_decode($series['data']);

        // 解压缩数据
        $uncompressedData = zlib_decode($decodedData);

        // 创建一个可以读字节的数组
        $bytes = unpack('C*', $uncompressedData);

        $readValueFromByteArray = [
            'Float' => [
                64 => function($bytes, $index) {
                    $data = pack('C*', ...array_slice($bytes, $index * 8 + 1, 8));
                    return unpack('d', $data)[1];
                },
                32 => function($bytes, $index) {
                    $data = pack('C*', ...array_slice($bytes, $index * 4 + 1, 4));
                    return unpack('f', $data)[1];
                }
            ],
            'Unsigned' => [
                16 => function($bytes, $index) {
                    $data = pack('C*', $bytes[$index * 2 + 1], $bytes[$index * 2 + 2]);
                    return unpack('n', $data)[1];
                },
                8 => function($bytes, $index) {
                    return $bytes[$index + 1];
                }
            ],
            'Signed' => [
                16 => function($bytes, $index) {
                    $data = pack('C*', $bytes[$index * 2 + 1], $bytes[$index * 2 + 2]);
                    return unpack('s', $data)[1];
                }
            ]
        ];

        $result = [];
        $totalLength = count($bytes);
        $elementSize = $series['bits'] / 8;

        for ($i = 0; $i < $totalLength / $elementSize; $i++) {
            $result[$i] = $readValueFromByteArray[$series['type']][$series['bits']]($bytes, $i);
        }

        return $result;
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
        if(empty($business_config['mysql_database']) || empty($business_config['mysql_prefix']) || empty($business_config['mysql_hostname'])
                || empty($business_config['mysql_username']) || empty($business_config['mysql_password'])) {
            throw new Exception('商户配置错误');
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
