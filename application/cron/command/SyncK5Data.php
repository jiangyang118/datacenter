<?php

namespace app\cron\command;

use app\model\EquipmentRelation as EquipmentRelationModel;
use app\model\EquipmentResult as EquipmentResultModel;
use app\model\EquipmentResultExtend as EquipmentResultExtendModel;
use app\model\EquipmentProcess as EquipmentProcessModel;
use app\model\EquipmentProcessExtend as EquipmentProcessExtendModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
class SyncK5Data extends Command {
    private $equipmentMark = 'CosmedK5';
    private $resultRelationType = 24;
    private $processRelationType = 25;
    protected function configure() {
        $this->setName('SyncK5Data')->setDescription('this api is for SyncK5Data')
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
                throw new Exception("k5原始数据目录未配置，请正确配置");
            }
            if(!is_dir($dirpath['wait'])) {
                throw new Exception("k5原始数据目录不存在");
            }
            if ($handle = opendir($dirpath['wait'])) {
                while (($file = readdir($handle)) !== false) {
                    if ($file == '.' || $file == '..' || $file == '_gsdata_') {
                        continue;
                    }
                    $file_extension = pathinfo($file, PATHINFO_EXTENSION);
    //                echo $file_extension."\n";
                    if(in_array(strtolower($file_extension), ['xlsx', 'xls'])) {
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
            $finish_dir =  $targetdir['finish'] . DS . $business . DS . date("Y-m-d") . DS . 'k5';
            !is_dir($finish_dir) AND mkdir($finish_dir, 0755, true);
            $file_name = pathinfo($file, PATHINFO_BASENAME);
            rename($file, $finish_dir . DS . $file_name);
        }
        return true;
    }
    
    /**
     * 暂时约定人员编号与设备ID2字段对应
     * 逐个处理原始数据文件
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
            $equipmentProcessModel = new EquipmentProcessModel();
            $equipmentProcessExtendModel = new EquipmentProcessExtendModel();
            //业务系统数据连接
            $business_db = $this->getBusinessDb($business);
            //设备系统编号
            $equipment_sys_number = $this->getEquipmentSysNumber($business);
            $equipment = [];
            if('shjxkx' == $business) {
                $equipment = $business_db->name("device_manage")->where(['id'=>$equipment_sys_number, 'is_show'=>1])->find();
            } else {
                $equipment = $business_db->name("equipment")->where(['system_number'=>$equipment_sys_number, 'is_del'=>0])->find();
            }
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
            //过程数据映射
            $processRelations = $equipmentRelationModel->inquiryAll(['type'=> $this->processRelationType, 'is_del'=>0], [], ['k','v','unit']);
            $processKVS = [];
            foreach($processRelations as $k=>$v) {
                $v['index'] = trim($v['v'], 'k');
                $processKVS[$v['k']] = $v;
            }
            
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
                
                $file_extension = pathinfo($file, PATHINFO_EXTENSION);
                if ($file_extension == 'xlsx') {
                    $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
                } else {
                    $objReader = \PHPExcel_IOFactory::createReader('Excel5');
                }
                $obj_PHPExcel = $objReader->load($file);
                //过程数据
                $excelProcess = $obj_PHPExcel->getsheet(0)->toArray();
                //结果数据
                $excelResult = $obj_PHPExcel->getsheet(1)->toArray();

                //结果数据表
                $resultArr = [
                    'business' => $business,
                    'equipment_id' => $equipment['id'],
                    'equipment_mark' => $this->equipmentMark,
                    'relation_type' => $this->resultRelationType,
                    'department_uuid' => '',
                    'staff_uuid' => '',
                    'date' => '',
                    'record_time' => '',
                    'filename' => $file,
                    'filemtime' => $filemtime,
                    'create_by' => $create_by
                ];
                
                foreach($excelResult as $line) {
                    $k = $line[0];
                    $v = empty($line[7]) ? '' : $line[7];
                    if(array_key_exists($line[0], $resultKVS)) {
                        $resultOrExtendKey = $resultKVS[$k]['v'];
                        if($resultKVS[$k]['index'] <= 20) {
                            $resultArr[$resultOrExtendKey] = $v;
                        } else {
                            $resultExtendArr[$resultOrExtendKey] = $v;
                        }
                    }
                }
                
                //过程数据表中有结果数据需要的字段
//                $personnel_umber = '';
                $personnel_name = '';
                $test_time = '';
                $tableHeads = [];
                foreach($excelProcess as $line_index=>$line) {
                    if($line_index > 11) {
                        break;
                    }
                    //结果数据中需要过程表的字段
                    for($i=0; $i<10; $i++) {
                        switch ($line[$i]) {
//                            case 'ID2'://人员编号（设备软件需要为人员添加这一列）
//                                $personnel_umber = $line[$i+1];
//                                break;
                            case '名字'://人员姓名,最终统一用姓名对应
                                $personnel_name = trim($line[$i+1]);
                                break;
                            case '测试日期'://测试日期
                                $resultArr['date'] = $test_date = date("Y-m-d", strtotime($line[$i+1]));
                                break;
                            case '测试时间'://测试时间
                                $resultArr['record_time'] = date("H:i:s", strtotime($line[$i+1]));
                                $test_date .= " " . $resultArr['record_time'];
                                $resultArr['record_time'] = $test_date;
                                break;
                            case '年龄':
                                if(!empty($resultKVS['age']['v'])) {
                                    $resultArr[$resultKVS['age']['v']] = $line[$i+1];
                                    $resultArr['staff_age'] = $line[$i+1];
                                }
                                break;
                            case '身高 (cm)':
                                if(!empty($resultKVS['height']['v'])) {
                                    $resultArr[$resultKVS['height']['v']] = $line[$i+1];
                                    $resultArr['staff_height'] = $line[$i+1];
                                }
                                break;
                            case '体重 (kg)':
                                if(!empty($resultKVS['body_weight']['v'])) {
                                    $resultArr[$resultKVS['body_weight']['v']] = $line[$i+1];
                                    $resultArr['staff_weight'] = $line[$i+1];
                                }
                                break;
                            case '性别':
                                if(!empty($resultKVS['gender']['v'])) {
                                    $resultArr[$resultKVS['gender']['v']] = strtolower($line[$i+1]) == 'male' ? '男' : '女';
                                }
                                break;
                            default :
                                break;
                        }
                    }
                    
                    if($line_index ==0) {
                        $volumnCount = count($line);
                        for($i=0; $i<$volumnCount; $i++) {
                            $volumn = $line[$i];
                            $tableHeads[$volumn] = array_search($volumn, $line);
                        }
                    }
                }
                    
                //受测人员
                if(empty($personnel_name)) {
                    continue;
//                    throw new Exception("受测人员不存在");
                }
                $staff = $business_db->name("staff")->where(['name'=>$personnel_name,'is_show'=>1])->find();
                if(empty($staff)) {
                    $msg = "受测人员【{$personnel_name}】在数据中心不存在，请先在数据中心创建";
                    $business_db->name("equipment_sync")->insert(['name'=>$personnel_name,'equipment'=>$this->equipmentMark, 'msg'=>$msg, 'create_time'=>$datetime, 'create_by'=>$create_by], true);
//                    throw new Exception($msg);
                    $sendmsg = [
                        'device' => $this->equipmentMark,
                        'business' => $business,
                        'message' => $msg
                    ];
//                    $this->sendMsg($business, $sendmsg, "数据同步");
                    if('shjxkx' != $business) {
                        continue;
                    }
                    
                } else {
                    $resultArr['staff_uuid'] = $staff['uuid'];
                    //受测人员所属部门
                    $department = $business_db->name("department")
                            ->alias("a")
                            ->join("staff_department b", 'a.uuid = b.department_uuid', 'left')
                            ->where(['b.staff_uuid'=>$staff['uuid'], 'a.is_show'=>1, 'a.status'=>1])
                            ->field(['a.uuid'])
                            ->find();
                    if(!empty($department['uuid'])) {
                        $resultArr['department_uuid'] = $department['uuid'];
                    }
                }
                
                //数据中心设备数据存储-----结果表
                $resultId = $equipmentResultModel->add($resultArr);
                if(!$resultId) {
                    throw new Exception("数据中心存储数据失败");
                }
                //数据中心设备数据存储-----结果扩展表
                if(!empty($resultExtendArr)) {
                    $resultExtendArr['equipment_result_id'] = $resultId;
                    $resultExtendArr['create_by'] = $create_by;
                    $resultExtendArr['other_info'] = json_encode($excelResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $equipmentResultExtendModel->add($resultExtendArr);
                }
                
                //过程数据
                $allProcess = [];
                foreach($excelProcess as $line_index=>$line) {
                    $processIsNull = true;//是否是空行
                    $processIsUnit = false;//是否是单位行
                    $processISNorm = false;//是否是指标行
                    $allProcessLine = [];
                    $processLine = [];
                    $processExtendLine = [];
                    foreach($processKVS as $k=>$v) {
                        if(!array_key_exists($k, $tableHeads)) {
                            continue;
                        }
                        //空行
                        if($processIsNull && !empty($line[$tableHeads[$k]])) {
                            $processIsNull = false;
                        }
                        //指标行
                        if(!$processISNorm && $v['k'] == (string)$line[$tableHeads[$k]]) {
                            $processISNorm = true;
                        }
                        //单位行
                        if(!$processIsUnit && $v['unit'] == (string)$line[$tableHeads[$k]]) {
                            $processIsUnit = true;
                        }
                        $volVal = is_null($line[$tableHeads[$k]]) ? '' : $line[$tableHeads[$k]];//单元格的值
                        $allProcessLine[$v['v']] = $volVal;
                        
                        if($v['index'] <= 20) {
                            $processLine[$v['v']] = $volVal;
                        } else {
                            $processExtendLine[$v['v']] = $volVal;
                        }
                    }
                    if($processIsNull || $processIsUnit || $processISNorm) {
                        continue;
                    }
                    //数据中心设备数据存储-----过程表
                    $processId = '';
                    if(!empty($processLine)) {
                        $processLine['equipment_result_id'] = $resultId;
                        $processLine['create_by'] = $create_by;
                        $processId = $equipmentProcessModel->add($processLine);
                        if(!$processId) {
                            throw new Exception("数据中心存储数据失败.");
                        }
                    }
                    //数据中心设备数据存储-----过程扩展表
                    if(!empty($processExtendLine)) {
                        $processExtendLine['equipment_process_id'] = $processId;
                        $processExtendLine['create_by'] = $create_by;
                        $equipmentProcessExtendModel->add($processExtendLine);
                    }
                    
                    
                    $allProcess[] = $allProcessLine;
                }
                
                //业务子系统存储数据
                //查找是否已有此数据
//                $businessResult = $business_db->name("equipment_result")
//                    ->where(['equipment_id'=>$equipment['id'],'business'=>$business,'equipment_mark'=>$this->equipmentMark,'relation_type'=>$this->resultRelationType,'date'=>$resultArr['date'],'staff_uuid'=>$staff['uuid'],'is_del'=>0])
//                    ->field('id,relation_type,staff_uuid')
//                    ->find();
                
                $business_db->startTrans();
                
                //处理旧数据
//                if($businessResult) {
//                    $businessResultDel = $business_db->name("equipment_result")->where(['id'=>$businessResult['id']])->update(['is_del'=>1]);
//                    if($businessResultDel === false) {
//                        throw new Exception("覆盖旧数据失败");
//                    }
//                    $businessResultExtendDel = $business_db->name("equipment_result_extend")->where(['equipment_result_id'=>$businessResult['id']])->update(['is_del'=>1]);
//                    if($businessResultExtendDel === false) {
//                        throw new Exception("覆盖旧数据失败.");
//                    }
//                    
//                    $businessProcessIds = $business_db->name("equipment_process")->where(['equipment_result_id'=>$businessResult['id'], 'is_del'=>0])->column("id");
//                    if($businessProcessIds) {
//                        $businessProcessDel = $business_db->name("equipment_process")->where(['equipment_result_id'=>$businessResult['id'], 'is_del'=>0])->update(['is_del'=>1]);
//                        if($businessProcessDel === false) {
//                            throw new Exception("覆盖旧数据失败..");
//                        }
//                        $businessProcessExtendDel = $business_db->name("equipment_process_extend")->where(['equipment_process_id'=>['in',$businessProcessIds], 'is_del'=>0])->update(['is_del'=>1]);
//                        if($businessProcessExtendDel === false) {
//                            throw new Exception("覆盖旧数据失败...");
//                        }
//                    }
//                }
                
                $resultArr['reference'] = $resultId;
                $businessResultId = $business_db->name("equipment_result")->insertGetId($resultArr);
                if(!$businessResultId) {
                    throw new Exception("数据存储失败");
                }
                if(!empty($resultExtendArr)) {
                    $resultExtendArr['equipment_result_id'] = $businessResultId;
                    $businessResultExtendId = $business_db->name("equipment_result_extend")->insertGetId($resultExtendArr);
                    if(!$businessResultExtendId) {
                        throw new Exception("数据存储失败.");
                    }
                }
                
                foreach($allProcess as $process) {
                    $businessProcess = array_slice($process, 0, 20, true);
                    $businessProcess['equipment_result_id'] = $businessResultId;
                    $businessProcess['create_by'] = $create_by;
                    
                    $businessProcessId = $business_db->name("equipment_process")->insertGetId($businessProcess);
                    if(!$businessProcessId) {
                        throw new Exception("数据存储失败..");
                    }
                    
                    $businessProcessExtend = array_slice($process, 20, null, true);
                    if($businessProcessExtend) {
                        $businessProcessExtend['equipment_process_id'] = $businessProcessId;
                        $businessProcessExtend['create_by'] = $create_by;
                        $businessProcessExtendId = $business_db->name("equipment_process_extend")->insertGetId($businessProcessExtend);
                        if(!$businessProcessExtendId) {
                            throw new Exception("数据存储失败...");
                        }
                    }
                    
                    
                }
                
                $business_db->commit();
                
                $msg = [
                    'device' => $this->equipmentMark,
                    'business' => $business,
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
        if(empty($business_config['mysql_database']) || !isset($business_config['mysql_prefix']) || empty($business_config['mysql_hostname'])
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
