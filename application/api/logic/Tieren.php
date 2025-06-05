<?php

namespace app\api\logic;
use app\base\BaseLogic;
use app\common\BaiduFace;
use think\App;
use think\Exception;
use app\model\EquipmentRelation as EquipmentRelationModel;
use app\model\EquipmentResult as EquipmentResultModel;
use app\model\EquipmentProcess as EquipmentProcessModel;
use app\model\EquipmentResultExtend as EquipmentResultExtendModel;
use app\model\EquipmentProcessExtend as EquipmentProcessExtendModel;
use think\Log;

class Tieren extends BaseLogic {

    private $relation_type_process = 32;//过程数据类型
    private $relation_type_result = 33;//结果数据类型

    private $equipmentRelationModel;
    private $equipmentResultModel;
    private $equipmentProcessModel;
    private $equipmentResultExtendModel;
    private $equipmentProcessExtendModel;

    public function __construct() {
        $this->equipmentRelationModel = new EquipmentRelationModel();
        $this->equipmentResultModel = new EquipmentResultModel();
        $this->equipmentProcessModel = new EquipmentProcessModel();
        $this->equipmentResultExtendModel = new EquipmentResultExtendModel();
        $this->equipmentProcessExtendModel = new EquipmentProcessExtendModel();
    }

