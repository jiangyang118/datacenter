<?php

namespace app\cron\command;
use app\common\QwRobot;
use app\model\TableWattbike;
use app\model\TableTechSkillrow;
use app\model\TableVersaClimber;
use app\model\EquipmentResult;
use app\model\EquipmentProcess;
use table\TableServer;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;
use app\model\TableFormat as TableFormatModel;
use app\model\EquipmentRelation as EquipmentRelationModel;

class SyncTableData extends Command{

    protected function configure() {
        $this->setName('SyncTableData')->setDescription('this api is for SyncTableData')
            ->addArgument('business')//商户唯一标识;
            ->addArgument('op');//操作项
    }

    /**
     * tablestore数据同步脚本
     * php think SyncTableData sxkx 0 定时同步脚本-concept2 每分钟执行一次
     * php think SyncTableData sxkx 1 初始化数据脚本-concept2
     * php think SyncTableData sxkx 2 初始化数据脚本-wattbike
     * php think SyncTableData sxkx 3 定时同步脚本-wattbike 每分钟执行一次
     */
    protected function execute(Input $input, Output $output) {
        echo date('Y-m-d H:i:s'). ' 数据开始同步' . PHP_EOL;
        $business = $input->getArgument('business');
        $op = $input->getArgument('op');
        $op = empty($op) ? 0 : $op;
        switch ($op) {
            case 0:
                 //定时同步脚本-concept2
                 $this->sysc_concept2_data($business);
                 break;
            /**case 1:
                 //初始化数据脚本-concept2
                 $this->init_concept2_data();
                 break;*/
            case 2:
                //初始化数据脚本-wattbike
                $this->init_wattbike_data($business);
                break;
            case 3:
                //定时同步脚本-wattbike
                $this->sysc_wattbike_data($business);
                break;
            case 4:
                //定时同步脚本-泰诺健划船机
                $this->sysc_skillrow_data($business);
                break;
            case 5:
                //定时同步脚本-攀爬机
                $this->sysc_climber_data($business);
                break;
            default:
                break;
        }
        echo date('Y-m-d H:i:s'). ' 数据同步完成' . PHP_EOL;
    }

    /**
     * 按映射关系生成数据
     * @param type $data
     * @param type $flag 1:结果数据，2：过程数据
     * @param type $convert 1:k转v(转化为映射),2:v转k（还原）
     * @return type
     */
    public function concept2RelationConvert($relations, $data, $convert=1) {
        if($convert == 2) {
            $relations = array_flip($relations);
        }
        if(is_array($data)) {
            foreach($data as $kk=>$vv) {
                if(array_key_exists($kk, $relations)) {
                    $resultOrExtendKey = $relations[$kk];
                    $data[$resultOrExtendKey] = $vv;
                    unset($data[$kk]);
                }
            }
        }
        return $data;
    }
    
    //同步数据脚本-concept2
    public function sysc_concept2_data($business) {
        $sys_end = time();//当前时间
        $sys_stat = $sys_end- 70;//1分钟10秒前时间，防止数据遗漏，多查10秒数据
        echo date('Y-m-d H:i:s'). ' 数据同步区间'.date('Y-m-d H:i:s', $sys_stat).' - '.date('Y-m-d H:i:s', $sys_end).PHP_EOL;
        
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在：' . $business . '_' . PHP_EOL;
            return;
        }
        echo date('Y-m-d H:i:s'). ' 数据同步区间'.date('Y-m-d H:i:s', $sys_stat).' - '.date('Y-m-d H:i:s', $sys_end).PHP_EOL;

        $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
        $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
        $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';
        $db_mysql = get_db($mysql_database, $mysql_prefix,$mysql_hostname);
        
        $table_format_model = new TableFormatModel();
        
        $concept2_model = new EquipmentResult();
        $details_model = new EquipmentProcess();

        $columnss = $table_format_model->getTableFields();
        
        $instanceName = $business_config['concept2']['instanceName'];//实例名称   cpt-table
        $tableName = $business_config['concept2']['tableName'];//table    shtks_concept2_dev
        $tableServer = new TableServer($instanceName, $tableName);
        
        $relationModel = new EquipmentRelationModel();
        $resultRelationType = 55;
        $processRelationType = 56;
        $resultRelation = $relationModel->inquiryColumn(['type'=> $resultRelationType, 'is_del'=>0], "v", "k");
        $processRelation = $relationModel->inquiryColumn(['type'=> $processRelationType, 'is_del'=>0], "v", "k");
        
        $last_device_data = [];//上一条数据
        $equipment_data = [];//设备信息
        $details_data = [];//子数据详情
        $details_data_device = [];//子数据详情-设备id
        $concept2_data = [];//主数据详情
        $staff_departments = [];//人员部门

        $count = 0;

