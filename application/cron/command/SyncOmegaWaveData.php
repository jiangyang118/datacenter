<?php

namespace app\cron\command;

use app\model\EquipmentRelation as EquipmentRelationModel;
use app\model\EquipmentResult as EquipmentResultModel;
use app\model\EquipmentResultExtend as EquipmentResultExtendModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
class SyncOmegaWaveData extends Command {
    private $equipmentMark = 'OmegaWave';
    private $resultRelationType = 40;
    protected function configure() {
        $this->setName('SyncOmegaWaveData')->setDescription('this api is for SyncOmegaWaveData')
            ->addArgument('business');//商户唯一标识;
    }
    
    protected function execute(Input $input, Output $output) {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $business = '';
        try {
            $business = $input->getArgument('business');
            $dirpath = $this->getEquipmentDataFilePath($business);
            
            //过滤文件
            $file_arr = [];
            if(empty($dirpath['wait'])) {
                throw new Exception("OmegaWave原始数据目录未配置，请正确配置");
            }
            if(!is_dir($dirpath['wait'])) {
                throw new Exception("OmegaWave原始数据目录不存在");
            }
            if ($handle = opendir($dirpath['wait'])) {
                while (($file = readdir($handle)) !== false) {
                    if ($file == '.' || $file == '..' || $file == '_gsdata_') {
                        continue;
                    }
                    $file_extension = pathinfo($file, PATHINFO_EXTENSION);
    //                echo $file_extension."\n";
                    if(in_array(strtolower($file_extension), ['csv'])) {
                        $file_arr[] = $dirpath['wait'].DS.$file;
                    }
                }
                closedir($handle);
            }
            if($file_arr) {
                $this->dealFileData($file_arr, $business);
            }
            
        } catch(Exception $e) {
//            $msg = [
//                'device' => $this->equipmentMark,
//                'business' => $business,
//                'message' => $e->getMessage()
//            ];
//            $this->sendMsg($business, $msg, "数据同步");
        }
        return true;
    }
    
    public function moveFile($file, $business) {
        $targetdir = $this->getEquipmentDataFilePath($business);
        if(!empty($targetdir['finish'])) {
            $finish_dir =  $targetdir['finish'] . DS . $business . DS . date("Y-m-d") . DS . 'omegawave';
            !is_dir($finish_dir) AND mkdir($finish_dir, 0755, true);
            $file_name = pathinfo($file, PATHINFO_BASENAME);
            rename($file, $finish_dir . DS . $file_name);
        }
        return true;
    }
    
