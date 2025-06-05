<?php


namespace app\api\logic;

use think\App;
use think\Config;
use think\Exception;

use app\model\EquipmentRelation as EquipmentRelationModel;
use app\model\EquipmentResult as EquipmentResultModel;
use app\model\EquipmentResultExtend as EquipmentResultExtendModel;
class Inbody770 {
    public $equipmentMark = 'Inbody770';
    public $relationType = 23;
    public $equipmentRelationModel;
    public $equipmentResultModel;
    public $equipmentResultExtendModel;
    public function __construct() {
        $this->equipmentRelationModel = new EquipmentRelationModel();
        $this->equipmentResultModel = new EquipmentResultModel();
        $this->equipmentResultExtendModel = new EquipmentResultExtendModel();
    }

    private $db_cache = [];

    public function getBusinessDb($business) {
        if (!empty($this->db_cache[$business])) {
            return $this->db_cache[$business];
        }
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            throw new Exception('商户配置错误');
        }
        if(empty($business_config['mysql_database']) || empty($business_config['mysql_prefix']) || empty($business_config['mysql_hostname'])
                || empty($business_config['mysql_username']) || empty($business_config['mysql_password'])) {
            throw new Exception('商户配置错误');
        }
        $this->db_cache[$business] = get_db_mysql($business_config['mysql_database'], $business_config['mysql_prefix'], $business_config['mysql_hostname'],
            $business_config['mysql_username'], $business_config['mysql_password']);
        return $this->db_cache[$business];
    }
    
    public function sendMsg($business, $msg, $title) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            throw new Exception('商户配置错误');
        }
        \app\common\QwRobot::pushMsgFormat($msg, $business_config['name'].$title);
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
     * 获取业务系统人员信息
     * @param string $business
     * @param string $personnel_umber
     */
    public function getStaff($business, $personnel_umber) {
        $business_db = $this->getBusinessDb($business);
        switch ($business) {
            case 'ahkx':
            case 'tnxljstks':
                $staff_where = ['code' => $personnel_umber, 'del_flag' => 0];
                break;
            case 'qhdkx':
                $staff_where = ['personnel_umber' => $personnel_umber, 'is_show' => 1];
                break;
            default:
                $staff_where = ['personnel_umber' => $personnel_umber, 'is_show' => 1];
                break;
        }
        $staff = $business_db->name("staff")->where($staff_where)->find();
        return $staff;
    }

    /**
     * 获取业务系统设备信息
     * @param string $business
     */
    public function getEquipment($business) {
        $business_db = $this->getBusinessDb($business);
        //设备系统编号
        $equipment_sys_number = $this->getEquipmentSysNumber($business);
        switch ($business) {
            case 'ahkx':
            case 'tnxljstks':
                $equipment = $business_db->name("equipment_hardware")->where(['id' => $equipment_sys_number, 'is_show' => 1])->find();
                break;
            case 'qhdkx':
                $equipment = $business_db->name("device_manage")->where(['id' => $equipment_sys_number, 'is_show' => 1])->find();
                break;
            default:
                $equipment = $business_db->name("equipment")->where(['system_number'=>$equipment_sys_number, 'is_del'=>0])->find();
                break;
        }
        return $equipment;
    }

    /**
     * 获取业务系统人员部门信息
     * @param string $business
     * @param string $staff_uuid
     */
    public function getStaffDepartment($business, $staff_uuid) {
        $business_db = $this->getBusinessDb($business);
        switch ($business) {
            case 'ahkx':
            case 'tnxljstks':
                $department_where = ['b.staff_uuid' => $staff_uuid, 'a.del_flag' => 0, 'a.status' => 1];
                break;
            default:
                $department_where = ['b.staff_uuid' => $staff_uuid, 'a.is_show' => 1, 'a.status' => 1];
                break;
        }
        $department = $business_db->name("department")
            ->alias("a")
            ->join("staff_department b", 'a.uuid = b.department_uuid', 'left')
            ->where($department_where)
            ->field(['a.uuid'])
            ->find();
        return $department;
    }
    
    /**
     * 会员信息同步（查询会员）
     * @param type $params
     * @return type
     * @throws Exception
     */
    public function getUserInfo($params) {
        $data = [
            'IsResult' => true,
            'INBODY_USER_INFO' => [],
            "ErrorMsg" => ''
        ];
        try {
            if(empty($params['USER_ID'])) {
                throw new Exception("USER_ID 不能为空");
            }
            $personnel_umber = $params['USER_ID'];
            if(empty($params['business'])) {
                throw new Exception("参数错误：business");
            }
            $business = $params['business'];//商户唯一编号
            $staff = $this->getStaff($business, $personnel_umber);
            if (empty($staff)) {
                throw new Exception("会员不存在");
            }
            $data['INBODY_USER_INFO'][] = [
                'USER_ID' => $personnel_umber,
                'USER_NAME' => $staff['name'],
                'USER_GENDER' => $staff['sex'] == 2 ? 'F' : 'M',
                'USER_BIRTHDAY' => date('Ymd', strtotime($staff['birthday'])),
                'USER_AGE' => get_age($staff['birthday']),
                'USER_HEIGHT' => $staff['height'],
                'ORDER_DATE' => date('Ymd', strtotime($staff['create_time'])),
            ];
            
            $msg = [
                'device' => $this->equipmentMark,
                'business' => $business
            ];
            $this->sendMsg($business, $msg, "会员信息同步");
        } catch(Exception $e) {
            $data['IsResult'] = false;
            $data['ErrorMsg'] = $e->getMessage();
        }
        
        return $data;
    }
    
    /**
     * 测试数据上传接口
     * @param type $params
     * @return type
     * @throws Exception
     */
    public function setInbodyData($params) {
        $data = [
            'IsResult' => true,
            'ErrorMsg' => ''
        ];
        $create_time = date("Y-m-d H:i:s");
        $create_by = 'api';
        $business_db = null;
        try {
            if(empty($params['USER_ID'])) {
                throw new Exception("USER_ID 不能为空");
            }
            $personnel_umber = $params['USER_ID'];
            if(empty($params['business'])) {
                throw new Exception("参数错误：business");
            }
            $business = $params['business'];//商户唯一编号
            $business_db = $this->getBusinessDb($business);
            $staff = $this->getStaff($business, $personnel_umber);
            if (empty($staff)) {
                throw new Exception("会员不存在");
            }
            $equipment = $this->getEquipment($business);
            if (empty($equipment)) {
                throw new Exception("设备不存在");
            }
            $resultArr = [
                'business' => $business,
                'equipment_id' => $equipment['id'],
                'equipment_mark' => $this->equipmentMark,
                'relation_type' => $this->relationType,
                'department_uuid' => '',
                'staff_uuid' => $staff['uuid'],
//                'date' => '',
//                'record_time' => '',
//                'reference' => '',
                'create_time' => $create_time,
                'create_by' => $create_by
            ];
            $test_date = '';
            if(!empty($params['DATETIMES'])) {
                $time = strtotime($params['DATETIMES']);
                $test_date = date("Y-m-d", $time);
                $resultArr['date'] = $test_date;
                $resultArr['record_time'] = date("Y-m-d H:i:s", $time);
                $resultArr['datetimes'] = $params['DATETIMES'];
            }
            //部门
            $department = $this->getStaffDepartment($business, $staff['uuid']);
            if(!empty($department['uuid'])) {
                $resultArr['department_uuid'] = $department['uuid'];
            }
            
            $kvs = $this->equipmentRelationModel->inquiryAll(['type'=> $this->relationType, 'is_del'=>0], [], ['k','v','unit']);
            $relations = [];
            foreach($kvs as $v) {
                $v['index'] = trim($v['v'], 'k');
                $relations[$v['k']] = $v;
            }
            $resultExtendArr = [];
            foreach($params as $k=>$v) {
                if(array_key_exists($k, $relations)) {
                    $resultOrExtendKey = $relations[$k]['v'];
                    if($k == 'USER_AGE') {  //年龄
                        $v = intval($v);
                        $resultArr['staff_age'] = $v;
                    } elseif($k == 'USER_HEIGHT') {    //身高
                        $resultArr['staff_height'] = $v;
                    } elseif($k == 'WT') {    //体重
                        $resultArr['staff_weight'] = $v;
                    } elseif($k == 'USER_GENDER') { //性别
                        $v = strtolower($v) == 'm' ? '男' : '女';
                    }
                    if($relations[$k]['index'] <= 20) {
                        $resultArr[$resultOrExtendKey] = $v;
                    } else {
                        $resultExtendArr[$resultOrExtendKey] = $v;
                    }
                }
            }
            
            $resultId = $this->equipmentResultModel->add($resultArr);
            if(!$resultId) {
                throw new Exception("存储测试结果失败");
            }
            if(!empty($resultExtendArr)) {
                $resultExtendArr['equipment_result_id'] = $resultId;
                $resultExtendArr['create_time'] = $create_time;
                $resultExtendArr['create_by'] = $create_by;
                $resultExtendId = $this->equipmentResultExtendModel->add($resultExtendArr);
                if(!$resultExtendId) {
                    throw new Exception("存储测试结果失败.");
                }
            }

//            $businessResult = $business_db->name("equipment_result")
//                    ->where(['equipment_id'=>$equipment['id'],'business'=>$business,'equipment_mark'=>$this->equipmentMark,'relation_type'=>$this->relationType,'date'=>$test_date,'staff_uuid'=>$staff['uuid'],'is_del'=>0])
//                    ->field('id,relation_type,staff_uuid')
//                    ->find();
            
            $business_db->startTrans();
            
//            if($businessResult) {
//                $businessResultDel = $business_db->name("equipment_result")->where(['id'=>$businessResult['id']])->update(['is_del'=>1]);
//                if($businessResultDel === false) {
//                    throw new Exception("覆盖旧数据失败");
//                }
//                $businessResultExtendDel = $business_db->name("equipment_result_extend")->where(['equipment_result_id'=>$businessResult['id']])->update(['is_del'=>1]);
//                if($businessResultExtendDel === false) {
//                    throw new Exception("覆盖旧数据失败.");
//                }
//            }
            
            $resultArr['reference'] = $resultId;//业务系统的外部指向指向到数据中心测试结果记录id
            $cResultId = $business_db->name("equipment_result")->insertGetId($resultArr);
            if(!$cResultId) {
                throw new Exception("测试结果上传失败");
            }
            if(!empty($resultExtendArr)) {
                $resultExtendArr['equipment_result_id'] = $cResultId;
                $cResultExtendId = $business_db->name("equipment_result_extend")->insertGetId($resultExtendArr);
                if(!$cResultExtendId) {
                    throw new Exception("测试结果上传失败.");
                }
            }

            $business_db->commit();

            //插入身体成分表
            $this->addBodyComposition($resultArr, $resultExtendArr, $business, $business_db);
            
            $msg = [
                'device' => $this->equipmentMark,
                'business' => $business
            ];
            $this->sendMsg($business, $msg, "测试结果上传");
        } catch(Exception $e) {
            $business_db->rollback();
            $data = [
                'IsResult' => false,
                'ErrorMsg' => $e->getMessage()
            ];
        }
        return $data;
    }

    public function addBodyComposition($resultArr, $resultExtendArr, $business, $business_db) {
        //江苏体科所
        if ($business != 'tnxljstks') {
            return;
        }
        $data = [];
        foreach ($this->body_composition_relation as $k => $v) {
            if (isset($resultArr[$v])) {
                $data[$k] = $resultArr[$v];
            } else if (isset($resultExtendArr[$v])) {
                $data[$k] = $resultExtendArr[$v];
            } else {
                $data[$k] = '';
            }
        }
        $data['ExamTime'] = strtotime($data['date']);
        //查询当前人员当天的历史数据
        $dataOri = $business_db->name('body_composition')->where(['staff_uuid' => $data['staff_uuid'], 'ExamTime' => $data['ExamTime']])->find();
        if (empty($dataOri)) {//新增
            $data['uuid'] = guid();
            $business_db->name('body_composition')->insertGetId($data);
        } else {//覆盖更新
            //空不覆盖
            foreach ($data as $k => $v) {
                if ($v == '') {
                    unset($data[$k]);
                }
            }
            unset($data['create_by']);
            unset($data['create_time']);
            $business_db->name('body_composition')->where(['id' => $dataOri['id']])->update($data);
        }

        //更新人员表数据
        $update_data = [
            'weight' => $data['weight'],
            'weight_date' => $data['date'],
            'PhFatRate' => $data['PhFatRate'],
            'PhFatRate_date' => $data['date'],
        ];
        $business_db->name('staff')->where(['uuid' => $data['staff_uuid']])->update($update_data);
    }

    /*
     * 身体生成测试表对应关系tn_body_composition
     * */
    public $body_composition_relation = [
        'uuid' => '',
        'staff_uuid' => 'staff_uuid',//人员uuid
        'ExamTime' => '', // 体测日期
        'mac' => '', // 可能是检测设备的MAC地址相关信息
        'height' => 'k1', // 身高
        'weight' => 'k6', // 体重
        'weight_stdLow' => 'k7', // 体重标准下限
        'weight_stdUp' => 'k8', // 体重标准上限
        'weightStd' => '', // 体重标准值
        'excp_fat_qua' => 'k30', // 瘦体重
        'excp_fat_qua_stdLow' => 'k31', // 瘦体重标准下限
        'excp_fat_qua_stdUp' => 'k32', // 瘦体重标准上限
        'Musl' => 'k27', // 肌肉量
        'Musl_stdLow' => 'k28', // 肌肉量标准下限
        'Musl_stdUp' => 'k29', // 肌肉量标准上限
        'Bone_slm' => 'k33', // 骨骼肌含量
        'Bone_slm_stdLow' => 'k34', // 骨骼肌含量标准下限
        'Bone_slm_stdUp' => 'k35', // 骨骼肌含量标准上限
        'ph_wat_qua' => 'k9', // 身体总水分
        'ph_wat_qua_stdLow' => 'k10', // 身体中水分标准下限
        'ph_wat_qua_stdUp' => 'k11', // 身体总水分标准上限
        'protein' => 'k18', // 蛋白质含量
        'protein_stdLow' => 'k19', // 蛋白质含量标准下限
        'protein_stdUp' => 'k20', // 蛋白质含量标准上限
        'Minelal' => 'k142', // 矿物质含量
        'Minelal_stdLow' => 'k143', // 矿物质含量标准下限
        'Minelal_stdUp' => 'k144', // 矿物质含量标准上限
        'ph_fat_qua' => 'k24', // 身体脂肪量
        'ph_fat_qua_stdLow' => 'k25', // 身体脂肪量标准下限
        'ph_fat_qua_stdUp' => 'k26', // 身体脂肪量标准上限
        'Bcm_in_water' => 'k12', // 细胞内水分
        'Bcm_in_water_stdLow' => 'k13', // 细胞内水分标准下限
        'Bcm_in_water_stdUp' => 'k14', // 细胞内水分标准上限
        'Bcm_out_water' => 'k15', // 细胞外水分
        'Bcm_out_water_stdLow' => 'k16', // 细胞外水分标准下限
        'Bcm_out_water_stdUp' => 'k17', // 细胞外水分标准上限
        'bmi' => 'k36', // 身体质量指数
        'ph_fat_rate' => '', // 脂肪百分比
        'ENELGDay' => '', // 总能量消耗
        'BodyAge' => 'k4', // 身体年龄
        'impedance' => '', // 电阻抗
        'BuiltInLvl' => 'k132', // 内脏脂肪水平
        'Inprotein' => 'k133', // 内脏脂肪面积
        'WHR' => 'k129', // 腰臀比
        'Naizoushibou' => '', // 内脏脂肪含量
        'Hikashibou' => '', // 皮下脂肪含量
        'VFA05' => '', // 腹部脂肪预测 +05
        'VFA10' => '', // 腹部脂肪预测 +10
        'VFA15' => '', // 腹部脂肪预测 +15
        'VFA20' => '', // 腹部脂肪预测 +20
        'type' => '', // 体型
        'LarmMuscle' => 'k46', // 左上肢肌肉量
        'LarmMusclePercent' => 'k49', // 左上肢肌肉标准百分比
        'RarmMuscle' => 'k42', // 右上肢肌肉量
        'RarmMusclePercent' => 'k45', // 右上肢肌肉标准百分比
        'LlagMuscle' => 'k58', // 左下肢肌肉量
        'LlagMusclePercent' => 'k61', // 左下肢肌肉标准百分比
        'RlagMuscle' => 'k54', // 右下肢肌肉量
        'RlagMusclePercent' => 'k57', // 右下肢肌肉标准百分比
        'BtrunkMuscle' => 'k50', // 躯干肌肉量
        'BtrunkMusclePercent' => 'k53', // 躯干肌肉标准百分比
        'LArmBujongECF' => 'k109', // 左上肢细胞外体液占比
        'LArmBujongECW' => '', // 左上肢细胞外水分占比
        'RArmBujongECF' => 'k108', // 右上肢细胞外体液占比
        'RArmBujongECW' => '', // 右上肢细胞外水分占比
        'LLegBujongECF' => 'k112', // 左下肢细胞外体液占比
        'LLegBujongECW' => '', // 左下肢细胞外水分占比
        'RLegBujongECF' => 'k111', // 右下肢细胞外体液占比
        'RLegBujongECW' => '', // 右下肢细胞外水分占比
        'TrunkBujongECF' => 'k110', // 躯干细胞外体液占比
        'TrunkBujongECW' => '', // 躯干细胞外水分占比
        'Bujong' => '', // 浮肿状况
        'BujongEvaluate' => '', // 浮肿状况评估
        'proteinEvaluate' => '', // 蛋白质营养评估
        'MinelalEvaluate' => '', // 矿物质营养评估
        'celltotalEvaluate' => '', // 细胞总量营养评估
        'weightCtrl' => 'k125', // 体重控制目标
        'phfatCtrl' => 'k126', // 脂肪控制目标
        'MusalCtrl' => 'k127', // 软体重控制目标
        'Rimp01' => 'k148', // R电阻抗1K
        'Rimp05' => 'k153', // R电阻抗5K
        'Rimp50' => 'k158', // R电阻抗50K
        'Rimp250' => 'k163', // R电阻抗250K
        'Rimp550' => 'k168', // R电阻抗550K
        'Rimp1m' => 'k173', // R电阻抗1M
        'Limp01' => 'k149', // L电阻抗1K
        'Limp05' => 'k154', // L电阻抗5K
        'Limp50' => 'k159', // L电阻抗50K
        'Limp250' => 'k164', // L电阻抗250K
        'Limp550' => 'k169', // L电阻抗550K
        'Limp1m' => 'k174', // L电阻抗1M
        'RXc01' => 'k151', // Rxc电阻抗1K
        'RXc05' => 'k156', // Rxc电阻抗5K
        'RXc50' => 'k161', // Rxc电阻抗50K
        'RXc250' => 'k166', // Rxc电阻抗250K
        'RXc550' => 'k171', // Rxc电阻抗550K
        'RXc1m' => 'k176', // Rxc电阻抗1M
        'LXc01' => 'k152', // Lxc电阻抗1K
        'LXc05' => 'k157', // Lxc电阻抗5K
        'LXc50' => 'k162', // Lxc电阻抗50K
        'LXc250' => 'k167', // Lxc电阻抗250K
        'LXc550' => 'k172', // Lxc电阻抗550K
        'LXc1m' => 'k177', // Lxc电阻抗1M
        'Rhandimp' => '', // 右上肢电阻抗
        'Lhandimp' => '', // 左上肢电阻抗
        'Rlegimp' => '', // 右下肢电阻抗
        'Llegimp' => '', // 左下肢电阻抗
        'Cimp' => '', // 躯干电阻抗
        'Sys' => 'k211', // 收缩压
        'Dias' => 'k212', // 舒张压
        'Pulse' => 'k216', // 心率
        'prp' => '', // 心脏负荷指数
        'PhFatRateStdLow' => 'k40', // 体脂率下限
        'PhFatRateStdUp' => 'k41', // 体脂率上限
        'PhFatRateStd' => '', // 体脂率标准值
        'PhFatQuaStd' => '', // 体脂肪量标准值
        'PhFatRate' => 'k39', // 体脂率
        'mineral' => 'k21', // 无机盐
        'mineral_min' => 'k22', // 无机盐（下限标准）
        'mineral_max' => 'k23', // 无机盐（上限标准）
        'wed' => 'k107', // 细胞外液总量/身体总水分
        'date' => 'date', // 测试日期
        'record_time' => 'record_time', // 测试时间
        'create_by' => 'create_by', // 创建人
        'create_time' => 'create_time' // 创建时间
    ];
    
    /**
     * 处理上传测试报告
     * @param type $params
     */
    public function setInbodyImage($params) {
        $data = [
            'IsResult' => true,
            'ErrorMsg' => ''
        ];
        try {
            if(empty($params['USER_ID'])) {
                throw new Exception("USER_ID 参数不能为空");
            }
            $personnel_umber = $params['USER_ID'];
            if(empty($params['business'])) {
                throw new Exception("参数错误：business");
            }
            if(empty($params['INBODY_IMAGE'])) {
                throw new Exception("INBODY_IMAGE 参数不能为空");
            }
            if(empty($params['DATETIMES'])) {
                throw new Exception("DATETIMES 参数不能为空");
            }
            $test_date = date("Y-m-d", strtotime($params['DATETIMES']));
            $business = $params['business'];//商户唯一编号
            $business_db = $this->getBusinessDb($business);
            $staff = $this->getStaff($business, $personnel_umber);
            if (empty($staff)) {
                throw new Exception("会员不存在");
            }
            $equipment = $this->getEquipment($business);
            if (empty($equipment)) {
                throw new Exception("设备不存在");
            }
            
            //查找当天最近一条记录
            $result = $this->equipmentResultModel->inquiryOne(['equipment_id'=>$equipment['id'],'business'=>$business,'equipment_mark'=>$this->equipmentMark,'relation_type'=>$this->relationType,'date'=>$test_date,'staff_uuid'=>$staff['uuid'],'is_del'=>0], ['datetimes'=>'desc','create_time'=>'desc'], ['id','staff_uuid']);
            if(empty($result)) {
                throw new Exception("测试记录不存在");
            }
            $dir = ROOT_PATH . 'public' . DS . 'static' . DS . 'upload' . DS . $business . DS . $this->equipmentMark;
            !is_dir($dir) AND mkdir($dir, 0777, true);
            
            $imgdata = $params['INBODY_IMAGE'];
            $imgname = $personnel_umber . $params['DATETIMES'] . ".jpg";
            $imgfile = $dir . DS . $imgname;
            file_put_contents($imgfile, base64_decode($imgdata));
            $imgUrl = config('domain') . substr($imgfile, strpos($imgfile, 'static')-1);
            
            $resultUpdate = $this->equipmentResultModel->modifyById($result['id'], ['report_url'=>$imgUrl]);
            if($resultUpdate === false) {
                throw new Exception("上传报告失败");
            }
            
            $businessResultUpdate = $business_db->name("equipment_result")->where(['reference'=>$result['id']])->update(['report_url'=>$imgUrl]); 
            if($businessResultUpdate === false) {
                throw new Exception("上传报告失败.");
            }
            
            $msg = [
                'device' => $this->equipmentMark,
                'business' => $business
            ];
            $this->sendMsg($business, $msg, "测试报告上传");
        } catch(Exception $e) {
            $data = [
                'IsResult' => false,
                'ErrorMsg' => $e->getMessage()
            ];
        }
        return $data;
    }
    
    /**
     * 记录日志
     * @param type $text
     * @param type $filename
     */
    public function writeLog($text, $filename='inbody770') {
        if(is_array($text)) {
            $text = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $dir = $filePath = ROOT_PATH . 'public' . DS . 'static' . DS . 'upload' . DS .'api' . DS . date("Y-m-d");
        !is_dir($dir) AND mkdir($dir, 0777, true);
        $s = date("Y-m-d H:i:s") . "\t". $text . "\r\n";
        file_put_contents($dir . "/{$filename}.txt", $s, FILE_APPEND );
   }
}