        while (true) {
            $stat = $sys_stat;
            $end = ($sys_stat + 10);
            $startPK = array (
                array('id', $stat * 1000),
            );
            $endPK = array (
                array('id', $end * 1000),
            );
            $getData = $tableServer->getRange($startPK, $endPK);
            if ($getData['code']) {
                echo date('Y-m-d H:i:s') . ' 获取数据失败' . $getData['message'] . PHP_EOL;
                break;
            }
            $test_data = $getData['data'];
            foreach ($test_data as $key => $value) {
                if (isset($value['columns'])) {
                    $columns = json_decode($value['columns'], true);
                    unset($test_data[$key]['columns']);
                    $checkFailedData = $columns['checkFailedData'];
                    unset($columns['checkFailedData']);
                    $columns = array_merge($columns, $checkFailedData);
                    foreach ($columns as $k => $column) {
                        if (is_array($column)) {
                            $columns[$k] = !empty($column['value']) ? $column['value'] : '';
                        }
                    }
                    $test_data[$key] = array_merge($test_data[$key], $columns);
                }
                $data = [];
                $data = $test_data[$key];
                $data['cid'] = $data['id'];
                unset($data['id']);
                $data['time'] = date('Y-m-d H:i:s', $data['cid'] / 1000);
                $data['ctime'] = date('Y-m-d H:i:s');
                //当前设备的上一条数据
                $last_data = !empty($last_device_data[$data['device_id']]) ? $last_device_data[$data['device_id']] : null;
                if (empty($last_data)) {
                    $last_data = $table_format_model->inquiryOne(['device_id' => $data['device_id']], ['id' => 'desc']);
                }
                //异常数据处理
                if (!isset($data['elapsedTime'])) {
                    $data['elapsedTime'] = $last_data['elapsedTime'];
                }
                //重复数据处理
                if (!empty($last_data) && $data['cid'] <= $last_data['cid']) {
                    echo date('Y-m-d H:i:s') . ' 重复数据处理'. $data['cid'] . PHP_EOL;
                    continue;
                }
                //判断字段是否存在
                foreach ($data as $s => $va) {
                    if (!in_array($s, $columnss)) {
                        echo date('Y-m-d H:i:s') . 'columns not exist：' . $s . PHP_EOL;
                        unset($data[$s]);
                    }
                }
                //数据合并入库
                if (empty($last_data) || $data['elapsedTime'] != $last_data['elapsedTime']) {
                    //插入数据
                    $data['id'] = $table_format_model->add($data);
                    $last_device_data[$data['device_id']] = $data;
                } else {
                    //更新数据
                    $update = [];
                    foreach ($data as $data_k => $data_v) {
                        if (!empty($data_v) && (!isset($last_data[$data_k]) || $data_v != $last_data[$data_k])) {
                            $update[$data_k] = $data_v;
                            $last_data[$data_k] = $data_v;
                        }
                    }
                    if (!empty($update)) {
                        $table_format_model->modifyById($last_data['id'], $update);
                    }
                    $last_device_data[$data['device_id']] = $last_data;
                }
                $format_data = $last_device_data[$data['device_id']];
                //需要的字段初始化
                $format_data['elapsed_his'] = $format_data['elapsedTime'];
                $format_data['workoutType'] = !empty($format_data['workoutType']) ? $format_data['workoutType'] : '';
                $format_data['totalCalories'] = !empty($format_data['totalCalories']) ? $format_data['totalCalories'] : '';
                $format_data['strokeCount'] = !empty($format_data['strokeCount']) ? $format_data['strokeCount'] : 0;
                $format_data['speed'] = !empty($format_data['speed']) ? $format_data['speed'] : '';
                $format_data['strokeRate'] = !empty($format_data['strokeRate']) ? $format_data['strokeRate'] : 0;
                $format_data['strokeDistance'] = !empty($format_data['strokeDistance']) ? $format_data['strokeDistance'] : '';
                $format_data['distance'] = !empty($format_data['distance']) ? $format_data['distance'] : '';
                $format_data['driveTime'] = !empty($format_data['driveTime']) ? $format_data['driveTime'] : '';
                $format_data['strokeRecoveryTime'] = !empty($format_data['strokeRecoveryTime']) ? $format_data['strokeRecoveryTime'] : '';
                //数据处理
                $format_data['elapsedTime'] = his_to_seconds($format_data['elapsedTime']);
                $format_data['driveTime'] = his_to_seconds($format_data['driveTime']);
                $format_data['strokeRecoveryTime'] = his_to_seconds($format_data['strokeRecoveryTime']);
                $format_data['totalCalories'] = str_preg_num($format_data['totalCalories']);
                $format_data['speed'] = str_preg_num($format_data['speed']);
                $format_data['strokeDistance'] = str_preg_num($format_data['strokeDistance']);
                $format_data['distance'] = str_preg_num($format_data['distance']);

                //设备
                if (isset($equipment_data[$format_data['device_id']])) {
                    $equipment = $equipment_data[$format_data['device_id']];
                } else {
                    $equipment = $equipment_data[$format_data['device_id']] = $db_mysql->name("device_manage")->where(['serial_number' => $format_data['device_id']])->find();
                }
                //部门
                $staff_department = !empty($staff_departments[$format_data['staff_uuid']]) ? $staff_departments[$format_data['staff_uuid']] : null;
                if (empty($staff_department)) {
                    //根据人员uuid获取部门
                    $department = $db_mysql->name("department")
                            ->alias("a")
                            ->join("staff_department b", 'a.uuid = b.department_uuid', 'left')
                            ->where(['b.staff_uuid'=>$format_data['staff_uuid'], 'a.is_show'=>1, 'a.status'=>1])
                            ->field(['a.uuid','a.name'])
                            ->find();
                    if(!empty($department)) {
                        $staff_departments[$format_data['staff_uuid']] = $department;
                    }
                }
                $department_uuid = !empty($staff_departments[$format_data['staff_uuid']]['uuid']) ? $staff_departments[$format_data['staff_uuid']]['uuid'] : '';
                
                //数据入库
                $concept2 = [
                    'business' => $business,
                    'equipment_id' => !empty($equipment['id']) ? $equipment['id'] : '',//设备id
                    'equipment_mark' => 'concept2',
                    'device_id' => $format_data['device_id'],//设备序列号,前端传过来的
                    'relation_type' => $resultRelationType,
                    'department_uuid' => $department_uuid,
                    'staff_uuid' => $format_data['staff_uuid'],
                    'date' => date('Y-m-d', $format_data['cid'] / 1000),
                    'record_time' => $format_data['time'],
                    'reference' => '',//业务系统中存储的是数据中心的ID
                    'action_name' => $format_data['workoutType'],//空
                    'elapsed_time' => $format_data['elapsedTime'],//00:00:00.5//转换秒
                    'total_calories' => $format_data['totalCalories'],//78cal 空
                    'stroke_count' => $format_data['strokeCount'],//空
                    'speed' => $format_data['speed'],//0m/s 空
                    'stroke_rate' => $format_data['strokeRate'],//空
                    'stroke_distance' => $format_data['strokeDistance'],//7m 空
                    'distance' => $format_data['distance'],//0m 空 110m
                    'format_data_id' => $format_data['id'],
                    'is_del' => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                    'create_by' => 'cron',
                ];
                $details = [
                    'equipment_result_id' => '',
                    'reference' => '',//业务系统中存储的是数据中心的id
                    'relation_type' => $processRelationType,
                    'business' => $business,
                    'device_id' => $concept2['device_id'],//设备序列号
                    'action_name' => $concept2['action_name'],
                    'stroke_rate' => $concept2['stroke_rate'],
                    'stroke_distance' => $concept2['stroke_distance'],
                    'format_data_id' => $concept2['format_data_id'],
                    'elapsed_his' => $format_data['elapsed_his'],//00:00:00.5
                    'drive_time' => $format_data['driveTime'],//00:00:00.5//转换秒
                    'stroke_recovery_time' => $format_data['strokeRecoveryTime'],//00:00:00.5//转换秒
                    'is_del' => 0,
                    'create_time' => date('Y-m-d H:i:s'),
                    'create_by' => 'cron',
                ];
//                detail中format_data_id：k1,device_id:k2,relation_type:k3,business:k4
                //查询子表是否存在
                if (!empty($details_data[$details['format_data_id']])) {
                    $last_details = $details_data[$details['format_data_id']];
                } else {
                    $last_details = $details_data[$details['format_data_id']] = $details_model->inquiryOne(['k4'=>$business,'k3'=>$processRelationType,'k1'=>$details['format_data_id'], 'is_del' => 0]);
                    $last_details = $this->concept2RelationConvert($processRelation, $last_details, 2);
                }
                if (empty($last_details)) {//没有明细数据
                    //设备子数据
                    if (!empty($details_data_device[$details['device_id']])) {
                        $last_details_device = $details_data_device[$details['device_id']];
                    } else {
                        $last_details_device = $details_data_device[$details['device_id']] = $details_model->inquiryOne(['k4'=>$business,'k3'=>$processRelationType,'k2'=>$details['device_id'], 'is_del' => 0], ['id' => 'desc']);
                        $last_details_device = $this->concept2RelationConvert($processRelation, $last_details_device, 2);
                    }
                    if (empty($last_details_device)) {//没有设备的明细数据
                        //插入主数据
                        echo date('Y-m-d H:i:s')  . '插入数据中心主数据1' . PHP_EOL;
//                        $concept2['uuid'] = guid();
//                        $details['concept2_uuid'] = $concept2['uuid'];
                        $result = $this->concept2RelationConvert($resultRelation, $concept2, 1);
                        $concept2['id'] = $concept2_model->add($result);
                        
                        echo date('Y-m-d H:i:s')  . '插入数据中心子数据2' . PHP_EOL;
                        $details['equipment_result_id'] = $concept2['id'];
                        $proccess = $this->concept2RelationConvert($processRelation, $details, 1);
                        $details['id'] = $details_model->add($proccess);
                        
                        echo date('Y-m-d H:i:s')  . '插入业务系统主数据1' . PHP_EOL;
                        $result['reference'] = $concept2['id'];
                        $business_concept2_id = $db_mysql->name("equipment_result")->insertGetId($result);
                        //插入业务系统子数据
                        echo date('Y-m-d H:i:s')  . '插入业务系统子数据2' . PHP_EOL;
                        $proccess['reference'] = $details['id'];
                        $proccess['equipment_result_id'] = $business_concept2_id;//一定要修改成业务系统的结果表id
                        $business_concept2_detail_id = $db_mysql->name("equipment_process")->insertGetId($proccess);
                    } else {//有设备的明细数据
                        $last_details = $last_details_device;
                        if ($last_details['elapsed_his'] != $format_data['elapsed_his']) {
                            if ($format_data['elapsed_his'] == '00:00:00.0') {//重新开始一次测试
                                echo date('Y-m-d H:i:s')  . '插入数据中心主数据3' . PHP_EOL;
                                $result = $this->concept2RelationConvert($resultRelation, $concept2, 1);
                                $concept2['id'] = $concept2_model->add($result);
                                echo date('Y-m-d H:i:s')  . '插入数据中心子数据4' . PHP_EOL;
                                $details['equipment_result_id'] = $concept2['id'];
                                $proccess = $this->concept2RelationConvert($processRelation, $details, 1);
                                $details['id'] = $details_model->add($proccess);
                                echo date('Y-m-d H:i:s')  . '插入业务系统主数据3' . PHP_EOL;
                                $result['reference'] = $concept2['id'];
                                $business_concept2_id = $db_mysql->name("equipment_result")->insertGetId($result);
                                echo date('Y-m-d H:i:s')  . '插入业务系统子数据4' . PHP_EOL;
                                $proccess['reference'] = $details['id'];
                                $proccess['equipment_result_id'] = $business_concept2_id;//一定要修改成业务系统的结果表id
                                $business_concept2_detail_id = $db_mysql->name("equipment_process")->insertGetId($proccess);
                                
                            } else {//继续本次测试
                                echo date('Y-m-d H:i:s')  . '插入数据中心子数据5' . PHP_EOL;
                                $concept2['id'] = $last_details['equipment_result_id'];
                                $details['equipment_result_id'] = $concept2['id'];
                                $proccess = $this->concept2RelationConvert($processRelation, $details, 1);
                                $details['id'] = $details_model->add($proccess);
                                
                                echo date('Y-m-d H:i:s')  . '插入业务系统子数据5' . PHP_EOL;
                                $proccess['reference'] = $details['id'];
                                $business_concept2 = $db_mysql->name("equipment_result")->where(['business'=>$business,'relation_type'=>$resultRelationType,'reference'=>$concept2['id'],'is_del'=>0])->find();
                                $business_concept2_id = !empty($business_concept2['id']) ? $business_concept2['id'] : '';
                                $proccess['equipment_result_id'] = $business_concept2_id;//一定要修改成业务系统的结果表id
                                $business_concept2_detail_id = $db_mysql->name("equipment_process")->insertGetId($proccess);
                                
                                if (!empty($concept2_data[$details['equipment_result_id']])) {
                                    $last_concept2 = $concept2_data[$details['equipment_result_id']];
                                } else {
                                    $last_concept2 = $concept2_data[$details['equipment_result_id']] = $concept2_model->inquiryOne(['id' => $details['equipment_result_id']]);
                                    $last_concept2 = $this->concept2RelationConvert($resultRelation, $last_concept2, 2);
                                }
                                $concept2['id'] = $last_concept2['id'];
                                unset($concept2['create_time']);
                                $update = [];
                                foreach ($concept2 as $concept2_k => $concept2_v) {
                                    if (!empty($concept2_v) && $concept2_v != $last_concept2[$concept2_k]) {
                                        $update[$concept2_k] = $concept2_v;
                                        $last_concept2[$concept2_k] = $concept2_v;
                                    }
                                }
                                if (!empty($update)) {
                                    echo date('Y-m-d H:i:s')  . '更新数据中心主数据6' . PHP_EOL;
                                    $result = $this->concept2RelationConvert($resultRelation, $update, 1);
                                    $concept2_model->modifyById($last_concept2['id'], $result);
                                    
                                    echo date('Y-m-d H:i:s')  . '更新业务系统主数据6' . PHP_EOL;
                                    if(isset($result['id'])) {
                                        unset($result['id']);
                                    }
                                    $db_mysql->name("equipment_result")->where(['business'=>$business,'relation_type'=>$resultRelationType,'reference'=>$last_concept2['id'],'is_del'=>0])->update($result);
                                }
                                $concept2 = $last_concept2;
                            }
                        } else {
                            $concept2['id'] = $last_details['equipment_result_id'];
                            $details['equipment_result_id'] = $concept2['id'];
                            $details['id'] = $last_details['id'];
                            unset($details['create_time']);
                            $update = [];
                            foreach ($details as $details_k => $details_v) {
                                if (!empty($details_v) && $details_v != $last_details[$details_k]) {
                                    $update[$details_k] = $details_v;
                                    $last_details[$details_k] = $details_v;
                                }
                            }
                            if (!empty($update)) {
                                echo date('Y-m-d H:i:s')  . '更新数据中心子数据7' . PHP_EOL;
                                $proccess = $this->concept2RelationConvert($processRelation, $update, 1);
                                $details_model->modifyById($last_details['id'], $proccess);
                                echo date('Y-m-d H:i:s')  . '更新业务系统子数据7' . PHP_EOL;
                                if(isset($proccess['id'])) {
                                    unset($proccess['id']);
                                }
                                $business_concept2 = $db_mysql->name("equipment_result")->where(['business'=>$business,'relation_type'=>$resultRelationType,'reference'=>$last_details['equipment_result_id'],'is_del'=>0])->find();
                                $business_concept2_id = !empty($business_concept2['id']) ? $business_concept2['id'] : '';
                                $proccess['equipment_result_id'] = $business_concept2_id;
                                $db_mysql->name("equipment_process")->where(['k4'=>$business,'k3'=>$processRelationType,'reference'=>$last_details['id'],'is_del'=>0])->update($proccess);
                            }
                            $details = $last_details;
                            if (!empty($concept2_data[$details['equipment_result_id']])) {
                                $last_concept2 = $concept2_data[$details['equipment_result_id']];
                            } else {
                                $last_concept2 = $concept2_data[$details['equipment_result_id']] = $concept2_model->inquiryOne(['id' => $details['equipment_result_id']]);
                                $last_concept2 = $this->concept2RelationConvert($resultRelation, $last_concept2, 2);
                            }
                            $concept2['id'] = $last_concept2['id'];
                            unset($concept2['create_time']);
                            $update = [];
                            foreach ($concept2 as $concept2_k => $concept2_v) {
                                if (!empty($concept2_v) && $concept2_v != $last_concept2[$concept2_k]) {
                                    $update[$concept2_k] = $concept2_v;
                                    $last_concept2[$concept2_k] = $concept2_v;
                                }
                            }
                            if (!empty($update)) {
                                echo date('Y-m-d H:i:s')  . '更新数据中心主数据8' . PHP_EOL;
                                $result = $this->concept2RelationConvert($resultRelation, $update, 1);
                                $concept2_model->modifyById($last_concept2['id'], $result);
                                echo date('Y-m-d H:i:s')  . '更新业务系统主数据8' . PHP_EOL;
                                if(isset($result['id'])) {
                                    unset($result['id']);
                                }
                                $db_mysql->name("equipment_result")->where(['business'=>$business,'relation_type'=>$resultRelationType,'reference'=>$last_concept2['id'],'is_del'=>0])->update($result);
                            }
                            $concept2 = $last_concept2;
                        }
                    }
                } else {//有明细数据
                    //更新子数据
//                    $concept2['uuid'] = $last_details['concept2_uuid'];
//                    $details['concept2_uuid'] = $concept2['uuid'];
                    $concept2['id'] = $last_details['equipment_result_id'];
                    $details['equipment_result_id'] = $concept2['id'];
                    $details['id'] = $last_details['id'];
                    unset($details['create_time']);
                    $update = [];
                    foreach ($details as $details_k => $details_v) {
                        if (!empty($details_v) && $details_v != $last_details[$details_k]) {
                            $update[$details_k] = $details_v;
                            $last_details[$details_k] = $details_v;
                        }
                    }
                    if (!empty($update)) {
                        echo date('Y-m-d H:i:s')  . '更新数据中心子数据9' . PHP_EOL;
                        $proccess = $this->concept2RelationConvert($processRelation, $update, 1);
                        $details_model->modifyById($last_details['id'], $proccess);
                        
                        echo date('Y-m-d H:i:s')  . '更新业务系统子数据9' . PHP_EOL;
                        if(isset($proccess['id'])) {
                            unset($proccess['id']);
                        }
                        $business_concept2 = $db_mysql->name("equipment_result")->where(['business'=>$business,'relation_type'=>$resultRelationType,'reference'=>$last_details['equipment_result_id'],'is_del'=>0])->find();
                        $business_concept2_id = !empty($business_concept2['id']) ? $business_concept2['id'] : '';
                        $proccess['equipment_result_id'] = $business_concept2_id;
                        $db_mysql->name("equipment_process")->where(['k4'=>$business,'k3'=>$processRelationType,'reference'=>$last_details['id'],'is_del'=>0])->update($proccess);
                    }
                    $details = $last_details;
                    if (!empty($concept2_data[$details['equipment_result_id']])) {
                        $last_concept2 = $concept2_data[$details['equipment_result_id']];
                    } else {
                        $last_concept2 = $concept2_data[$details['equipment_result_id']] = $concept2_model->inquiryOne(['id' => $details['equipment_result_id']]);
                        $last_concept2 = $this->concept2RelationConvert($resultRelation, $last_concept2, 2);
                    }
                    $concept2['id'] = $last_concept2['id'];
                    unset($concept2['create_time']);
                    $update = [];
                    foreach ($concept2 as $concept2_k => $concept2_v) {
                        if (!empty($concept2_v) && $concept2_v != $last_concept2[$concept2_k]) {
                            $update[$concept2_k] = $concept2_v;
                            $last_concept2[$concept2_k] = $concept2_v;
                        }
                    }
                    if (!empty($update)) {
                        echo date('Y-m-d H:i:s')  . '更新数据中心主数据10' . PHP_EOL;
                        $result = $this->concept2RelationConvert($resultRelation, $update, 1);
                        $concept2_model->modifyById($last_concept2['id'], $result);
                        echo date('Y-m-d H:i:s')  . '更新业务系统主数据10' . PHP_EOL;
                        if(isset($result['id'])) {
                            unset($result['id']);
                        }
                        $db_mysql->name("equipment_result")->where(['business'=>$business,'relation_type'=>$resultRelationType,'reference'=>$last_concept2['id'],'is_del'=>0])->update($result);
                    }
                    $concept2 = $last_concept2;
                }
                //插入主数据
                //插入子数据
                //更新主数据
                //更新子数据
                $details_data[$details['format_data_id']] = $details_data_device[$details['device_id']] = $details;
                $concept2_data[$details['equipment_result_id']] = $concept2;
            }
            $sys_stat = $end;
            if ($sys_stat >= $sys_end) {
                break;
            }
            $count += count($test_data);
        }
        echo date('Y-m-d H:i:s') . ' sync count:' . $count .  PHP_EOL;
    }
    
    
    /**
     *泰诺健划船机数据同步
     **/
    public function sysc_skillrow_data($business) {
        $sys_end = time();//当前时间
        $sys_stat = $sys_end- 70;//1分钟10秒前时间，防止数据遗漏，多查10秒数据

        echo date('Y-m-d H:i:s'). ' 数据同步区间'.date('Y-m-d H:i:s', $sys_stat).' - '.date('Y-m-d H:i:s', $sys_end).PHP_EOL;

        $equipment_result_mode = new EquipmentResult();
        $equipment_process_mode = new EquipmentProcess();
        $table_tech_skillrow_model = new TableTechSkillrow();

        $columnss = $table_tech_skillrow_model->getTableFields();

        $index = 0;//数据循环次数
        $time_index = 0;//时间循环次数
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在：' . $business . '_' . PHP_EOL;
            return;
        }