    /**
     * 过程数据
     */
    public function deviceProcess($business_config, $data) {
        try {
            $this->set_db_config($business_config);
            $db_mysql = get_db();
            //查询人员
            $staff = $db_mysql->name('staff')->where('id', $data['userCode'])->find();
            $staff_uuid = '';
            $department_uuid = '';
            if (!empty($staff)) {
                $staff_uuid = $staff['uuid'];
                //查询部门
                $staff_department = $db_mysql->name('staff_department')->where('staff_uuid', $staff_uuid)->find();
                $department_uuid = !empty($staff_department['department_uuid']) ? $staff_department['department_uuid'] : 0;
            }
            //查询设备
            $device = $db_mysql->name('equipment_hardware')->where('device_id', $data['deviceKey'])->find();
            $equipment_id = !empty($device['id']) ? $device['id'] : 0;
            $record_time = !empty($data['data']['ts']) ? $data['data']['ts'] : date('Y-m-d H:i:s');
            $date = date('Y-m-d', strtotime($record_time));
            $equipment_result = [
                'business' => $data['business'],
                'equipment_id' => $equipment_id,
                'relation_type' => $this->relation_type_result,
                'department_uuid' => $department_uuid,
                'staff_uuid' => $staff_uuid,
                'date' => $date,
                'record_time' => $record_time,
                'reference' => APP::$trace_id,
                'k15' => 0,
                'k1' => $data['deviceKey'],
                'k2' => $data['deviceName'],
                'k3' => $data['userCode'],
                'k4' => $data['typeCode'],
                'k5' => $data['typeName'],
                'k11' => 0,
            ];
            $equipment_process = [
                'equipment_result_id' => 0,
                'reference' => APP::$trace_id,
                'k1' => $data['deviceKey'],
                'k2' => $data['deviceName'],
                'k3' => $data['userCode'],
                'k4' => $data['typeCode'],
                'k5' => $data['typeName'],
            ];
            //查询过程字段
            $relations = $this->equipmentRelationModel->inquiryAll(['type' => $this->relation_type_process, 'is_del' => 0]);
            $v_indexs = [];
            $relation_list = [];
            foreach ($relations as $item) {
                $v_index = trim($item['v'],'k');
                $v_indexs[] = $v_index;
                $item['v_index'] = $v_index;
                $relation_list[$item['k']] = $item;
            }
            $norms = $data['data'];
            $model = !empty($data['model']) ? $data['model'] : null;
            foreach ($norms as $k => $v) {
                $relation = !empty($relation_list[$k]) ? $relation_list[$k] : '';
                //新增指标
                if (empty($relation)) {
                    //组装字段
                    $model_set = !empty($model[$k]) ? $model[$k] : null;
                    $v_index_new = max($v_indexs) + 1;
                    $miss_v = 'k'.$v_index_new;
                    $miss_param = [
                        'type' => $this->relation_type_process,
                        'k' => $k,
                        'v' => $miss_v,
                        'unit' => !empty($model_set['funcUnit']) ? $model_set['funcUnit'] : '',
                        'note' => !empty($model_set['funcName']) ? $model_set['funcName'] : '',
                        'note_en' => !empty($model_set['funcDesc']) ? $model_set['funcDesc'] : '',
                        'create_by' => 'api',
                    ];
                    //插入字段
                    $this->equipmentRelationModel->insertGetId($miss_param);
                    $db_mysql->name('equipment_relation')->insertGetId($miss_param);
                    //更新数据
                    $miss_param['v_index'] = $v_index_new;
                    $v_indexs[] = $v_index_new;
                    $relation_list[$k] = $miss_param;
                    $relation = $miss_param;
                    Log::write(sprintf('设备测试参数与扩展表对应关系缺少参数：type:%s;k:%s;v:%s',$miss_param['type'], $k, $miss_v));
                }
                if ($relation['v_index'] > 20) {
                    $equipment_process['process_extend'][$relation['v']] = $v;
                } else {
                    $equipment_process[$relation['v']] = $v;
                }
            }
            //查询当前设备上一个没有结束的结果数据
            $result_where = [
                'business' => $equipment_result['business'],
                'k15' => 0,
                'is_del' => 0,
            ];
            if (!empty($device)) {
                $result_where['equipment_id'] = $device['id'];
            } else {
                $result_where['k1'] = $data['deviceKey'];
            }
            $result_last = $this->equipmentResultModel->inquiryOne($result_where,['id' => 'desc']);
            $mac_last_id = 0;
            $last_id = 0;
            $user_change = 0;
            if (!empty($result_last)) {
                $mac_last_id = $result_last['id'];
                $last_info = $db_mysql->name('equipment_result')
                    ->where('reference', $mac_last_id)
                    ->order(['id' => 'desc'])
                    ->limit(1)
                    ->find();
                $last_id = $last_info['id'];
                //设备换人
                if ((!empty($staff) && $result_last['staff_uuid'] != $staff['uuid']) || $result_last['k3'] != $data['userCode']) {
                    $user_change = 1;
                }
            }
            //如果设备换人，结束上一条数据
            if ($mac_last_id > 0 && $user_change == 1) {
                $this->equipmentResultModel->modifyById($mac_last_id, ['k15' => 1]);
                $db_mysql->name('equipment_result')->where('id', $last_id)->update(['k15' => 1]);
            }
            //如果设备换人，重新插入新数据
            if ($mac_last_id == 0 || $user_change == 1) {
                //插入数据中心数据库主表
                $mac_last_id = $this->equipmentResultModel->add($equipment_result);
                //插入用户数据库主表
                $equipment_result['reference']  = $mac_last_id;
                $last_id = $db_mysql->name('equipment_result')->insertGetId($equipment_result);
            }
            $process_extend = !empty($equipment_process['process_extend']) ? $equipment_process['process_extend'] : [];
            unset($equipment_process['process_extend']);
            //插入数据中心过程数据
            $equipment_process['equipment_result_id'] = $mac_last_id;
            $mac_process_last_id = $this->equipmentProcessModel->add($equipment_process);
            //插入用户数据库过程
            $equipment_process['equipment_result_id']  = $last_id;
            $equipment_process['reference']  = $mac_process_last_id;
            $process_last_id = $db_mysql->name('equipment_process')->insertGetId($equipment_process);
            if (!empty($process_extend)) {
                //插入数据中心数据库扩展表
                $process_extend['equipment_process_id'] = $mac_process_last_id;
                $this->equipmentProcessExtendModel->add($process_extend);
                //插入用户数据库扩展表
                $process_extend['equipment_process_id'] = $process_last_id;
                $db_mysql->name('equipment_process_extend')->insertGetId($process_extend);
            }
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '同步成功', 'data'=>[]];
    }