    /**
     * 处理文件
     * @param type $file_arr
     * @param type $business
     */
    public function dealFileData($file_arr, $business) {
        $business_db = '';
        try {
            $datetime = date("Y-m-d H:i:s");
            $create_by = "syncFile";
            $equipmentRelationModel = new EquipmentRelationModel();
            $equipmentResultModel = new EquipmentResultModel();
            $equipmentResultExtendModel = new EquipmentResultExtendModel();
            //业务系统数据连接
            $business_db = $this->getBusinessDb($business);
            //设备系统编号
            $equipment_sys_number = $this->getEquipmentSysNumber($business);
            $equipment = $business_db->name("equipment")->where(['system_number'=>$equipment_sys_number, 'is_del'=>0])->find();
            if(empty($equipment)) {
                throw new Exception("设备不存在");
            }
            //结果数据映射
            $resultRelations = $equipmentRelationModel->inquiryAll(['type'=> $this->resultRelationType, 'is_del'=>0], [], ['k','v','unit']);
            $resultKVS = [];
            foreach($resultRelations as $k=>$v) {
                $v['index'] = trim($v['v'], 'k');
                $resultKVS[$v['k']] = $v;
            }
            
            $staffs = [];
            foreach($file_arr as $file) {
                if(!file_exists($file)) {
                    continue;
                }
                $filemtime = filemtime($file);
                //同名文件，查看是否修改过，没修改的不予处理
                $fileResult = $equipmentResultModel->inquiryOne(['equipment_id'=>$equipment['id'],'business'=>$business,'equipment_mark'=>$this->equipmentMark,'relation_type'=>$this->resultRelationType,'filename'=>$file,'is_del'=>0], ['filemtime'=>'desc'], ['id','filemtime']);
                if($fileResult && $fileResult['filemtime'] >= $filemtime) {
                    continue;
                }
                
                // 打开CSV文件
                $csvFile = fopen($file, 'r');
                if(!$csvFile) {
                    continue;
                }
                
                $allResultArr = [];
                $allResultExtendArr = [];
                // 读取CSV文件
                $tableHeads = [];
                $lineIndex = 0;
                //不存在的人名
                $notExists = [];
                while (($line = fgetcsv($csvFile)) !== false) {//一行就是一个完整记录
                    //表头
                    if($lineIndex++ == 0) {
                        $tableHeads = $line;
                        continue;
                    }
                    //过滤无效数据
                    $CalculationStatusIndex = array_search("CalculationStatus", $tableHeads);
                    if(empty($line[$CalculationStatusIndex]) || (strtolower($line[$CalculationStatusIndex]) != 'ok')) {
                        continue;
                    }
                    //人员及部门处理
                    $FirstNameIndex = array_search("FirstName", $tableHeads);
                    $LastNameIndex = array_search("LastName", $tableHeads);
                    $FirstName = empty($line[$FirstNameIndex]) ? '' : trim($line[$FirstNameIndex]);
                    $LastName = empty($line[$LastNameIndex]) ? '' : trim($line[$LastNameIndex]);
//                    $staff_name = $FirstName.$LastName;
                    $staff_name = $LastName;
                    //$staff_name = mb_convert_encoding($staff_name, 'UTF-8', 'GBK');
                    if(!array_key_exists($staff_name, $staffs)) {
                        $staff = $business_db->name("staff")->where(['name'=>$staff_name,'is_show'=>1])->find();
                        if(empty($staff)) {
                            $notExists[$staff_name] = [
                                'name' => $staff_name,
                                'equipment' => $this->equipmentMark,
                                'msg' => "第{$lineIndex}行,受测人员【{$staff_name}】在数据中心不存在，请先在数据中心创建",
                                'create_time' => $datetime,
                                'create_by' => $create_by
                            ];
                            continue;
                        }
                        $staffs[$staff_name] = [
                            'staff_uuid' => $staff['uuid'],
                            'staff_height' => $staff['height'],
                            'staff_weight' => $staff['weight'],
                            'staff_age' => get_age($staff['birthday'])
                        ];
                        //部门处理
                        //受测人员所属部门
                        $department = $business_db->name("department")
                                ->alias("a")
                                ->join("staff_department b", 'a.uuid = b.department_uuid', 'left')
                                ->where(['b.staff_uuid'=>$staff['uuid'], 'a.is_show'=>1, 'a.status'=>1])
                                ->field(['a.uuid'])
                                ->find();
                        if(!empty($department['uuid'])) {
                            $staffs[$staff_name]['department_uuid'] = $department['uuid'];
                        }
                    }
                    
                    //结果数据表
                    $resultArrTmp = [
                        'business' => $business,
                        'equipment_id' => $equipment['id'],
                        'equipment_mark' => $this->equipmentMark,
                        'relation_type' => $this->resultRelationType,
                        'department_uuid' => empty($staffs[$staff_name]['department_uuid']) ? '' : $staffs[$staff_name]['department_uuid'],
                        'staff_uuid' => empty($staffs[$staff_name]['staff_uuid']) ? '' : $staffs[$staff_name]['staff_uuid'],
                        'staff_height' => empty($staffs[$staff_name]['staff_height']) ? '' : $staffs[$staff_name]['staff_height'],
                        'staff_weight' => empty($staffs[$staff_name]['staff_weight']) ? '' : $staffs[$staff_name]['staff_weight'],
                        'staff_age' => empty($staffs[$staff_name]['staff_age']) ? '' : $staffs[$staff_name]['staff_age'],
                        'filename' => $file,
                        'filemtime' => $filemtime,
                        'create_by' => $create_by
                    ];
                    $resultExtendArrTmp = [];
                    foreach($resultKVS as $k=>$v) {
                        if(in_array($k, $tableHeads)) {
                            $tableHeadIndex = array_search($k, $tableHeads);
                            $value = !isset($line[$tableHeadIndex]) ? '' : trim($line[$tableHeadIndex]);
                            $resultOrExtendKey = $v['v'];
                            if($v['index'] <= 20) {
                                $resultArrTmp[$resultOrExtendKey] = $value;
                            } else {
                                $resultExtendArrTmp[$resultOrExtendKey] = $value;
                            }
                            
                            if($k == 'AssessmentDateTimeLocalTime') {
                                $resultArrTmp['date'] = date("Y-m-d", strtotime($value));
                                $resultArrTmp['record_time'] = date("Y-m-d H:i:s", strtotime($value));
                            }
                        }
                    } 
                    
                    $system_uuid = guid();
                    $allResultArr[$system_uuid] = $resultArrTmp;
                    if($resultExtendArrTmp) {
                        $allResultExtendArr[$system_uuid] = $resultExtendArrTmp;
                    }
                }

                // 关闭文件句柄
                fclose($csvFile);
//                判断不存在的人员
                if(!empty($notExists)) {
                    $business_db->name("equipment_sync")->insertAll($notExists, true);
                    $notExistStaffNames = implode(",", array_column($notExists, "name"));
                    $msg = "受测人员【{$notExistStaffNames}】在数据中心不存在，请先在数据中心创建";
                    $sendmsg = [
                        'device' => $this->equipmentMark,
                        'business' => $business,
                        'message' => $msg
                    ];
//                    $this->sendMsg($business, $sendmsg, "数据同步");
                    continue;
                }
                
                //数据中心插入数据
                foreach($allResultArr as $resultKey=>$resultArr) {
                    $resultId = $equipmentResultModel->add($resultArr);
                    if(!$resultId) {
                        throw new Exception("数据中心存储数据失败");
                    }
                    $allResultArr[$resultKey]['reference'] = $resultId;
                    if(!empty($allResultExtendArr[$resultKey])) {
                        $allResultExtendArr[$resultKey]['equipment_result_id'] = $resultId;
                        $allResultExtendArr[$resultKey]['create_by'] = $create_by;
                        $equipmentResultExtendModel->add($allResultExtendArr[$resultKey]);
                    }
                }
                
                //业务子系统
                $business_db->startTrans();
                
                foreach($allResultArr as $resultKey=>$resultArr) {
                    //查找是否已有此数据
//                    $businessResult = $business_db->name("equipment_result")
//                        ->where(['k1'=>$resultArr['k1'],'equipment_id'=>$equipment['id'],'business'=>$business,'equipment_mark'=>$this->equipmentMark,'relation_type'=>$this->resultRelationType,'date'=>$resultArr['date'],'staff_uuid'=>$resultArr['staff_uuid'],'is_del'=>0])
//                        ->field('id,relation_type,staff_uuid')
//                        ->find();
//                    //处理旧数据
//                    if($businessResult) {
//                        $businessResultDel = $business_db->name("equipment_result")->where(['id'=>$businessResult['id']])->update(['is_del'=>1]);
//                        if($businessResultDel === false) {
//                            throw new Exception("覆盖旧数据失败");
//                        }
//                        $businessResultExtendDel = $business_db->name("equipment_result_extend")->where(['equipment_result_id'=>$businessResult['id']])->update(['is_del'=>1]);
//                        if($businessResultExtendDel === false) {
//                            throw new Exception("覆盖旧数据失败.");
//                        }
//
//                    }
                    
                    //插入新数据
                    $businessResultId = $business_db->name("equipment_result")->insertGetId($resultArr);
                    if(!$businessResultId) {
                        throw new Exception("数据存储失败");
                    }
                    if(!empty($allResultExtendArr[$resultKey])) {
                        $allResultExtendArr[$resultKey]['equipment_result_id'] = $businessResultId;
                        $businessResultExtendId = $business_db->name("equipment_result_extend")->insertGetId($allResultExtendArr[$resultKey]);
                        if(!$businessResultExtendId) {
                            throw new Exception("数据存储失败.");
                        }
                    }
                }
                
                $business_db->commit();
                
                $msg = [
                    'device' => $this->equipmentMark,
                    'business' => $business,
                    'recordCount' => count($allResultArr),
                    'message' => '同步成功'
                ];
                $this->sendMsg($business, $msg, "数据同步");
                
                //处理完的文件，移动到其他地方
                $this->moveFile($file, $business);
            }
            
        } catch(Exception $e) {
            $business_db->rollback();
//            $msg = [
//                'device' => $this->equipmentMark,
//                'business' => $business,
//                'message' => $e->getMessage()
//            ];
//            $this->sendMsg($business, $msg, "数据同步");
        }
        
        return true;
    }
    
    /**
     * 获取设备系统编号
     */
    public function getEquipmentSysNumber($business) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            throw new Exception('商户配置错误');
        }
        if(empty($business_config['equipment_system_numbers'])) {
            throw new Exception("商户配置错误.");
        }
        if(!array_key_exists($this->equipmentMark, $business_config['equipment_system_numbers'])) {
            throw new Exception("系统设备配置错误");
        }
        return $business_config['equipment_system_numbers'][$this->equipmentMark];
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
    
    /**
     * 获取设备原始数据文件存放的路径
     * @param type $business
     * @return type
     * @throws Exception
     */
    public function getEquipmentDataFilePath($business) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            throw new Exception('商户配置错误');
        }
        if(empty($business_config['equipment_data_filepath'])) {
            throw new Exception("商户配置错误.");
        }
        if(!array_key_exists($this->equipmentMark, $business_config['equipment_data_filepath'])) {
            throw new Exception("设备数据文件路径配置错误");
        }
        return $business_config['equipment_data_filepath'][$this->equipmentMark];
    }
}