//        var_dump($business_set[$business]['name']);die;
        $instanceName = $business_set[$business]['tech-skillrow']['instanceName'];//实例名称
        $tableName = $business_set[$business]['tech-skillrow']['tableName'];//table
        $tableServer = new TableServer($instanceName, $tableName);
        $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
        $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
        $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';
//        $db_mysql = get_db($mysql_database, $mysql_prefix,$mysql_hostname);
        $mysql_username = !empty($business_config['mysql_username']) ? $business_config['mysql_username'] : '';
        $mysql_password= !empty($business_config['mysql_password']) ? $business_config['mysql_password'] : '';
        $db_mysql = get_db_mysql($mysql_database, $mysql_prefix, $mysql_hostname, $mysql_username, $mysql_password);

        $last_data = [];//上一条数据
        $last_device_data = [];//上一条数据

        $last_data1 = [];//上一条数据
        $last_device_data1 = [];//上一条数据
//        var_dump(ceil(microtime(true) * 1000) );die;
//        var_dump(date("Y-m-d H:i:s",1721375621375/1000));die;
        //设备信息
//        $equipments = [];/home/wwwroot/qjydy/Application/Runtime/Logs/Home/
            $stat = $sys_stat;
        //    $end = $sys_stat + 10;
        $end = $sys_end;
         $startPK = array (
             array('id', $stat * 1000),
         );
         $endPK = array (
             array('id', $end * 1000),
         );
        //临时设置