    /**
     * 结果数据
     */
    public function deviceResult($business_config, $data) {
        try {
            $this->set_db_config($business_config);
            $db_mysql = get_db();
            //查询人员
            $staff = $db_mysql->name('staff')->where('id', $data['userCode'])->find();
            $staff_uuid = '';
            $department_uuid = '';
            if (!empty($staff)) {
                $staff_uuid = $staff['uuid'];
                //查询部门
                $staff_department = $db_mysql->name('staff_department')->where('staff_uuid', $staff_uuid)->find();
                $department_uuid = !empty($staff_department['department_uuid']) ? $staff_department['department_uuid'] : 0;
            }
            //查询设备
            $device = $db_mysql->name('equipment_hardware')->where('device_id', $data['deviceKey'])->find();
            $equipment_id = !empty($device['id']) ? $device['id'] : 0;
            $record_time = !empty($data['data']['startTime']) ? $data['data']['startTime'] : date('Y-m-d H:i:s');
            $date = date('Y-m-d', strtotime($record_time));
            $equipment_result = [
                'business' => $data['business'],
                'equipment_id' => $equipment_id,
                'relation_type' => $this->relation_type_result,
                'department_uuid' => $department_uuid,
                'staff_uuid' => $staff_uuid,
                'date' => $date,
                'record_time' => $record_time,
                'reference' => APP::$trace_id,
                'k15' => 1,
                'k1' => $data['deviceKey'],
                'k2' => $data['deviceName'],
                'k3' => $data['userCode'],
                'k4' => $data['typeCode'],
                'k5' => $data['typeName'],
            ];
            //查询过程字段
            $relations = $this->equipmentRelationModel->inquiryAll(['type' => $this->relation_type_result, 'is_del' => 0]);
            $v_indexs = [];
            $relation_list = [];
            foreach ($relations as $item) {
                $v_index = trim($item['v'],'k');
                $v_indexs[] = $v_index;
                $item['v_index'] = $v_index;
                $relation_list[$item['k']] = $item;
            }
            $norms = $data['data'];
            $model = !empty($data['model']) ? $data['model'] : null;
            foreach ($norms as $k => $v) {
                $relation = !empty($relation_list[$k]) ? $relation_list[$k] : '';
                //新增指标
                if (empty($relation)) {
                    //组装字段
                    $model_set = !empty($model[$k]) ? $model[$k] : null;
                    $v_index_new = max($v_indexs) + 1;
                    $miss_v = 'k'.$v_index_new;
                    $miss_param = [
                        'type' => $this->relation_type_result,
                        'k' => $k,
                        'v' => $miss_v,
                        'unit' => !empty($model_set['funcUnit']) ? $model_set['funcUnit'] : '',
                        'note' => !empty($model_set['funcName']) ? $model_set['funcName'] : '',
                        'note_en' => !empty($model_set['funcDesc']) ? $model_set['funcDesc'] : '',
                        'create_by' => 'api',
                    ];
                    //插入字段
                    $this->equipmentRelationModel->insertGetId($miss_param);
                    $db_mysql->name('equipment_relation')->insertGetId($miss_param);
                    //更新数据
                    $miss_param['v_index'] = $v_index_new;
                    $v_indexs[] = $v_index_new;
                    $relation_list[$k] = $miss_param;
                    $relation = $miss_param;
                    Log::write(sprintf('设备测试参数与扩展表对应关系缺少参数：type:%s;k:%s;v:%s',$miss_param['type'], $k, $miss_v));
                }
                if ($relation['v_index'] > 20) {
                    $equipment_result['result_extend'][$relation['v']] = $v;
                } else {
                    $equipment_result[$relation['v']] = $v;
                }
            }
            //查询当前设备上一个没有结束的结果数据
            $result_where = [
                'business' => $equipment_result['business'],
                'k15' => 0,
                'is_del' => 0,
            ];
            if (!empty($device)) {
                $result_where['equipment_id'] = $device['id'];
            } else {
                $result_where['k1'] = $data['deviceKey'];
            }
            $result_last = $this->equipmentResultModel->inquiryOne($result_where,['id' => 'desc']);
            $mac_last_id = 0;
            $last_id = 0;
            $user_change = 0;
            if (!empty($result_last)) {
                $mac_last_id = $result_last['id'];
                $last_info = $db_mysql->name('equipment_result')
                    ->where('reference', $mac_last_id)
                    ->order(['id' => 'desc'])
                    ->limit(1)
                    ->find();
                $last_id = $last_info['id'];
                //设备换人
                if ((!empty($staff) && $result_last['staff_uuid'] != $staff['uuid']) || $result_last['k3'] != $data['userCode']) {
                    $user_change = 1;
                }
            }
            //如果设备换人，结束上一条数据
            if ($mac_last_id > 0 && $user_change == 1) {
                $this->equipmentResultModel->modifyById($mac_last_id, ['k15' => 1]);
                $db_mysql->name('equipment_result')->where('id', $last_id)->update(['k15' => 1]);
            }
            $result_extend = !empty($equipment_result['result_extend']) ? $equipment_result['result_extend'] : [];
            unset($equipment_result['result_extend']);
            //如果设备换人，重新插入新数据
            if ($mac_last_id == 0 || $user_change == 1) {
                //插入数据中心数据库主表
                $mac_last_id = $this->equipmentResultModel->add($equipment_result);
                //插入用户数据库主表
                $equipment_result['reference']  = $mac_last_id;
                $last_id = $db_mysql->name('equipment_result')->insertGetId($equipment_result);
                //插入结果扩展表
                if (!empty($result_extend)) {
                    $result_extend['equipment_result_id'] = $mac_last_id;
                    $this->equipmentResultExtendModel->add($result_extend);
                    $result_extend['equipment_result_id'] = $last_id;
                    $db_mysql->name('equipment_result_extend')->insertGetId($result_extend);
                }
            } else {
                //更新数据中心数据库主表
                $this->equipmentResultModel->modifyById($mac_last_id, $equipment_result);
                $equipment_result['reference'] = $mac_last_id;
                $db_mysql->name('equipment_result')->where('id', $last_id)->update($equipment_result);
                //更新结果扩展表
                if (!empty($result_extend)) {
                    $mac_extend = $this->equipmentResultExtendModel->inquiryOne(['equipment_result_id' => $mac_last_id]);
                    if (!empty($mac_extend)) {
                        $this->equipmentResultExtendModel->modifyById($mac_extend['id'], $result_extend);
                    } else {
                        $result_extend['equipment_result_id'] = $mac_last_id;
                        $this->equipmentResultExtendModel->add($result_extend);
                    }
                    $extend = $db_mysql->name('equipment_result_extend')->where('equipment_result_id', $last_id)->find();
                    if (!empty($extend)) {
                        $db_mysql->name('equipment_result_extend')->where('id', $extend['id'])->update($result_extend);
                    } else {
                        $result_extend['equipment_result_id'] = $last_id;
                        $db_mysql->name('equipment_result_extend')->insertGetId($result_extend);
                    }
                }
            }
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '同步成功', 'data'=>['staff' => $staff]];
    }

    //人脸识别
    public function searchFace($business_config, $faceUrl, $faceType) {
        try {
            $this->set_db_config($business_config);
            $db_mysql = get_db();
            $baiduFace = new BaiduFace();
            $baiduFace->setDb($db_mysql);
            $baiduFace->setBaiduGroupID($business_config['baidu_face_db_group_id']);
            $baiduFace->setBaiduApikey($business_config['baidu_apikey']);
            $baiduFace->setBaiduSecretkey($business_config['baidu_secretkey']);
            $search_face = $baiduFace->search($faceUrl, $faceType);
            $staff = false;
            if (!empty($search_face) && !empty($search_face['user_list']) && !empty($search_face['user_list'][0])) {
                $user_id = $search_face['user_list'][0]['user_id'];
                $staff = $this->inquiryOne($business_config, 'staff', ['id' => $user_id]);
            }
            if (empty($staff)) {
                throw new Exception('人脸识别失败，请重新识别或检查人脸信息');
            }
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '查询成功', 'data'=> ['staff' => $staff]];
    }
}