//        $startPK = array (
//            array('id', 1724933231950),
//        );
//        $endPK = array (
//            array('id', 1724933240983),
//        );

        $getData = $tableServer->getRange($startPK, $endPK);
        if ($getData['code']) {
            echo date('Y-m-d H:i:s') . ' 获取数据失败' . $getData['message'].PHP_EOL;
        }
        $test_data = $getData['data'];

        foreach ($test_data as $key => $value) {
            if (isset($value['columns'])) {
                $columns = json_decode($value['columns'], true);
                unset($test_data[$key]['columns']);
                foreach ($columns as $k => $column) {
                    if (is_array($column)) {
                        $columns[$k] =  !empty($column['value']) ? $column['value'] : '';
                    }
                }
                $json_data = json_decode($columns['data']);
                unset($columns['data']);
                foreach($json_data as $json_data_key => $json_data_value)
                    $columns[$json_data_key] = $json_data_value;
                $test_data[$key] = array_merge($test_data[$key], $columns);
            }
            $data = [];
            $data = $test_data[$key];
            $data['cid'] = $data['id'];
            unset($data['id']);
            unset($data['topic']);
            $data['time'] = date('Y-m-d H:i:s', $data['cid'] / 1000);
            $data['date'] = date('Y-m-d', $data['cid'] / 1000);
            //判断字段是否存在
            foreach ($data as $s => $va) {
                if (!in_array($s, $columnss)) {
                    echo date('Y-m-d H:i:s') . 'columns not exist：' . $s . PHP_EOL;
                    unset($data[$s]);
                }
            }
            //如果数据包计数为0则过滤掉
            if ($data['sequence'] == 0) {
                echo date('Y-m-d H:i:s') . ' count is 0' . PHP_EOL;
                continue;
            }

            //总距离为0的过滤
            if ($data['totalDistance'] == 0) {
                echo date('Y-m-d H:i:s') . ' count is 0' . PHP_EOL;
                continue;
            }
            //当前设备的上一条数据
            $last_table_data = $last_device_data[$data['terminalMac']] = $table_tech_skillrow_model->inquiryOne(['terminalMac' => $data['terminalMac'],'staff_uuid'=>$data['staff_uuid'],'date'=>$data['date']], ['id' => 'desc']);

            //重复数据处理
            if (!empty($last_table_data) && $data['cid'] <= $last_table_data['cid']) {
                echo date('Y-m-d H:i:s') . ' 重复数据处理'. $data['cid'] . PHP_EOL;
                continue;
            }
            //调试中暂时注释
            $data['id'] = $table_tech_skillrow_model->add($data);

            //当前设备的上一条数据
            $last_table_data1 = $last_device_data1[$data['terminalMac']] = $db_mysql->name('table_tech_skillrow')->where(['terminalMac' => $data['terminalMac'],'staff_uuid'=>$data['staff_uuid'],'date'=>$data['date']])->order('id desc')->find();;
            //重复数据处理
            if (!empty($last_table_data1) && $data['cid'] <= $last_table_data1['cid']) {
                echo date('Y-m-d H:i:s') . ' 重复数据处理'. $data['cid'] . PHP_EOL;
                continue;
            }
            $data1= $data;

            //调试中暂时注释
            $data1['id'] = $db_mysql->name('table_tech_skillrow')->insertGetId($data);

            $format_data = $data;

            $format_data['staff_uuid'] = !empty($format_data['staff_uuid']) ? $format_data['staff_uuid'] : '';
            $format_data['DeviceID'] = !empty($format_data['DeviceID']) ? $format_data['DeviceID'] : '';

            /*$equipment = !empty($equipments[$format_data['DeviceID']]) ? $equipments[$format_data['DeviceID']] : null;
            if (empty($equipment)) {
                $equipment = $device_manage_model->inquiryOne(['id' => $format_data['DeviceID']]);
                if (!empty($equipment)) {
                    $equipments[$format_data['DeviceID']] = $equipment;
                }
            }*/
            //是否通过设备mac 获取设备id


            $department_uuid = '';
            // 根据人员uuid获取部门的uuid
            if (!empty($format_data['staff_uuid'])){
                $department_uuid_arr = $db_mysql->name('staff_department')->where(['staff_uuid' => $format_data['staff_uuid']])->order(['create_time' => 'desc'])->field('department_uuid')->find();
                $department_uuid = !empty($department_uuid_arr) ? $department_uuid_arr['department_uuid'] : '';
            }

            $equipment_id = 2;
            $equipment_name = '泰诺健划船机';

            if($data['terminalMac'])
            {
                $serial_number = $data['terminalMac'];
                $device_info = $db_mysql->name('device_manage')->where(['serial_number' => $serial_number])->find();
                if($device_info)
                {
                    $equipment_id = $device_info['id'];
                    $equipment_name = $device_info['name'];
                }
            }
            //结果表入库
            $equipment_result = [
                'business' => $business,
                'equipment_id' => $equipment_id,
                'equipment_mark' =>$equipment_name,
                'relation_type' =>'43',
                'department_uuid' => $department_uuid,
                'staff_uuid' => $format_data['staff_uuid'], // 人员标识
                'date' => date('Y-m-d', $format_data['cid'] / 1000), //运动日期
                'record_time' => $format_data['time'],// 运动时间
                'k1' => $format_data['totalDistance'],
                'k2' => $format_data['paceInstantaneous'],
                'k3' => $format_data['paceAverage'],
                'k4' => $format_data['powerInstantaneous'],
                'k5' => $format_data['powerAverage'],
                'k6' => $format_data['resistanceLevel'],
                'k7' => $format_data['met'],
                'k8' => $format_data['elapsedTime'],
                'k9' => $format_data['strokeRate'],
                'k10' => $format_data['strokeCount'],
                'k11' => $format_data['strokeAverageRate'],
                'k12' => $format_data['energyTotal'],
                'k13' => $format_data['energyPerHour'],
                'k14' => $format_data['energyPerMinute'],
                'k15' => $format_data['terminalMac'],
                'k16' => $format_data['cid'] / 1000,
                'k17' => $format_data['sequence']
            ];

            $equipment_process = [
                'k1' => $format_data['totalDistance'],
                'k2' => $format_data['paceInstantaneous'],
                'k3' => $format_data['paceAverage'],
                'k4' => $format_data['powerInstantaneous'],
                'k5' => $format_data['powerAverage'],
                'k6' => $format_data['resistanceLevel'],
                'k7' => $format_data['met'],
                'k8' => $format_data['elapsedTime'],
                'k9' => $format_data['strokeRate'],
                'k10' => $format_data['strokeCount'],
                'k11' => $format_data['strokeAverageRate'],
                'k12' => $format_data['energyTotal'],
                'k13' => $format_data['energyPerHour'],
                'k14' => $format_data['energyPerMinute'],
                'k15' => $format_data['terminalMac'],
                'k16' => $format_data['cid'] / 1000,
                'k17' => $format_data['sequence']
            ];

            //根据人员、日期、数据包时间判断是添加还是修改
//                if (empty($last_data[$equipment_result['k15']])) {
            $last_where = [
                'k15' => $equipment_result['k15'],
                'date' => $equipment_result['date'],
                'staff_uuid' => $equipment_result['staff_uuid'],
            ];
            $last_data[$equipment_result['k15']] = $equipment_result_mode->inquiryOne($last_where, ['id' => 'desc']);
//                }

//                if (empty($last_data1[$equipment_result['k15']])) {
//                    $last_where = [
//                        'k15' => $equipment_result['k15'],
//                        'date' => $equipment_result['date'],
//                        'staff_uuid' => $equipment_result['staff_uuid'],
//                    ];
            $last_data1[$equipment_result['k15']] =$db_mysql->name('equipment_result')->where($last_where)->order('id desc')->find();;
//                }
            // 使用 strtotime 函数将时间字符串转换为时间戳
            $start_timestamp = strtotime($equipment_result['record_time']);
            $end_timestamp1 = strtotime($last_data1[$equipment_result['k15']]['update_time']);
            $end_timestamp = strtotime($last_data[$equipment_result['k15']]['update_time']);

            // 计算两个时间戳之间的差值
            $diff_seconds1 = abs($end_timestamp1 - $start_timestamp);
            $diff_seconds = abs($end_timestamp - $start_timestamp);
            if (empty($last_data1[$equipment_result['k15']]) || (!empty($last_data1[$equipment_result['k15']]) && $last_data1[$equipment_result['k15']]['k17']>$format_data['sequence']) || $diff_seconds1 > 60) {
                $equipment_result_id = $db_mysql->name('equipment_result')->insertGetId($equipment_result);
                $equipment_process['equipment_result_id'] = $equipment_result_id;
                $db_mysql->name('equipment_process')->insertGetId($equipment_process);
                $last_data1[$equipment_result['k15']] = $equipment_result;
            }elseif (!empty($last_data1[$equipment_result['k15']])&&$last_data1[$equipment_result['k15']]['k17']<$format_data['sequence']) {
                $equipment_process['equipment_result_id'] = $last_data1[$equipment_result['k15']]['id'];
                $update = [
                    'k1' => $format_data['totalDistance'],
                    'k2' => $format_data['paceInstantaneous'],
                    'k3' => $format_data['paceAverage'],
                    'k4' => $format_data['powerInstantaneous'],
                    'k5' => $format_data['powerAverage'],
                    'k6' => $format_data['resistanceLevel'],
                    'k7' => $format_data['met'],
                    'k8' => $format_data['elapsedTime'],
                    'k9' => $format_data['strokeRate'],
                    'k10' => $format_data['strokeCount'],
                    'k11' => $format_data['strokeAverageRate'],
                    'k12' => $format_data['energyTotal'],
                    'k13' => $format_data['energyPerHour'],
                    'k14' => $format_data['energyPerMinute'],
                    'k16' => $format_data['cid'] / 1000,
                    'k17' => $format_data['sequence']
                ];
                $db_mysql->name('equipment_result')->where('id', $last_data1[$equipment_result['k15']]['id'])->update($update);
                $db_mysql->name('equipment_process')->insertGetId($equipment_process);
            }

            if (empty($last_data[$equipment_result['k15']]) || (!empty($last_data[$equipment_result['k15']]) && $last_data[$equipment_result['k15']]['k17']>$format_data['sequence'])|| $diff_seconds > 60) {
                $equipment_result_id = $equipment_result_mode->add($equipment_result);
                $equipment_process['equipment_result_id'] = $equipment_result_id;
                $equipment_process_mode->add($equipment_process);
                $last_data[$equipment_result['k15']] = $equipment_result;
                QwRobot::pushMsgFormat('划船机数据同步成功',$business_set[$business]['name'].':'.'划船机数据同步成功');
            } elseif (!empty($last_data[$equipment_result['k15']]) && $last_data[$equipment_result['k15']]['k17']<$format_data['sequence']) {
//                    var_dump($last_data[$equipment_result['k15']]);
                $equipment_process['equipment_result_id'] = $last_data[$equipment_result['k15']]['id'];
                $update = [
                    'k1' => $format_data['totalDistance'],
                    'k2' => $format_data['paceInstantaneous'],
                    'k3' => $format_data['paceAverage'],
                    'k4' => $format_data['powerInstantaneous'],
                    'k5' => $format_data['powerAverage'],
                    'k6' => $format_data['resistanceLevel'],
                    'k7' => $format_data['met'],
                    'k8' => $format_data['elapsedTime'],
                    'k9' => $format_data['strokeRate'],
                    'k10' => $format_data['strokeCount'],
                    'k11' => $format_data['strokeAverageRate'],
                    'k12' => $format_data['energyTotal'],
                    'k13' => $format_data['energyPerHour'],
                    'k14' => $format_data['energyPerMinute'],
                    'k16' => $format_data['cid'] / 1000,
                    'k17' => $format_data['sequence']
                ];
                $equipment_result_mode->modifyById($last_data[$equipment_result['k15']]['id'], $update);
                $equipment_process_mode->add($equipment_process);
            }
            $index ++;
        }
        echo date('Y-m-d H:i:s') . ' count:' . $index .  PHP_EOL;
    }

    /**
     *攀爬机数据同步
     **/
    public function sysc_climber_data($business) {
        $sys_end = time();//当前时间
        $sys_stat = $sys_end- 70;//1分钟10秒前时间，防止数据遗漏，多查10秒数据
        echo date('Y-m-d H:i:s'). ' 数据同步区间'.date('Y-m-d H:i:s', $sys_stat).' - '.date('Y-m-d H:i:s', $sys_end).PHP_EOL;
        $equipment_result_mode = new EquipmentResult();
        $equipment_process_mode = new EquipmentProcess();
        $table_versa_climber_model = new TableVersaClimber();

        $columnss = $table_versa_climber_model->getTableFields();

        $index = 0;//数据循环次数
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在：' . $business . '_' . PHP_EOL;
            return;
        }
//        var_dump($business_set[$business]);die;
        $instanceName = $business_set[$business]['tech-skillrow']['instanceName'];//实例名称
        $tableName = $business_set[$business]['versa_climber']['tableName'];//table
        $tableServer = new TableServer($instanceName, $tableName);
        $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
        $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
        $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';
        $db_mysql = get_db($mysql_database, $mysql_prefix,$mysql_hostname);

        $last_data = [];//上一条数据
        $last_device_data = [];//上一条数据

        $last_data1 = [];//上一条数据
        $last_device_data1 = [];//上一条数据

        //设备信息
//        $equipments = [];
        $stat = $sys_stat;
//        $end = $sys_stat + 10;
        $end = $sys_end;
        $startPK = array (
            array('id', $stat * 1000),
        );
        $endPK = array (
            array('id', $end * 1000),
        );
        //临时设置
//            $startPK = array (
//                array('id', 1721022906401),
//            );
//            $endPK = array (
//                array('id', 1721022906408),
//            );

        $getData = $tableServer->getRange($startPK, $endPK);
        if ($getData['code']) {
            echo date('Y-m-d H:i:s') . ' 获取数据失败' . $getData['message'].PHP_EOL;
        }
        $test_data = $getData['data'];
        //var_dump($test_data);die;
        foreach ($test_data as $key => $value) {
            if (isset($value['columns'])) {
                $columns = json_decode($value['columns'], true);
                unset($test_data[$key]['columns']);
                foreach ($columns as $k => $column) {
                    if (is_array($column)) {
                        $columns[$k] =  !empty($column['value']) ? $column['value'] : '';
                    }
                }
                $json_data = json_decode($columns['data']);
                unset($columns['data']);
                foreach($json_data as $json_data_key => $json_data_value)
                    $columns[$json_data_key] = $json_data_value;
                $test_data[$key] = array_merge($test_data[$key], $columns);
            }
            unset($test_data[$key]['dateStr']);
//            var_dump($test_data);die;
            $data = [];
            $data = $test_data[$key];
            $data['cid'] = $data['id'];
            unset($data['id']);
            unset($data['topic']);
            $data['elapsedTime'] = $data['time'];
            $data['time'] = date('Y-m-d H:i:s', $data['cid'] / 1000);
            $data['date'] = date('Y-m-d', $data['cid'] / 1000);
            //判断字段是否存在
            foreach ($data as $s => $va) {
                if (!in_array($s, $columnss)) {
                    echo date('Y-m-d H:i:s') . 'columns not exist：' . $s . PHP_EOL;
                    unset($data[$s]);
                }
            }
            //当前设备的上一条数据
            $last_table_data = $last_device_data[$data['terminalMac']] = $table_versa_climber_model->inquiryOne(['terminalMac' => $data['terminalMac'],'staff_uuid'=>$data['staff_uuid'],'date'=>$data['date']], ['id' => 'desc']);
            //重复数据处理
            if (!empty($last_table_data) && $data['cid'] <= $last_table_data['cid']) {
                echo date('Y-m-d H:i:s') . ' 重复数据处理'. $data['cid'] . PHP_EOL;
                continue;
            }
//            var_dump($data);die;
            //调试中暂时注释
            $data['id'] = $table_versa_climber_model->add($data);
            if($data['longRange'] == 0)
            {
                echo date('Y-m-d H:i:s') . ' 过滤为空的数据'. $data['cid'] . PHP_EOL;
                continue;
            }
            //当前设备的上一条数据
            $last_table_data1 = $last_device_data1[$data['terminalMac']] = $db_mysql->name('table_versa_climber')->where(['terminalMac' => $data['terminalMac'],'staff_uuid'=>$data['staff_uuid'],'date'=>$data['date']])->order('id desc')->find();;
            //重复数据处理
            if (!empty($last_table_data1) && $data['cid'] <= $last_table_data1['cid']) {
                echo date('Y-m-d H:i:s') . ' 重复数据处理'. $data['cid'] . PHP_EOL;
                continue;
            }
            $data1= $data;
            //调试中暂时注释
            $data1['id'] = $db_mysql->name('table_versa_climber')->insertGetId($data);

            $format_data = $data;
            $format_data['staff_uuid'] = !empty($format_data['staff_uuid']) ? $format_data['staff_uuid'] : '';
            $format_data['device_id'] = !empty($format_data['device_id']) ? $format_data['device_id'] : '';

            /*$equipment = !empty($equipments[$format_data['DeviceID']]) ? $equipments[$format_data['DeviceID']] : null;
            if (empty($equipment)) {
                $equipment = $device_manage_model->inquiryOne(['id' => $format_data['DeviceID']]);
                if (!empty($equipment)) {
                    $equipments[$format_data['DeviceID']] = $equipment;
                }
            }*/
            //是否通过设备mac 获取设备id

            $department_uuid = '';
            // 根据人员uuid获取部门的uuid
            if (!empty($format_data['staff_uuid'])){
                $department_uuid_arr = $db_mysql->name('staff_department')->where(['staff_uuid' => $format_data['staff_uuid']])->order(['create_time' => 'desc'])->field('department_uuid')->find();
                $department_uuid = !empty($department_uuid_arr) ? $department_uuid_arr['department_uuid'] : '';
            }

            $equipment_id = '';
            $equipment_name = '攀爬机';
            if($data['terminalMac'])
            {
                $serial_number = $data['terminalMac'];
                $device_info = $db_mysql->name('device_manage')->where(['serial_number' => $serial_number])->find();
                if($device_info)
                {
                    $equipment_id = $device_info['id'];
                    $equipment_name = $device_info['name'];
                }
            }

            //结果表入库
            $equipment_result = [
                'business' => $business,
                'equipment_id' => $equipment_id,
                'equipment_mark' =>$equipment_name,
                'relation_type' =>'47',
                'department_uuid' => $department_uuid,
                'staff_uuid' => $format_data['staff_uuid'], // 人员标识
                'date' => date('Y-m-d', $format_data['cid'] / 1000), //运动日期
                'record_time' => $format_data['time'],// 运动时间
                'k1' => $format_data['currentFeet'],
                'k2' => $format_data['longRange'],
                'k3' => $format_data['feetMinute'],
                'k4' => $format_data['averageFeet'],
                'k5' => $format_data['heartRate'],
                'k6' => $format_data['energy'],
                'k7' => $format_data['power'],
                'k8' => $format_data['terminalMac'],
                'k9' => $format_data['elapsedTime'],
                'k10' => $format_data['cid'] / 1000

            ];

            $equipment_process = [
                'k1' => $format_data['currentFeet'],
                'k2' => $format_data['longRange'],
                'k3' => $format_data['feetMinute'],
                'k4' => $format_data['averageFeet'],
                'k5' => $format_data['heartRate'],
                'k6' => $format_data['energy'],
                'k7' => $format_data['power'],
                'k8' => $format_data['terminalMac'],
                'k9' => $format_data['elapsedTime'],
                'k10' => $format_data['cid'] / 1000
            ];

            //根据人员、日期、数据包时间判断是添加还是修改
//                if (empty($last_data[$equipment_result['k15']])) {
            $last_where = [
                'k8' => $equipment_result['k8'],
                'date' => $equipment_result['date'],
                'staff_uuid' => $equipment_result['staff_uuid'],
            ];
            $last_data[$equipment_result['k8']] = $equipment_result_mode->inquiryOne($last_where, ['id' => 'desc']);
//                }

//                if (empty($last_data1[$equipment_result['k8']])) {
//                    $last_where = [
//                        'k8' => $equipment_result['k8'],
//                        'date' => $equipment_result['date'],
//                        'staff_uuid' => $equipment_result['staff_uuid'],
//                    ];
            $last_data1[$equipment_result['k8']] =$db_mysql->name('equipment_result')->where($last_where)->order('id desc')->find();;
//                }

            if (empty($last_data1[$equipment_result['k8']]) || (!empty($last_data1[$equipment_result['k8']]) && $last_data1[$equipment_result['k8']]['k9']>$format_data['elapsedTime'])) {
                $equipment_result_id = $db_mysql->name('equipment_result')->insertGetId($equipment_result);
                $equipment_process['equipment_result_id'] = $equipment_result_id;
                $db_mysql->name('equipment_process')->insertGetId($equipment_process);
                $last_data1[$equipment_result['k8']] = $equipment_result;
                QwRobot::pushMsgFormat('攀爬机数据同步成功',$business_set[$business]['name'].':'.'攀爬机数据同步成功');
            }elseif (!empty($last_data1[$equipment_result['k8']])&&$last_data1[$equipment_result['k8']]['k9']<=$format_data['elapsedTime']) {
                $equipment_process['equipment_result_id'] = $last_data1[$equipment_result['k8']]['id'];
                $update = [
                    'k1' => $format_data['currentFeet'],
                    'k2' => $format_data['longRange'],
                    'k3' => $format_data['feetMinute'],
                    'k4' => $format_data['averageFeet'],
                    'k5' => $format_data['heartRate'],
                    'k6' => $format_data['energy'],
                    'k7' => $format_data['power'],
                    'k8' => $format_data['terminalMac'],
                    'k9' => $format_data['elapsedTime'],
                    'k10' => $format_data['cid'] / 1000
                ];
                $db_mysql->name('equipment_result')->where('id', $last_data1[$equipment_result['k8']]['id'])->update($update);
                $db_mysql->name('equipment_process')->insertGetId($equipment_process);
            }

            if (empty($last_data[$equipment_result['k8']]) || (!empty($last_data[$equipment_result['k8']]) && $last_data[$equipment_result['k8']]['k9']>$format_data['elapsedTime'])) {
                $equipment_result_id = $equipment_result_mode->add($equipment_result);
                $equipment_process['equipment_result_id'] = $equipment_result_id;
                $equipment_process_mode->add($equipment_process);
                $last_data[$equipment_result['k8']] = $equipment_result;
            } elseif (!empty($last_data[$equipment_result['k8']]) && $last_data[$equipment_result['k8']]['k9']<$format_data['elapsedTime']) {
//                    var_dump($last_data[$equipment_result['k8']]);
                $equipment_process['equipment_result_id'] = $last_data[$equipment_result['k8']]['id'];
                $update = [
                    'k1' => $format_data['currentFeet'],
                    'k2' => $format_data['longRange'],
                    'k3' => $format_data['feetMinute'],
                    'k4' => $format_data['averageFeet'],
                    'k5' => $format_data['heartRate'],
                    'k6' => $format_data['energy'],
                    'k7' => $format_data['power'],
                    'k8' => $format_data['terminalMac'],
                    'k9' => $format_data['elapsedTime'],
                    'k10' => $format_data['cid'] / 1000
                ];
                $equipment_result_mode->modifyById($last_data[$equipment_result['k8']]['id'], $update);
                $equipment_process_mode->add($equipment_process);
            }
            $index ++;
        }
        echo date('Y-m-d H:i:s') . ' count:' . $index .  PHP_EOL;
    }

    //初始化数据脚本-wattbike
    public function init_wattbike_data($business) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在：' . $business . '_' . PHP_EOL;
            return;
        }
        $table_wattbike_model = new TableWattbike();
        $equipment_result_model = new EquipmentResult();
        $equipment_process_model = new EquipmentProcess();
        $columnss = $table_wattbike_model->getTableFields();
        $time_index = 0;//时间循环次数
        $sys_stat = strtotime('2024-11-14 17:03:00');
        $sys_end = strtotime('2024-11-14 17:04:02');
        $instanceName = $business_config['wattbike']['instanceName'];//实例名称
        $tableName = $business_config['wattbike']['tableName'];//table
        $tableServer = new TableServer($instanceName, $tableName);

        $last_data = [];//上一条数据
        $last_data1 = [];//上一条数据

        //设备信息
//        $equipments = [];

        while (true) {
            $stat = $sys_stat;
            $end = $sys_stat + 10;
            $startPK = array (
                array('id', ''.$stat * 1000),
                array('DeviceID', null, 'INF_MIN'),
            );
            $endPK = array (
                array('id', ''.$end * 1000),
                array('DeviceID', null, 'INF_MAX'),
            );
            $getData = $tableServer->getRange($startPK, $endPK);
            if ($getData['code']) {
                echo date('Y-m-d H:i:s') . ' 获取数据失败' . $getData['message'];
                break;
            }
            $test_data = $getData['data'];
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                echo date('Y-m-d H:i:s'). ' 用户不存在：' . $business . '_' . PHP_EOL;
                return;
            }
            $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
            $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
            $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';
            $db_mysql = get_db($mysql_database, $mysql_prefix,$mysql_hostname);

            foreach ($test_data as $key => $value) {
                if (isset($value['columns'])) {
                    $columns = json_decode($value['columns'], true);
                    unset($test_data[$key]['columns']);
                    $checkFailedData = $columns['checkFailedData'];
                    unset($columns['checkFailedData']);
                    $columns = array_merge($columns, $checkFailedData);
                    foreach ($columns as $k => $column) {
                        if (is_array($column)) {
                            $columns[$k] =  !empty($column['value']) ? $column['value'] : '';
                        }
                    }
                    $test_data[$key] = array_merge($test_data[$key], $columns);
                }
                $data = [];
                $data = $test_data[$key];
                $data['cid'] = $data['id'];
                unset($data['id']);
                $data['time'] = date('Y-m-d H:i:s', $data['cid'] / 1000);
                $data['ctime'] = date('Y-m-d H:i:s');
                //判断字段是否存在
                foreach ($data as $s => $va) {
                    if (!in_array($s, $columnss)) {
                        echo date('Y-m-d H:i:s') . 'columns not exist：' . $s . PHP_EOL;
                        unset($data[$s]);
                    }
                }
                $data['id'] = $table_wattbike_model->add($data);

                $format_data = $data;
                $format_data['staff_uuid'] = !empty($format_data['staff_uuid']) ? $format_data['staff_uuid'] : '';
                $format_data['DeviceID'] = !empty($format_data['DeviceID']) ? $format_data['DeviceID'] : '';
                $format_data['Power'] = !empty($format_data['Power']) ? $format_data['Power'] : '';
                $format_data['Cadence'] = !empty($format_data['Cadence']) ? $format_data['Cadence'] : '';
                $format_data['status'] = !empty($format_data['status']) ? $format_data['status'] : 0;//0=开始1结束

                /*$equipment = !empty($equipments[$format_data['DeviceID']]) ? $equipments[$format_data['DeviceID']] : null;
                if (empty($equipment)) {
                    $equipment = $device_manage_model->inquiryOne(['id' => $format_data['DeviceID']]);
                    if (!empty($equipment)) {
                        $equipments[$format_data['DeviceID']] = $equipment;
                    }
                }*/
                $department_uuid = '';
// 根据人员uuid获取部门的uuid
                if (!empty($format_data['staff_uuid'])){
                    $department_uuid_arr = $db_mysql->name('staff_department')->where(['staff_uuid' => $format_data['staff_uuid']])->order(['create_time' => 'desc'])->field('department_uuid')->find();
                    $department_uuid = !empty($department_uuid_arr) ? $department_uuid_arr['department_uuid'] : '';
                }

                //数据入库
//                $equipment_wattbike = [
//                    'uuid' => '',
//                    'date' => date('Y-m-d', $format_data['cid'] / 1000), //运动日期
//                    'staff_uuid' => $format_data['staff_uuid'], // 人员标识
//                    'department_uuid' => $department_uuid,
//                    'device_id' => $format_data['DeviceID'], //设备id
////                    'equipment_uuid' => !empty($equipment['uuid']) ? $equipment['uuid'] : '',
//                    'record_time' => $format_data['time'],// 运动时间
//                    'action_name' => '',//todo // 动作名称
//                    'power' => $format_data['Power'],// 功率
//                    'cadence' => $format_data['Cadence'],
//                    'format_data_id' => $format_data['id'],
//                    'is_del' => 0,
//                    'create_time' => date('Y-m-d H:i:s'),
//                    'create_by' => 'cron',
//                    'update_time' => date('Y-m-d H:i:s'),
//                ];
                //结果表入库
                $equipment_result = [
                    'business' => $business,
                    'equipment_id' =>$format_data['DeviceID'],
                    'department_uuid' => $department_uuid,
                    'staff_uuid' => $format_data['staff_uuid'], // 人员标识
                    'date' => date('Y-m-d', $format_data['cid'] / 1000), //运动日期
                    'record_time' => $format_data['time'],// 运动时间
                    'k1' => $format_data['Power'],// 功率       K1
                    'k2' => $format_data['Cadence'],//       K2
                    'k3' => $format_data['id']//     K3
                ];

//                $equipment_wattbike_details = [
//                    'data_uuid' => $equipment_wattbike['uuid'],
//                    'date' => $equipment_wattbike['date'],
//                    'staff_uuid' => $equipment_wattbike['staff_uuid'],
//                    'device_id' => $equipment_wattbike['device_id'],
////                    'equipment_uuid' => $equipment_wattbike['equipment_uuid'],
//                    'record_time' => $equipment_wattbike['record_time'],
//                    'action_name' => $equipment_wattbike['action_name'],
//                    'power' => $format_data['Power'],
//                    'cadence' => $format_data['Cadence'],
//                    'is_del' => 0,
//                    'create_time' => date('Y-m-d H:i:s'),
//                    'create_by' => 'cron',
//                ];

                //过程表入库
                $equipment_process = [
                    'k1'=>$format_data['time'],
                    'k2'=>$format_data['Power'],
                    'k3'=>$format_data['Cadence'],
                ];

                if (empty($last_data[$equipment_result['equipment_id']])) {
                    $equipment_result['id'] = $equipment_result_model->add($equipment_result);
                    $equipment_process['equipment_result_id'] = $equipment_result['id'];
                    $equipment_process_model->add($equipment_process);
                    $last_data[$equipment_result['equipment_id']] = $equipment_result;
                } else {
                    $update = [
                        'k1' => $format_data['Power'],
                        'k2' => $format_data['Cadence'],
                    ];
                    $equipment_result_model->modifyById($last_data[$equipment_result['equipment_id']]['id'], $update);
                    $equipment_process['equipment_result_id'] = $last_data[$equipment_result['equipment_id']]['id'];
                    $equipment_process_model->add($equipment_process);
                }

                if (empty($last_data1[$equipment_result['equipment_id']])) {
                    $equipment_result['id'] = $db_mysql->name('equipment_result')->insertGetId($equipment_result);
                    $equipment_process['equipment_result_id'] = $equipment_result['id'];
                    $db_mysql->name('equipment_process')->insertGetId($equipment_process);
                    $last_data1[$equipment_result['equipment_id']] = $equipment_result;
                } else {
                    $update = [
                        'k1' => $format_data['Power'],
                        'k2' => $format_data['Cadence'],
                    ];
                    $db_mysql->name('equipment_result')->where('id', $last_data1[$equipment_result['equipment_id']]['id'])->update($update);
                    $equipment_process['equipment_result_id'] = $last_data1[$equipment_result['equipment_id']]['id'];
                    $db_mysql->name('equipment_process')->insertGetId($equipment_process);
                }


            }
            $sys_stat = $end;
            if ($sys_stat >= $sys_end || $sys_stat > time()) {
                break;
            }
            $time_index ++;
            echo date('Y-m-d H:i:s') . ' time_index:' . $time_index . date('Y-m-d H:i:s', $sys_stat) .  PHP_EOL;
        }
    }

    //定时同步脚本-wattbike
    public function sysc_wattbike_data($business) {
        $sys_end = time();//当前时间
        $sys_stat = $sys_end- 70;//1分钟10秒前时间，防止数据遗漏，多查10秒数据
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在：' . $business . '_' . PHP_EOL;
            return;
        }
        echo date('Y-m-d H:i:s'). ' 数据同步区间'.date('Y-m-d H:i:s', $sys_stat).' - '.date('Y-m-d H:i:s', $sys_end).PHP_EOL;

        $equipment_result_mode = new EquipmentResult();
        $equipment_process_mode = new EquipmentProcess();
        $table_wattbike_model = new TableWattbike();

        $columnss = $table_wattbike_model->getTableFields();

        $index = 0;//数据循环次数
        $time_index = 0;//时间循环次数

        $instanceName = $business_config['wattbike']['instanceName'];//实例名称   new-wattbike-sx
        $tableName = $business_config['wattbike']['tableName'];//table    pro_table
        $tableServer = new TableServer($instanceName, $tableName);

        $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
        $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
        $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';
        $db_mysql = get_db($mysql_database, $mysql_prefix,$mysql_hostname);

        $last_data = [];//上一条数据
        $last_device_data = [];//上一条数据

        //设备信息
//        $equipments = [];

        //人员信息
        $staff_departments = [];

        //用户结果表
        $user_equipment_results = [];

        while (true) {
            $stat = $sys_stat;
            $end = $sys_stat + 10;
            $startPK = array (
                array('id', ''.$stat * 1000),
                array('DeviceID', null, 'INF_MIN'),
            );
            $endPK = array (
                array('id', ''.$end * 1000),
                array('DeviceID', null, 'INF_MAX'),
            );
            //获取固定时间段的数据  todo
//            $startPK = array (
//                array('id', '1731641106657'),
//                array('DeviceID', null, 'INF_MIN'),
//            );
//            $endPK = array (
//                array('id', '1731641306657'),
//                array('DeviceID', null, 'INF_MAX'),
//            );
            $getData = $tableServer->getRange($startPK, $endPK);
            if ($getData['code'] != 0 || empty($getData['data'])) {
                echo date('Y-m-d H:i:s') . ' 获取数据失败：' . $getData['message'].PHP_EOL;
                $sys_stat = $end;
                if ($sys_stat >= $sys_end || $sys_stat > time()) {
                    break;
                }
                continue;
            }
            $test_data = $getData['data'];
            foreach ($test_data as $key => $value) {
                if (isset($value['columns'])) {
                    $columns = json_decode($value['columns'], true);
                    unset($test_data[$key]['columns']);
                    $checkFailedData = $columns['checkFailedData'];
                    unset($columns['checkFailedData']);
                    $columns = array_merge($columns, $checkFailedData);
                    foreach ($columns as $k => $column) {
                        if (is_array($column)) {
                            $columns[$k] =  isset($column['value']) ? $column['value'] : '';
                        }
                    }
                    $test_data[$key] = array_merge($test_data[$key], $columns);
                }
                $data = [];
                $data = $test_data[$key];
                $data['cid'] = $data['id'];
                unset($data['id']);
                $data['time'] = date('Y-m-d H:i:s', $data['cid'] / 1000);
                $data['ctime'] = date('Y-m-d H:i:s');
                $data['business'] = $business;
                $data['staff_uuid'] = trim($data['staff_uuid']);
                $data['DeviceID'] = trim($data['DeviceID']);
                //判断字段是否存在
                foreach ($data as $s => $va) {
                    if (!in_array($s, $columnss)) {
                        echo date('Y-m-d H:i:s') . 'columns not exist：' . $s . PHP_EOL;
                        unset($data[$s]);
                    }
                }
                //当前设备的上一条数据
                $last_table_data = !empty($last_device_data[$data['DeviceID']]) ? $last_device_data[$data['DeviceID']] : null;
                if (empty($last_table_data)) {
                    $last_table_data = $last_device_data[$data['DeviceID']] = $table_wattbike_model->inquiryOne(['DeviceID' => $data['DeviceID'],'business'=>$business], ['id' => 'desc']);
                }
                //重复数据处理
                if (!empty($last_table_data) && $data['cid'] <= $last_table_data['cid']) {
                    echo date('Y-m-d H:i:s') . ' 重复数据处理'. $data['cid'] . PHP_EOL;
                    continue;
                }
                $db_mysql->name('table_wattbike')->insertGetId($data);
                $data['id'] = $table_wattbike_model->add($data);
                $format_data = $data;
                $format_data['staff_uuid'] = !empty($format_data['staff_uuid']) ? $format_data['staff_uuid'] : '';
                $format_data['DeviceID'] = !empty($format_data['DeviceID']) ? $format_data['DeviceID'] : '';
                $format_data['Power'] = !empty($format_data['Power']) ? $format_data['Power'] : '';
                $format_data['Cadence'] = !empty($format_data['Cadence']) ? $format_data['Cadence'] : '';
                $format_data['status'] = isset($format_data['status']) ? $format_data['status'] : 1;//0开始1过程2结束

//                $equipment = !empty($equipments[$format_data['DeviceID']]) ? $equipments[$format_data['DeviceID']] : null;
//                if (empty($equipment)) {
//                    //根据设备实际ID获取设备
//                    $equipment = $equipment_hardware_model->inquiryOne(['device_id' => $format_data['DeviceID']]);
//                    if (!empty($equipment)) {
//                        $equipments[$format_data['DeviceID']] = $equipment;
//                    }
//                }

                $staff_department = !empty($staff_departments[$format_data['staff_uuid']]) ? $staff_departments[$format_data['staff_uuid']] : null;
                if (empty($staff_department)) {
                    //根据人员uuid获取部门的uuid
                    $staff_department = $db_mysql->name('staff_department')->where(['staff_uuid' => $format_data['staff_uuid']])->order('id desc')->find();
                    if (!empty($staff_department)) {
                        $staff_departments[$format_data['staff_uuid']] = $staff_department;
                    }
                }
                $department_uuid = !empty($staff_department['department_uuid']) ? $staff_department['department_uuid'] : '';
                $equipment_id = '';
                if($format_data['DeviceID'])
                {
                    $serial_number = $format_data['DeviceID'];
                    $device_info = $db_mysql->name('device_manage')->where(['serial_number' => $serial_number])->find();
                    if($device_info)
                    {
                        $equipment_id = $device_info['id'];
                    }
                }

                //结果表入库
                $equipment_result = [
                    'business' => $business,
                    'equipment_id' =>$equipment_id,
                    'department_uuid' => $department_uuid,
                    'relation_type' => 11,
                    'staff_uuid' => $format_data['staff_uuid'], // 人员标识
                    'date' => date('Y-m-d', $format_data['cid'] / 1000), //运动日期
                    'record_time' => $format_data['time'],// 运动时间
                    'k1' => $format_data['Power'],// 功率       K1
                    'k2' => $format_data['Cadence'],//       K2
                    'k3' => $format_data['id']//     K3
                ];

                $equipment_process = [
                    'k1' => $format_data['time'],
                    'k2' => $format_data['Power'],
                    'k3' => $format_data['Cadence'],
                ];

                //开始结束逻辑 status 0开始1过程2结束
                if (empty($last_data[$equipment_result['equipment_id']])) {
                    $last_where = [
                        'relation_type' => 11,
                        'business' => $business,
                        'equipment_id' => $equipment_result['equipment_id'],
                        'staff_uuid' => $equipment_result['staff_uuid'],
                        'date' => $equipment_result['date'],
                        'is_del' => 0
                    ];
                    $last_data[$equipment_result['equipment_id']] = $equipment_result_mode->inquiryOne($last_where, ['id' => 'desc']);
                }
                if (empty($last_data[$equipment_result['equipment_id']]) || $format_data['status'] == 0) {
                    $mac_last_id = $equipment_result_mode->add($equipment_result);
                    if ($mac_last_id){
                        QwRobot::pushMsgFormat('Wattbike数据同步成功',$business_set[$business]['name'].':'.'Wattbike数据同步成功');
                    }
                    //插入用户表
                    $equipment_result['reference']  = $mac_last_id;
                    $last_id = $db_mysql->name('equipment_result')->insertGetId($equipment_result);
                    $equipment_result['id'] = $mac_last_id;
                    $last_data[$equipment_result['equipment_id']] = $equipment_result;
                } else if ( $format_data['status'] == 1) {
                    $mac_last_id = $last_data[$equipment_result['equipment_id']]['id'];
                    $update = [
                        'k1' => $format_data['Power'],
                        'k2' => $format_data['Cadence'],
                    ];
                    $equipment_result_mode->modifyById($mac_last_id, $update);
                    $equipment_process['equipment_result_id'] = $mac_last_id;
                    $equipment_process_mode->add($equipment_process);
                    //插入用户表
                    $user_equipment_result = [];
                    if (empty($user_equipment_results[$mac_last_id])) {
                        $user_equipment_result = $db_mysql->name('equipment_result')->where(['reference' => $mac_last_id])->find();
                        $user_equipment_results[$mac_last_id] = $user_equipment_result;
                    }
                    if (!empty($user_equipment_result)) {
                        $last_id = $user_equipment_result['id'];
                        $db_mysql->name('equipment_result')->where("id", $last_id)->update($update);
                        $equipment_process['equipment_result_id'] = $last_id;
                        $db_mysql->name('equipment_process')->insertGetId($equipment_process);
                    }
                }
                $index ++;
            }
            $sys_stat = $end;
            if ($sys_stat >= $sys_end || $sys_stat > time()) {
                break;
            }
            $time_index ++;
        }
        echo date('Y-m-d H:i:s') . ' count:' . $index .  PHP_EOL;
    }
}
