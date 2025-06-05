<?php

namespace app\api\logic;

use app\model\EquipmentProcess;
use app\model\EquipmentRelation;
use app\model\EquipmentResult;
use app\model\PipelineLog;
use app\model\EngineryAction;
use think\App;
use think\Exception;
use app\model\File as fileModel;

class Action {
    public $fileModel;
    private $equipmentRelationModel;
    private $equipmentResultModel;
    private $equipmentProcessModel;
    private $engineryActionModel;
    private $PipelineLogModel;
    public function __construct() {
        $this->fileModel = new fileModel();
        $this->equipmentRelationModel = new EquipmentRelation();
        $this->equipmentResultModel = new EquipmentResult();
        $this->equipmentProcessModel = new EquipmentProcess();
        $this->pipelineLogModel = new PipelineLog();
        $this->engineryActionModel = new EngineryAction();
    }

    /**
     * 同步数据
     * @throws Exception
     */
    public function ocrData($data, $file,$business, $enginery_band = 'eliteform') {
        try {
            $file_path_img = '';
            if (!empty($file)) {
                //上传文件
                $file_path = 'import' . DS . $enginery_band;
                $file_info = $this->fileModel->uploadFile($file, $file_path, true);
                if ($file_info['code']) {
                    throw new Exception($file_info['message']);
                }
                $file_path_img = $file_info['data'];
            }
            $res = $this->ocrDataDo($data, $file_path_img, $business, $enginery_band);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '同步成功', 'data'=> $res['data']];
    }

    /**
     * 数据解析
     * @throws Exception
     */
    public function ocrDataDo($data, $file_path_img, $business, $enginery_band = 'eliteform') {
        try {
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception("用户不存在");
            }
            $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
            $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
            $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';
            $mysql_username = !empty($business_config['mysql_username']) ? $business_config['mysql_username'] : '';
            $mysql_password = !empty($business_config['mysql_password']) ? $business_config['mysql_password'] : '';

            $db_mysql = get_db_mysql($mysql_database, $mysql_prefix, $mysql_hostname, $mysql_username, $mysql_password);


            $device_id = !empty($data['device_id']) ? $data['device_id'] : 0;
            $date = !empty($data['date']) ? $data['date'] : date('Y-m-d');
            $group_id = !empty($data['group_id']) ? $data['group_id'] : 0;
            $record_time = !empty($data['record_time']) ? $data['record_time'] : date('Y-m-d H:i:s');
            $file_name = !empty($data['file_name']) ? $data['file_name'] : intval(microtime(true) * 1000);
            $action_id = !empty($data['action_id']) ? $data['action_id'] : 0;
            $action_name = !empty($data['action_name']) ? $data['action_name'] : '';
            $weight = !empty($data['weight']) ? $data['weight'] : 0;
            $type = !empty($data['type']) ? $data['type'] : 'VELOCITY';
            $data_type = !empty($data['data_type']) ? $data['data_type'] : '';
            $v1 = !empty($data['v1']) ? $data['v1'] : 0;
            $v2 = !empty($data['v2']) ? $data['v2'] : 0;
            $v3 = !empty($data['v3']) ? $data['v3'] : 0;
            $v4 = !empty($data['v4']) ? $data['v4'] : 'ENDSET';
            $list1 = !empty($data['list1']) ? $data['list1'] : null;
            $list2 = !empty($data['list2']) ? $data['list2'] : null;
            $list3 = !empty($data['list3']) ? $data['list3'] : null;

            $ocr_data = [];
            $model_type = $v4 == 'ENDSET' ? 1 : 2;//1过程2结果
            $ocr_status = 0;
            if (!empty($file_path_img) && config('ocr_disjunctor')) {//阿里云OCR开关
                $ocr_status = 1;
                //文件绝对路径
                $file_path_ab = ROOT_PATH . 'public' . DS . $file_path_img;
                $img_base64 = base64_encode(file_get_contents($file_path_ab));
                //首次识别
                $ocr_1_start = microtime(true);
                \think\Log::write('ocr_1:'.$model_type.$file_path_img);
                $res = \ocr\OCRServer::run($img_base64, $model_type);
                \think\Log::write('ocr_runtime:'.round(microtime(true) - $ocr_1_start, 10));
                if ($res['code']) {
                    throw new Exception($res['message']);
                }
                $ocr_data = $res['data'];
                if (empty($ocr_data['v4']) || $ocr_data['v4'] != $v4) {
                    //二次识别
                    $model_type_rep = $model_type == 1 ? 2 : 1;
                    $ocr_2_start = microtime(true);
                    \think\Log::write('ocr_2:'.$model_type.$file_path_img);
                    $res = \ocr\OCRServer::run($img_base64, $model_type_rep);
                    \think\Log::write('ocr_runtime:'.round(microtime(true) - $ocr_2_start, 10));
                    if ($res['code']) {
                        throw new Exception($res['message']);
                    }
                    //如果二次识别-预测服务置信度更高，替换识别值
                    if ($res['data']['score'] > $ocr_data['score']) {
                        $model_type = $model_type_rep;
                        $ocr_data = $res['data'];
                    }
                }
            }
            //数据整合处理
            $action_name = !empty($ocr_data['action_name']) && $ocr_data['action_name'] != 'Lift Summary' ? $ocr_data['action_name'] : $action_name;
            $weight = !empty($ocr_data['weight']) ? $ocr_data['weight'] : $weight;
            $type = !empty($ocr_data['type']) ? $ocr_data['type'] : $type;
            $v1 = !empty($ocr_data['v1']) ? $ocr_data['v1'] : $v1;
            $v2 = !empty($ocr_data['v2']) ? $ocr_data['v2'] : $v2;
            $v3 = !empty($ocr_data['v3']) ? $ocr_data['v3'] : $v3;
            $v4 = !empty($ocr_data['v4']) ? $ocr_data['v4'] : $v4;
            $list1 = !empty($ocr_data['list1']) ? $ocr_data['list1'] : $list1;
            $list2 = !empty($ocr_data['list2']) ? $ocr_data['list2'] : $list2;
            $list3 = !empty($ocr_data['list3']) ? $ocr_data['list3'] : $list3;

            //数据格式化处理
            $detail_list = [];
            if (!empty($list1)) {
                foreach ($list1 as $detail_key => $detail_rep) {
                    $detail_rep_num = 1;
                    if (preg_match('/\d+/', $detail_rep, $arr)) {
                        $detail_rep_num = $arr[0];
                    }
                    $detail_list[] = [
                        'rep' => $detail_rep_num,
                        'peak' => !empty($list2[$detail_key]) ? trim($list2[$detail_key]) : '',
                        'average' => !empty($list3[$detail_key]) ? trim($list3[$detail_key]) : '',
                    ];
                }
            }
            $date = date('Y-m-d',strtotime($date));
            $record_time = date('Y-m-d H:i:s',strtotime($record_time));
            $action_name = trim($action_name);
            $weight_str = $weight;
            if (preg_match('/\d+/', $weight_str, $arr)) {
                $weight = $arr[0];
            } else {
                $weight = 0;
            }
            //重量单位
            $str_arr = explode($weight, $weight_str);
            $weight_unit = trim(end($str_arr));
            if (strpos($type, 'VELOCITY') !== false) {
                $action_type = 1;
                $type = 'VELOCITY';
            } else if (strpos($type, 'POWER') !== false) {
                $action_type = 2;
                $type = 'POWER';
            } else {
                $action_type = 3;
                $type = 'WORK';
            }
            $v1 = floatval($v1);
            if ($model_type == 1) {
                $v2 = intval($v2);
                $v3 = intval($v3);
            } else {
                $v2 = floatval($v2);
                $v3 = floatval($v3);
            }
            //数据比对
            $ocr_keys = ['action_name','weight','type','v1','v2','v3','v4','list1','list2','list3'];
            $data_diff = [];
            foreach ($ocr_keys as $ocr_key) {
                $data_org = !empty($data[$ocr_key]) ? $data[$ocr_key] : '';
                if (is_array($data_org)) {
                    $data_org = json_encode($data_org);
                    $data_ocr = json_encode($$ocr_key);
                } else {
                    $data_ocr = ''.$$ocr_key;
                }
                if ($ocr_key == 'weight') {
                    $data_ocr = $weight.$weight_unit;
                }
                if ($data_org !== $data_ocr) {
                    $data_diff[] = [$ocr_key => [$data_org,$data_ocr]];
                }
            }
            //图片信息
            $engineryDataImg = [
                'data_uuid' => '',
                'detail_id' => 0,
                'data' => json_encode($data),
                'data_ali' => json_encode($ocr_data),
                'data_diff' => json_encode($data_diff),
                'file_path' => $file_path_img,
                'file_name' => $file_name,
                'ocr_status' => $ocr_status,
                'model_type' => $model_type,
                'is_del' => 0,
                'create_by' => 'api',
                'create_time' => date('Y-m-d H:i:s'),
            ];
            //维护活动类型
            $action_data = [
                'enginery_band' => $enginery_band,
                'action_name' => $action_name,
                'is_del' => 0,
            ];
            //业务中心
            $action_info1 = $db_mysql->name('enginery_action')->where($action_data)->find();
            if (!empty($action_info1)) {
                $action_id1 = $action_info1['id'];
            } else {
                $action_data['create_by'] = 'api';
                $action_data['create_time'] = date('Y-m-d H:i:s');
                $action_id1 = $db_mysql->name('enginery_action')->insertGetId($action_data);
                \think\Log::write('action_data:'.$action_id1);
            }
            $action_info = $this->engineryActionModel->inquiryOne($action_data);
            if (!empty($action_info)) {
                $action_id = $action_info['id'];
            } else {
                $action_data['create_by'] = 'api';
                $action_data['create_time'] = date('Y-m-d H:i:s');
                $action_id = $this->engineryActionModel->add($action_data);
                \think\Log::write('action_data:'.$action_id);
            }

            //数据入库
            $params = [
                'device_id' => $device_id,
                'business' => $business,
                'date' => $date,
                'group_id' => $group_id,
                'record_time' => $record_time,
                'file_name' => $file_name,
                'action_id' => $action_id,
                'action_id1' => $action_id1,
                'action_name' => $action_name,
                'weight' => $weight,
                'weight_unit' => $weight_unit,
                'action_type' => $action_type,
                'data_type' => $data_type,
                'v1' => $v1,
                'v2' => $v2,
                'v3' => $v3,
                'detail_list' => $detail_list
            ];
            if ($model_type == 1) {//过程数据
                $saveRes = $this->processData($params, $db_mysql);
            } else {//结果数据
                $saveRes = $this->resultData($params, $db_mysql);
            }
            //图片重复上传，删除之前上传记录
            $imgs = $db_mysql->name('enginery_data_img')->where(['file_name' => $engineryDataImg['file_name'], 'is_del' => 0])->select();
            if (!empty($imgs)) {
                if (count($imgs) == 1) {
                    $mod_where['id'] = $imgs[0]['id'];
                } else {
                    $mod_where['id'] = ['in', array_column($imgs, 'id')];
                }
                \think\Log::write('engineryDataImgUpdate:'.json_encode($mod_where));
                $db_mysql->name('enginery_data_img')->where($mod_where)->update(['is_del' => 1]);
            }
            $engineryDataImg['data_id'] = $saveRes['data_id'];
            $engineryDataImg['detail_id'] = $saveRes['detail_id'];
            $last_id = $db_mysql->name('enginery_data_img')->insert($engineryDataImg);
            \think\Log::write('engineryDataImg:'.$last_id);
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '同步成功', 'data'=> $data_diff];
    }

    //过程数据
    public function processData($params, $db_mysql) {
        extract($params);
        //数据组装
        $peak = $average = $peak_source = $average_source = $peak_source_sub = $average_source_sub = 0;
        if ($data_type == 'AVG') {
            $average = $v1;
            $average_source = 1;
            $average_source_sub = 2;
        } else {
            $peak = $v1;
            $peak_source = 1;
            $peak_source_sub = 2;
        }
        $engineryDataDetail = [
//            'data_uuid' => '',
            'device_id' => $device_id,
            'date' => $date,
            'group_id' => $group_id,
            'record_time' => $record_time,
            'file_name' => $file_name,
            'action_id' => $action_id,
            'action_name' => $action_name,
            'weight' => $weight,
            'weight_unit' => $weight_unit,
            'action_type' => $action_type,//数据类型：1速度2功率3做功
            'data_type' => $data_type,//数据类型：PEAK/AVG
            'rep' => $v3,
            'peak' => $peak,
            'average' => $average,
            'ratio' => $v2,
            'peak_source' => $peak_source,
            'average_source' => $average_source,
            'data_source' => 1,
            'is_del' => 0,
            'create_by' => 'api',
            'create_time' => date('Y-m-d H:i:s'),
        ];
        //速度与功率数据算法互推 P=mgv,action_type=3时是做功，不需要计算
        // 1lb = 0.45kg
        $engineryDataDetailSub = [];
        if ($action_type == 1) {
            $engineryDataDetailSub = $engineryDataDetail;
//            unset($engineryDataDetailSub['device_id']);
            $engineryDataDetailSub['action_type'] = 2;
            $engineryDataDetailSub['peak_source'] = $peak_source_sub;
            $engineryDataDetailSub['average_source'] = $average_source_sub;
            $engineryDataDetailSub['data_source'] = 2;
            if(!$peak || !$average)
            {
                if ($weight_unit == 'lb') {
                    $engineryDataDetailSub['peak'] = round($engineryDataDetail['peak'] * $weight * 0.45 * 9.8, 2);
                    $engineryDataDetailSub['average'] = round($engineryDataDetail['average'] * $weight * 0.45 * 9.8, 2);
                } else {
                    $engineryDataDetailSub['peak'] = round($engineryDataDetail['peak'] * $weight * 9.8, 2);
                    $engineryDataDetailSub['average'] = round($engineryDataDetail['average'] * $weight * 9.8, 2);
                }
            }

        } else if ($action_type == 2) {
            $engineryDataDetailSub = $engineryDataDetail;
            $engineryDataDetailSub['action_type'] = 1;
            $engineryDataDetailSub['peak_source'] = $peak_source_sub;
            $engineryDataDetailSub['average_source'] = $average_source_sub;
            $engineryDataDetailSub['data_source'] = 2;
            //     if(!$peak || !$average)
            //     {
            //         if ($weight_unit == 'lb') {
            //             $engineryDataDetailSub['peak'] = $weight != 0 ? round($engineryDataDetail['peak'] / ($weight * 0.45 * 9.8), 2) : 0;
            //             $engineryDataDetailSub['average'] = $weight != 0 ? round($engineryDataDetail['average'] / ($weight * 0.45 * 9.8), 2) : 0;
            //         } else {
            //             $engineryDataDetailSub['peak'] = $weight != 0 ? round($engineryDataDetail['peak'] / ($weight * 9.8), 2) : 0;
            //             $engineryDataDetailSub['average'] = $weight != 0 ? round($engineryDataDetail['average'] / ($weight * 9.8), 2) : 0;
            //         }
            //   }
        }
        //数据入库
        $where_common = [
            'k18' => $engineryDataDetail['device_id'],
            'k1' => $engineryDataDetail['date'],
            'k2' => $engineryDataDetail['group_id'],
            'is_del' => 0,
        ];
        $where_rep = $where_common;
        $where_rep['k9'] = empty($engineryDataDetailSub) ? $engineryDataDetail['action_type'] : ['in', [1, 2]];
        $where_rep['k11'] = $engineryDataDetail['rep'];
        $ori_detail1 = $db_mysql->name('equipment_process')->where($where_rep)->select();
        $engineryDataDetail = [
            'equipment_result_id' => '',                    //data_uuid
            'k18' => $device_id,                            //device_id
            'k1' => $date,                                  //date
            'k2' => $group_id,                              //group_id
            'k3' => $record_time,                           //record_time
            'k4' => $file_name,                             //file_name
            'k5' => $action_id,                             //action_id
            'k6' => $action_name,                           //action_name
            'k7' => $weight,                                //weight
            'k8' => $weight_unit,                           //weight_unit
            'k9' => $action_type,//数据类型：1速度2功率3做功    //action_type
            'k10' => $data_type,//数据类型：PEAK/AVG          //data_type
            'k11' => $v3,                                   //rep
            'k12' => $peak,                                  //peak
            'k13' => $average,                              //average
            'k14' => $v2,                                   //ratio
            'k15' => $peak_source,                          //peak_source
            'k16' => $average_source,                       //average_source
            'k17' => 1,                                     //data_source
            'is_del' => 0,
            'create_by' => 'api',
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $ori_detail = $this->equipmentProcessModel->inquiryAll($where_rep);
//        var_dump($ori_detail);die;
        if (!empty($ori_detail)) {
            //如果当前序号已经插入过，逻辑删除 数据来源：优先级 1过程上传 > 2过程推算 > 3结果矫正 > 空
            foreach ($ori_detail as $ori_data) {
                if ($ori_data['k10'] == $engineryDataDetail['k10']) {
                    if ($ori_data['k12'] != 0 && $engineryDataDetail['k12'] == 0) {
                        $engineryDataDetail['k12'] = $ori_data['k12'];
                    }
                    if ($ori_data['k13'] != 0 && $engineryDataDetail['k13'] == 0) {
                        $engineryDataDetail['k13'] = $ori_data['k13'];
                    }
                    //重新计算 推算值
                    if (!empty($engineryDataDetailSub)) {
                        if ($action_type == 1) {
                            if(!$engineryDataDetailSub['peak'] || !$engineryDataDetailSub['average'])
                            {
                                if ($weight_unit == 'lb') {
                                    $engineryDataDetailSub['peak'] = round($engineryDataDetail['k12'] * $weight * 0.45 * 9.8, 2);
                                    $engineryDataDetailSub['average'] = round($engineryDataDetail['k13'] * $weight * 0.45 * 9.8, 2);
                                } else {
                                    $engineryDataDetailSub['peak'] = round($engineryDataDetail['k12'] * $weight * 9.8, 2);
                                    $engineryDataDetailSub['average'] = round($engineryDataDetail['k13'] * $weight * 9.8, 2);
                                }
                            }
                        } else if ($action_type == 2) {
                            // if(!$engineryDataDetailSub['peak'] || !$engineryDataDetailSub['average'])
                            // {
                            //     if ($weight_unit == 'lb') {
                            //         $engineryDataDetailSub['peak'] = $weight != 0 ? round($engineryDataDetail['k12'] / ($weight * 0.45 * 9.8), 2) : 0;
                            //         $engineryDataDetailSub['average'] = $weight != 0 ? round($engineryDataDetail['k13'] / ($weight * 0.45 * 9.8), 2) : 0;
                            //     } else {
                            //         $engineryDataDetailSub['peak'] = $weight != 0 ? round($engineryDataDetail['k12'] / ($weight * 9.8), 2) : 0;
                            //         $engineryDataDetailSub['average'] = $weight != 0 ? round($engineryDataDetail['k13'] / ($weight * 9.8), 2) : 0;
                            //     }
                            // }
                        }
                    }
                }
                if (!empty($engineryDataDetailSub) && $ori_data['k9'] == $engineryDataDetailSub['action_type']) {
                    if ($ori_data['k12'] != 0 && ($ori_data['k15'] == 1 || $engineryDataDetailSub['peak'] == 0)) {
                        $engineryDataDetailSub['peak'] = $ori_data['k12'];
                        $engineryDataDetailSub['peak_source'] = $ori_data['k15'];
                    }
                    if ($ori_data['k13'] != 0 && ($ori_data['k16'] == 1 || $engineryDataDetailSub['average'] == 0)) {
                        $engineryDataDetailSub['average'] = $ori_data['k13'];
                        $engineryDataDetailSub['average_source'] = $ori_data['k16'];
                    }
                }
            }
            if (count($ori_detail) == 1) {
                $mod_where['id'] = $ori_detail[0]['id'];
            } else {
                $mod_where['id'] = ['in', array_column($ori_detail, 'id')];
            }
            \think\Log::write('engineryDataUpdate:'.json_encode($mod_where));
            $this->equipmentProcessModel->modifyByWhere($mod_where, ['is_del' => 1]);

            $db_mysql->name('equipment_process')->where($mod_where)->update(['is_del' => 1]);
            $engineryDataDetail['equipment_result_id'] = $ori_detail[0]['equipment_result_id'];
        } else {
            //数据主表是否存在
            $ori_data = $this->equipmentResultModel->inquiryOne(['equipment_id'=>$engineryDataDetail['k18'],'date'=>$engineryDataDetail['k1'],'k1'=>$engineryDataDetail['k2']]);

            if (!empty($ori_data)) {
                $engineryDataDetail['equipment_result_id'] = $ori_data['id'];
            } else {
                //初始化主表
//                $uuid = guid();
//                $engineryData_ini = [
//                    'uuid' => $uuid,
//                    'device_id' => $engineryDataDetail['device_id'],
//                    'date' => $engineryDataDetail['date'],
//                    'group_id' => $engineryDataDetail['group_id'],
//                    'record_time' => $engineryDataDetail['record_time'],
//                    'file_name' => $engineryDataDetail['file_name'],
//                    'action_id' => $engineryDataDetail['action_id'],
//                    'action_name' => $engineryDataDetail['action_name'],
//                    'weight' => $engineryDataDetail['weight'],
//                    'weight_unit' => $engineryDataDetail['weight_unit'],
//                    'velocity_peak' => 0,
//                    'velocity_average' => 0,
//                    'power_peak' => 0,
//                    'power_average' => 0,
//                    'work_peak' => 0,
//                    'work_average' => 0,
//                    'work_total' => 0,
//                    'is_del' => 0,
//                    'create_by' => 'api',
//                    'create_time' => date('Y-m-d H:i:s'),
//                ];
                $engineryData_ini = [
                    'business' => $business,
                    'equipment_id' => $device_id,
                    'equipment_mark'=>'Eliteform',
                    'relation_type'=>13,
                    'date' => $date,
                    'record_time' => $record_time,
                    'k1' => $engineryDataDetail['k2'],
                    'k2' => $engineryDataDetail['k4'],
                    'k3' => $engineryDataDetail['k5'],
                    'k4' => $engineryDataDetail['k6'],
                    'k5' => $engineryDataDetail['k7'],
                    'k6' => $engineryDataDetail['k8'],
                    'k7' => 0,
                    'k8' => 0,
                    'k9' => 0,
                    'k10' => 0,
                    'k11' => 0,
                    'k12' => 0,
                    'k13' => 0,
                    'create_by' => 'api',
                    'create_time' => date('Y-m-d H:i:s')
                ];
                $engineryData_ini['mode'] = empty($engineryData_ini['k4']) ? '' : $engineryData_ini['k4'];
                $last_id = $this->equipmentResultModel->add($engineryData_ini);
                \think\Log::write('engineryData_ini:'.$last_id);
                $engineryDataDetail['equipment_result_id'] = $last_id;

            }
        }

        $ori_detail1 = $db_mysql->name('equipment_process')->where($where_rep)->select();
//        var_dump($ori_detail1);die;
        if (!empty($ori_detail1)) {
            //如果当前序号已经插入过，逻辑删除 数据来源：优先级 1过程上传 > 2过程推算 > 3结果矫正 > 空
            foreach ($ori_detail1 as $ori_data) {
                if ($ori_data['k10'] == $engineryDataDetail['k10']) {
                    if ($ori_data['k12'] != 0 && $engineryDataDetail['k12'] == 0) {
                        $engineryDataDetail['k12'] = $ori_data['k12'];
                    }
                    if ($ori_data['k13'] != 0 && $engineryDataDetail['k13'] == 0) {
                        $engineryDataDetail['k13'] = $ori_data['k13'];
                    }
                    //重新计算 推算值
                    if (!empty($engineryDataDetailSub)) {
                        if ($action_type == 1) {
                            if(!$engineryDataDetailSub['peak'] || $engineryDataDetailSub['average'])
                            {
                                if ($weight_unit == 'lb') {
                                    $engineryDataDetailSub['peak'] = round($engineryDataDetail['k12'] * $weight * 0.45 * 9.8, 2);
                                    $engineryDataDetailSub['average'] = round($engineryDataDetail['k13'] * $weight * 0.45 * 9.8, 2);
                                } else {
                                    $engineryDataDetailSub['peak'] = round($engineryDataDetail['k12'] * $weight * 9.8, 2);
                                    $engineryDataDetailSub['average'] = round($engineryDataDetail['k13'] * $weight * 9.8, 2);
                                }
                            }
                        } else if ($action_type == 2) {
                            // if(!$engineryDataDetailSub['peak'] || $engineryDataDetailSub['average'])
                            // {
                            //     if ($weight_unit == 'lb') {
                            //         $engineryDataDetailSub['peak'] = $weight != 0 ? round($engineryDataDetail['k12'] / ($weight * 0.45 * 9.8), 2) : 0;
                            //         $engineryDataDetailSub['average'] = $weight != 0 ? round($engineryDataDetail['k13'] / ($weight * 0.45 * 9.8), 2) : 0;
                            //     } else {
                            //         $engineryDataDetailSub['peak'] = $weight != 0 ? round($engineryDataDetail['k12'] / ($weight * 9.8), 2) : 0;
                            //         $engineryDataDetailSub['average'] = $weight != 0 ? round($engineryDataDetail['k13'] / ($weight * 9.8), 2) : 0;
                            //     }
                            // }
                        }
                    }
                }
                if (!empty($engineryDataDetailSub) && $ori_data['k9'] == $engineryDataDetailSub['action_type']) {
                    if ($ori_data['k12'] != 0 && ($ori_data['k15'] == 1 || $engineryDataDetailSub['peak'] == 0)) {
                        $engineryDataDetailSub['peak'] = $ori_data['k12'];
                        $engineryDataDetailSub['peak_source'] = $ori_data['k15'];
                    }
                    if ($ori_data['k13'] != 0 && ($ori_data['k16'] == 1 || $engineryDataDetailSub['average'] == 0)) {
                        $engineryDataDetailSub['average'] = $ori_data['k13'];
                        $engineryDataDetailSub['average_source'] = $ori_data['k16'];
                    }
                }
            }

            if (count($ori_detail1) == 1) {
                $mod_where['id'] = $ori_detail1[0]['id'];
            } else {
                $mod_where['id'] = ['in', array_column($ori_detail1, 'id')];
            }
            \think\Log::write('engineryDataUpdate:'.json_encode($mod_where));
            $db_mysql->name('equipment_process')->where($mod_where)->update(['is_del' => 1]);
            $engineryDataDetail1 = $engineryDataDetail;
            $engineryDataDetail1['equipment_result_id'] = $ori_detail1[0]['equipment_result_id'];
        } else {
            //数据主表是否存在
            //$ori_data = $this->equipmentResultModel->inquiryOne(['equipment_id'=>$engineryDataDetail['k18'],'date'=>$engineryDataDetail['k1'],'k1'=>$engineryDataDetail['k2']]);
            $ori_data = $db_mysql->name('equipment_result')->where(['equipment_id'=>$engineryDataDetail['k18'],'date'=>$engineryDataDetail['k1'],'k1'=>$engineryDataDetail['k2']])->find();
            if (!empty($ori_data)) {
                $engineryDataDetail1 = $engineryDataDetail;
                $engineryDataDetail1['equipment_result_id'] = $ori_data['id'];
            } else {

                //初始化主表
//                $uuid = guid();
//                $engineryData_ini = [
//                    'uuid' => $uuid,
//                    'device_id' => $engineryDataDetail['device_id'],
//                    'date' => $engineryDataDetail['date'],
//                    'group_id' => $engineryDataDetail['group_id'],
//                    'record_time' => $engineryDataDetail['record_time'],
//                    'file_name' => $engineryDataDetail['file_name'],
//                    'action_id' => $engineryDataDetail['action_id'],
//                    'action_name' => $engineryDataDetail['action_name'],
//                    'weight' => $engineryDataDetail['weight'],
//                    'weight_unit' => $engineryDataDetail['weight_unit'],
//                    'velocity_peak' => 0,
//                    'velocity_average' => 0,
//                    'power_peak' => 0,
//                    'power_average' => 0,
//                    'work_peak' => 0,
//                    'work_average' => 0,
//                    'work_total' => 0,
//                    'is_del' => 0,
//                    'create_by' => 'api',
//                    'create_time' => date('Y-m-d H:i:s'),
//                ];
                $engineryData_ini = [
                    'business' => $business,
                    'equipment_id' => $device_id,
                    'equipment_mark'=>'Eliteform',
                    'relation_type'=>13,
                    'date' => $date,
                    'record_time' => $record_time,
                    'k1' => $engineryDataDetail['k2'],
                    'k2' => $engineryDataDetail['k4'],
                    'k3' => $engineryDataDetail['k5'],
                    'k4' => $engineryDataDetail['k6'],
                    'k5' => $engineryDataDetail['k7'],
                    'k6' => $engineryDataDetail['k8'],
                    'k7' => 0,
                    'k8' => 0,
                    'k9' => 0,
                    'k10' => 0,
                    'k11' => 0,
                    'k12' => 0,
                    'k13' => 0,
                    'create_by' => 'api',
                    'create_time' => date('Y-m-d H:i:s')
                ];
                $engineryData_ini['mode'] = empty($engineryData_ini['k4']) ? '' : $engineryData_ini['k4'];
                $last_id1 = $db_mysql->name('equipment_result')->insertGetId($engineryData_ini);

                $engineryDataDetail1 = $engineryDataDetail;
                $engineryDataDetail1['equipment_result_id'] = $last_id1;
            }
        }
//        var_dump($engineryDataDetail);
//        var_dump($engineryDataDetail1);die;
        $detail_id = $this->equipmentProcessModel->add($engineryDataDetail);
        $detail_id1 = $db_mysql->name('equipment_process')->insertGetId($engineryDataDetail1);
//        var_dump($engineryDataDetailSub);die;
        \think\Log::write('engineryDataDetail:'.$detail_id1);

        if (!empty($engineryDataDetailSub)) {
            $engineryDataDetailSuba = [
                'equipment_result_id' => '',                    //data_uuid
                'k18' => $engineryDataDetailSub['device_id'],                            //device_id
                'k1' => $engineryDataDetailSub['date'],                                  //date
                'k2' => $engineryDataDetailSub['group_id'],                              //group_id
                'k3' => $engineryDataDetailSub['record_time'],                           //record_time
                'k4' => $engineryDataDetailSub['file_name'],                             //file_name
                'k5' => $engineryDataDetailSub['action_id'],                             //action_id
                'k6' => $engineryDataDetailSub['action_name'],                           //action_name
                'k7' => $engineryDataDetailSub['weight'],                                //weight
                'k8' => $engineryDataDetailSub['weight_unit'],                           //weight_unit
                'k9' => $engineryDataDetailSub['action_type'],//数据类型：1速度2功率3做功    //action_type
                'k10' => $engineryDataDetailSub['data_type'],//数据类型：PEAK/AVG          //data_type
                'k11' => $engineryDataDetailSub['rep'],                                   //rep
                'k12' => $engineryDataDetailSub['peak'],                                  //peak
                'k13' => $engineryDataDetailSub['average'],                              //average
                'k14' => $engineryDataDetailSub['ratio'],                                   //ratio
                'k15' => $engineryDataDetailSub['peak_source'],                          //peak_source
                'k16' => $engineryDataDetailSub['average_source'],                       //average_source
                'k17' => $engineryDataDetailSub['data_source'],                                     //data_source
                'is_del' => 0,
                'create_by' => 'api',
                'create_time' => date('Y-m-d H:i:s'),
            ];
            $engineryDataDetailSuba['equipment_result_id'] = $engineryDataDetail['equipment_result_id'];
            $last_id = $this->equipmentProcessModel->add($engineryDataDetailSuba);
            $engineryDataDetailSuba['equipment_result_id'] = $engineryDataDetail1['equipment_result_id'];
            $last_id1 = $db_mysql->name('equipment_process')->insertGetId($engineryDataDetailSuba);
            \think\Log::write('engineryDataDetailSub:'.$last_id1);
        }
        $res = [
            'data_id' => $engineryDataDetail1['equipment_result_id'],
            'detail_id' => $detail_id1
        ];
        //添加指标
        $this->correctEliteformData($res['data_id'], $db_mysql);
        
        return $res;
    }
    
    /**
     * 添加指标动作次数
     * @param type $result_id
     * @param type $db_mysql
     * @return boolean
     */
    public function correctEliteformData($result_id, $db_mysql) {
        $result = $db_mysql->name('equipment_result')->where(['id'=>$result_id])->find();
        if(empty($result)) {
            return false;
        }
        $max_rep = $db_mysql->name('equipment_process')
                ->field("equipment_result_id,max(k11) max_rep")
                ->group("equipment_result_id")
                ->where(['equipment_result_id'=>$result_id, 'is_del'=>0])
                ->find();
        if(!empty($max_rep['max_rep'])) {
            $db_mysql->name('equipment_result')->where(['id'=>$result_id])->update(['mode'=>$result['k4'], 'k14'=>$max_rep['max_rep']]);
        }
        return true;
    }
    
    //结果数据
    public function resultData($params, $db_mysql) {
        extract($params);
        //数据组装
        $velocity_peak = 0;
        $velocity_average = 0;
        $power_peak = 0;
        $power_average = 0;
        $work_peak = 0;
        $work_average = 0;
        $work_total = 0;
        //数据主表是否存在
        $where_common = [
            'equipment_id' => $device_id,
            'date' => $date,
            'k1' => $group_id,
            'is_del' => 0,
        ];
        $ori_data = $this->equipmentResultModel->inquiryOne($where_common);
        $ori_data1 = $db_mysql->name('equipment_result')->where($where_common)->find();
        //速度与功率数据算法互推 P=mgv
        switch ($action_type) {
            case 1:
                $velocity_peak = $v1;
                $velocity_average = $v2;
                if(!empty($ori_data1))
                {
                    if(!$ori_data1['k9'])
                    {
                        if ($weight_unit == 'lb') {
                            $power_peak = round($velocity_peak * $weight * 0.45 * 9.8, 2);
                            $power_average = round($velocity_average * $weight * 0.45 * 9.8, 2);
                        } else {
                            $power_peak = round($velocity_peak * $weight * 9.8, 2);
                            $power_average = round($velocity_average * $weight * 9.8, 2);
                        }
                    }
                }

                break;
            case 2:
                $power_peak = $v1;
                $power_average = $v2;
                if(!empty($ori_data1))
                {
                    if(!$ori_data1['k7'])
                    {
//                        if ($weight_unit == 'lb') {
//                            $velocity_peak = $weight != 0 ? round($power_peak / ($weight * 0.45 * 9.8), 2) : 0;
//                            $velocity_average = $weight != 0 ? round($power_average / ($weight * 0.45 * 9.8), 2) : 0;
//                        } else {
//                            $velocity_peak = $weight != 0 ? round($power_peak / ($weight * 9.8), 2) : 0;
//                            $velocity_average = $weight != 0 ? round($power_average / ($weight * 9.8), 2) : 0;
//                        }
                    }
                }
                break;
            case 3:
                $work_peak = $v1;
                $work_average = $v2;
                $work_total = $v3;
                break;
            default:
                break;
        }
//        $engineryData = [
//            'uuid' => '',
//            'device_id' => $device_id,
//            'date' => $date,
//            'group_id' => $group_id,
//            'record_time' => $record_time,
//            'file_name' => $file_name,
//            'action_id' => $action_id,
//            'action_name' => $action_name,
//            'weight' => $weight,
//            'weight_unit' => $weight_unit,
//            'velocity_peak' => $velocity_peak,
//            'velocity_average' => $velocity_average,
//            'power_peak' => $power_peak,
//            'power_average' => $power_average,
//            'work_peak' => $work_peak,
//            'work_average' => $work_average,
//            'work_total' => $work_total,
//            'is_del' => 0,
//            'create_by' => 'api',
//            'create_time' => date('Y-m-d H:i:s'),
//        ];

        $engineryData = [
            'business' => $business,
            'equipment_id' => $device_id,
            'equipment_mark'=>'Eliteform',
            'relation_type'=>13,
            'date' => $date,
            'record_time' => $record_time,
            'k1' => $group_id,
            'k2' => $file_name,
            'k3' => $action_id,
            'k4' => $action_name,
            'k5' => $weight,
            'k6' => $weight_unit,
            'k7' => $velocity_peak,
            'k8' => $velocity_average,
            'k9' => $power_peak,
            'k10' => $power_average,
            'k11' => $work_peak,
            'k12' => $work_average,
            'k13' => $work_total,
            'create_by' => 'api',
        ];
        if (!empty($ori_data)) {
            $engineryDataUpate['update_time'] = date('Y-m-d H:i:s');
            $item_list = ['record_time','k3','k4','k5','k6'];
            $item_value_list = ['k7','k8','k9','k10','k11','k12','k13'];
            foreach ($item_list as $item) {
                if ($ori_data[$item] != $engineryData[$item]) {
                    $engineryDataUpate[$item] = $engineryData[$item];
                }
            }
            foreach ($item_value_list as $item) {
                if ($ori_data[$item] != $engineryData[$item] && $engineryData[$item] != 0) {
                    $engineryDataUpate[$item] = $engineryData[$item];
                }
            }
            \think\Log::write('engineryDataUpate:'.$ori_data['id']);
            $engineryDataUpate['mode'] = empty($engineryDataUpate['k4']) ? '' : $engineryDataUpate['k4'];
            $this->equipmentResultModel->modifyById($ori_data['id'], $engineryDataUpate);
            $engineryData['id'] = $ori_data['id'];
        } else {
            $engineryData['mode'] = empty($engineryData['k4']) ? '' : $engineryData['k4'];
            $last_id = $this->equipmentResultModel->add($engineryData);
            $engineryData['id'] = $last_id;
            \think\Log::write('engineryData:'.$last_id);
        }
        //业务系统数据处理
        $engineryData1 = $engineryData;
        if (!empty($ori_data1)) {
            $engineryDataUpate1['update_time'] = date('Y-m-d H:i:s');
            $item_list = ['record_time','k3','k4','k5','k6'];
            $item_value_list = ['k7','k8','k9','k10','k11','k12','k13'];
            foreach ($item_list as $item) {
                if ($ori_data1[$item] != $engineryData1[$item]) {
                    $engineryDataUpate1[$item] = $engineryData1[$item];
                }
            }
            foreach ($item_value_list as $item) {
                if ($ori_data1[$item] != $engineryData1[$item] && $engineryData1[$item] != 0) {
                    $engineryDataUpate1[$item] = $engineryData1[$item];
                }
            }
            \think\Log::write('engineryDataUpate:'.$ori_data1['id']);
            $engineryDataUpate1['mode'] = empty($engineryDataUpate1['k4']) ? '' : $engineryDataUpate1['k4'];
            $db_mysql->name('equipment_result')->where(['id'=>$ori_data1['id']])->update($engineryDataUpate1);
            $engineryData1['id'] = $ori_data1['id'];
        } else {
            unset($engineryData1['id']);
            $engineryData1['mode'] = empty($engineryData1['k4']) ? '' : $engineryData1['k4'];
            $last_id1 = $db_mysql->name('equipment_result')->insertGetId($engineryData1);
            $engineryData1['id'] = $last_id1;
            \think\Log::write('engineryData:'.$last_id1);
        }

        //过程数据
//        var_dump($detail_list);die;
        if (!empty($detail_list)) {
            //全部详情
            $details = $this->equipmentProcessModel->inquiryAll(['equipment_result_id' => $engineryData['id'], 'is_del' => 0]);
            $details_list = [];
            if (!empty($details)) {
                foreach ($details as $detail) {
                    $details_list[$detail['k9'].'_'.$detail['k11']] = $detail;
                }
            }
            //数据来源：优先级 1过程上传 > 2过程推算 > 3结果矫正 > 空
            foreach ($detail_list as $detail) {
                $peak = $detail['peak'];
                $average = $detail['average'];
                if ($action_type == 3) {
                    $peak = $detail['average'];
                    $average = 0;
                }
//                $engineryDataDetail = [
//                    'data_uuid' => $engineryData['uuid'],
//                    'device_id' => $engineryData['device_id'],
//                    'date' => $engineryData['date'],
//                    'group_id' => $engineryData['group_id'],
//                    'record_time' => $engineryData['record_time'],
//                    'file_name' => $engineryData['file_name'],
//                    'action_id' => $engineryData['action_id'],
//                    'action_name' => $engineryData['action_name'],
//                    'weight' => $engineryData['weight'],
//                    'weight_unit' => $engineryData['weight_unit'],
//                    'action_type' => $action_type,//数据类型：1速度2功率3做功
//                    'data_type' => $data_type,//数据类型：PEAK/AVG
//                    'rep' => $detail['rep'],
//                    'peak' => $peak,
//                    'average' => $average,
//                    'ratio' => 0,
//                    'peak_source' => 3,
//                    'average_source' => 3,
//                    'data_source' => 3,
//                    'is_del' => 0,
//                    'create_by' => 'api',
//                    'create_time' => date('Y-m-d H:i:s'),
//                ];
                $engineryDataDetail = [
                    'equipment_result_id' => $engineryData['id'],                   //data_uuid
                    'k18' => $engineryData['equipment_id'],                         //device_id
                    'k1' => $engineryData['date'],                                  //date
                    'k2' => $engineryData['k1'],                                    //group_id
                    'k3' => $engineryData['record_time'],                           //record_time
                    'k4' => $engineryData['k2'],                                    //file_name
                    'k5' => $engineryData['k3'],                             //action_id
                    'k6' => $engineryData['k4'],                           //action_name
                    'k7' => $engineryData['k5'],                                //weight
                    'k8' => $engineryData['k6'],                           //weight_unit
                    'k9' => $action_type,//数据类型：1速度2功率3做功    //action_type
                    'k10' => $data_type,//数据类型：PEAK/AVG          //data_type
                    'k11' => $detail['rep'],                                   //rep
                    'k12' => $peak,                                  //peak
                    'k13' => $average,                              //average
                    'k14' => 0,                                   //ratio
                    'k15' => 3,                          //peak_source
                    'k16' => 3,                       //average_source
                    'k17' => 3,                                     //data_source
                    'is_del' => 0,
                    'create_by' => 'api',
                    'create_time' => date('Y-m-d H:i:s'),
                ];

                $details_list_key = $engineryDataDetail['k9'].'_'.$engineryDataDetail['k11'];
                $ori_data_detail = !empty($details_list[$details_list_key]) ? $details_list[$details_list_key] : null;
                if (!empty($ori_data_detail)) {
                    if ($ori_data_detail['k12'] != 0 && (in_array($ori_data_detail['k15'], [1, 2]) || $engineryDataDetail['k12'] == 0)) {
                        $engineryDataDetail['k12'] = $ori_data_detail['k12'];
                        $engineryDataDetail['k15'] = $ori_data_detail['k15'];
                    }
                    if ($ori_data_detail['k13'] != 0 && (in_array($ori_data_detail['k16'], [1, 2]) || $engineryDataDetail['k13'] == 0)) {
                        $engineryDataDetail['k13'] = $ori_data_detail['k13'];
                        $engineryDataDetail['k16'] = $ori_data_detail['k16'];
                    }
                    if ($ori_data_detail['k14'] != 0) {
                        $engineryDataDetail['k14'] = $ori_data_detail['k14'];
                    }
                    \think\Log::write('ori_data_detail:'.$ori_data_detail['id']);
                    $this->equipmentProcessModel->modifyById($ori_data_detail['id'], ['is_del' => 1]);
                }
                $last_id = $this->equipmentProcessModel->add($engineryDataDetail);
                \think\Log::write('engineryDataDetail:'.$last_id);
            }
        }
        if (!empty($detail_list)) {
            //全部详情
            $details = $db_mysql->name('equipment_process')->where(['equipment_result_id' => $engineryData1['id'], 'is_del' => 0])->select();
            $details_list = [];
            if (!empty($details)) {
                foreach ($details as $detail) {
                    $details_list[$detail['k9'].'_'.$detail['k11']] = $detail;
                }
            }
            //数据来源：优先级 1过程上传 > 2过程推算 > 3结果矫正 > 空
            foreach ($detail_list as $detail) {
                $peak = $detail['peak'];
                $average = $detail['average'];
                if ($action_type == 3) {
                    $peak = $detail['average'];
                    $average = 0;
                }
                $engineryDataDetail = [
                    'equipment_result_id' => $engineryData1['id'],                   //data_uuid
                    'k18' => $engineryData1['equipment_id'],                         //device_id
                    'k1' => $engineryData1['date'],                                  //date
                    'k2' => $engineryData1['k1'],                                    //group_id
                    'k3' => $engineryData1['record_time'],                           //record_time
                    'k4' => $engineryData1['k2'],                                    //file_name
                    'k5' => $engineryData1['k3'],                                    //action_id
                    'k6' => $engineryData1['k4'],                                    //action_name
                    'k7' => $engineryData1['k5'],                                    //weight
                    'k8' => $engineryData1['k6'],                                    //weight_unit
                    'k9' => $action_type,//数据类型：1速度2功率3做功                    //action_type
                    'k10' => $data_type,//数据类型：PEAK/AVG                          //data_type
                    'k11' => $detail['rep'],                                        //rep
                    'k12' => $peak,                                                 //peak
                    'k13' => $average,                                              //average
                    'k14' => 0,                                                     //ratio
                    'k15' => 3,                                                     //peak_source
                    'k16' => 3,                                                     //average_source
                    'k17' => 3,                                                     //data_source
                    'is_del' => 0,
                    'create_by' => 'api',
                    'create_time' => date('Y-m-d H:i:s'),
                ];

                $details_list_key = $engineryDataDetail['k9'].'_'.$engineryDataDetail['k11'];
                $ori_data_detail = !empty($details_list[$details_list_key]) ? $details_list[$details_list_key] : null;
                if (!empty($ori_data_detail)) {
                    if ($ori_data_detail['k12'] != 0 && (in_array($ori_data_detail['k15'], [1, 2]) || $engineryDataDetail['k12'] == 0)) {
                        $engineryDataDetail['k12'] = $engineryDataDetail['k12']?$engineryDataDetail['k12']:$ori_data_detail['k12'];
                        $engineryDataDetail['k15'] = $ori_data_detail['k15'];
                    }
                    if ($ori_data_detail['k13'] != 0 && (in_array($ori_data_detail['k16'], [1, 2]) || $engineryDataDetail['k13'] == 0)) {
                        $engineryDataDetail['k13'] = $engineryDataDetail['k13']?$engineryDataDetail['k13']:$ori_data_detail['k13'];
                        $engineryDataDetail['k16'] = $ori_data_detail['k16'];
                    }
                    if ($ori_data_detail['k14'] != 0) {
                        $engineryDataDetail['k14'] = $ori_data_detail['k14'];
                    }
                    \think\Log::write('ori_data_detail:'.$ori_data_detail['id']);
                    $db_mysql->name('equipment_process')->where(['id' => $ori_data_detail['id']])->update(['is_del' => 1]);
                }
                $last_id = $db_mysql->name('equipment_process')->insertGetId($engineryDataDetail);
                \think\Log::write('engineryDataDetail:'.$last_id);
            }
        }
        $res = [
            'data_id' => $engineryData1['id'],
            'detail_id' => 0
        ];
        
        //添加指标
        $this->correctEliteformData($res['data_id'], $db_mysql);
        return $res;
    }

    /**
     * 仅上传图片
     * @throws Exception
     */
    public function addFile($file, $enginery_band = 'eliteform') {
        try {
            //上传文件
            $file_path = 'import'.DS.$enginery_band;
            $file_info = $this->fileModel->uploadFile($file, $file_path, true);
            if ($file_info['code']) {
                throw new Exception($file_info['message']);
            }
            $file_path_img = $file_info['data'];
            $fileSaveName = $file->getinfo("name");
            $img = $this->engineryDataImgModel->inquiryOne(['file_name' => $fileSaveName, 'is_del' => 0], ['id' => 'desc']);
            if (!empty($img)) {
                $this->engineryDataImgModel->modifyById($img['id'], ['file_path' => $file_path_img]);
            }
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '上传成功', 'data'=> []];
    }

    /**
     * 机能设备数据上传
     */
    public function engineryData($business, $data) {
        try {
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception("用户不存在");
            }
            $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
            $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
            $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';
            $mysql_username = !empty($business_config['mysql_username']) ? $business_config['mysql_username'] : '';
            $mysql_password= !empty($business_config['mysql_password']) ? $business_config['mysql_password'] : '';
            $db_mysql = get_db_mysql($mysql_database, $mysql_prefix, $mysql_hostname, $mysql_username, $mysql_password);

            $device_code = !empty($data['device_code']) ? $data['device_code'] : '';//设备唯一代号
            $record_time = !empty($data['record_time']) ? $data['record_time'] : date('Y-m-d H:i:s');//测试时间
            $date = date('Y-m-d', strtotime($record_time));
            $ybh = !empty($data['ybh']) ? $data['ybh'] : '';//样本号
            $xmdh = !empty($data['xmdh']) ? $data['xmdh'] : '';//测试项
            $csjg = !empty($data['csjg']) ? $data['csjg'] : '';//测试结果
            if (empty($device_code) || empty($record_time) || empty($ybh) || empty($xmdh) || empty($csjg)) {
                \think\Log::write('测试数据不完善');
            }
            //用户仪器对应设备
            $device = $db_mysql->name('device_manage')->where('ruimei_yq', $device_code)->find();
            if (empty($device)) {
                throw new Exception('用户设备不存在');
            }
            /*
             * 统一字段
            [k1] => 111-------------------用户系统对应样本号 ydy_specimen.specimen_no
            [k2] => CNTR_C----------------瑞美仪器代号 ydy_device_manage.ruimei_yq
            [k3] => 20231012104-----------仪器样本号
            [k4] => 2023-10-12 10:34:59---测试时间 ydy_equipment_result.record_time
            */
            $item = [
                'business' => $business,
                'equipment_id' => $device['id'],
                'department_uuid' => '',
                'staff_uuid' => '',
                'date' => $date,
                'record_time' => $record_time,
                'reference' => App::$trace_id,
                'k1' => '',
                'k2' => $device_code,
                'k3' => $ybh,
                'k4' => $record_time,
                'create_by' => 'api',
            ];
            $relation_type = config('relation_type');
            if (empty($relation_type[$device_code])) {
                throw new Exception('设备数据关系不存在');
            }
            //设备-测试项
            $relations = $this->equipmentRelationModel->inquiryAll(['type' => $relation_type[$device_code], 'is_del' => 0]);
            $v_indexs = [];
            $relation_list = [];
            foreach ($relations as $ritem) {
                $v_index = trim($ritem['v'],'k');
                $v_indexs[] = $v_index;
                $ritem['v_index'] = $v_index;
                $relation_list[$ritem['k']] = $ritem;
            }
            $relation = !empty($relation_list[$xmdh]) ? $relation_list[$xmdh] : '';
            if (empty($relation)) {
                \think\Log::write(sprintf('设备测试参数与扩展表对应关系缺少参数：type:%s;k:%s',$relation_type[$device_code], $xmdh));
                //组装字段
                $v_index_new = max($v_indexs) + 1;
                $miss_v = 'k'.$v_index_new;
                $miss_param = [
                    'type' => $relation_type[$device_code],
                    'k' => $xmdh,
                    'v' => $miss_v
                ];
                //插入字段
                $this->equipmentRelationModel->name('equipment_relation')->insertGetId($miss_param);
                $db_mysql->name('equipment_relation')->insertGetId($miss_param);
                //更新数据
                $miss_param['v_index'] = $v_index_new;
                $relation = $miss_param;

                $content = '设备测试参数对应关系缺少字段确认是否添加成功' . PHP_EOL;
                $content .= $business_config['name'] . PHP_EOL;
                $content .= 'time:' . date('Y-m-d H:i:s') . PHP_EOL;
                $content .= 'miss_params:' . json_encode($miss_param);
                $params = array(
                    'msgtype' => 'text',
                    'text' => array(
                        'content' => $content,
                        'mentioned_mobile_list' => array(
                            '15811137696'
                        )
                    ),
                );
                \app\common\QwRobot::pushMsg($params);
            }
            $result_extend = [];
            if ($relation['v_index'] > 20) {
                $result_extend[$relation['v']] = $csjg;
            } else {
                $item[$relation['v']] = $csjg;
            }
            //当前设备-当前仪器样本号是否存在-存在即更新-不存在再新增
            $result_where = [
                'business' => $item['business'],
                'equipment_id' => $item['equipment_id'],
                'date' => $item['date'],
                'k3' => $item['k3'],
                'is_del' => 0
            ];
            //数据中心数据库
            $db = get_db();
            $current_result = $db->name('equipment_result')->where($result_where)->find();
            $push_msg = false;
            if (empty($current_result)) {
                $push_msg = true;
                $current_specimen = $this->get_current_specimen($db_mysql, $item);
                if (!empty($current_specimen)) {
                    $item['k1'] = $current_specimen['specimen_no'];
                    $item['staff_uuid'] = $current_specimen['staff_uuid'];
                    $item['department_uuid'] = $current_specimen['department_uuid'];
                }
                //插入数据中心数据库主表
                $mac_last_id = $db->name('equipment_result')->insertGetId($item);
                //插入用户数据库主表
                $item['reference']  = $mac_last_id;
                $last_id = $db_mysql->name('equipment_result')->insertGetId($item);
                if (!empty($result_extend)) {
                    //插入数据中心数据库扩展表
                    $result_extend['equipment_result_id'] = $mac_last_id;
                    $db->name('equipment_result_extend')->insertGetId($result_extend);
                    //插入用户数据库扩展表
                    $result_extend['equipment_result_id'] = $last_id;
                    $db_mysql->name('equipment_result_extend')->insertGetId($result_extend);
                }
            } else {
                $mac_last_id = $current_result['id'];
                unset($item['reference']);
                //查询一下当前样本号
                $current_specimen_where = [
                    'test_date' => $item['date'],
                    'is_del' => 0,
                    'specimen_no' => $current_result['k1'],
                ];
                $current_specimen = $db_mysql->name('specimen')->where($current_specimen_where)->find();
                if (!empty($current_specimen)) {
                    $item['k1'] = $current_specimen['specimen_no'];
                    $item['staff_uuid'] = $current_specimen['staff_uuid'];
                    $item['department_uuid'] = $current_specimen['department_uuid'];
                }
                //数据更新-不一样的数据
                //主数据需要更新
                $result_update = [];
                $extend_update = [];
                foreach ($item as $column => $value) {
                    if ($value != $current_result[$column]) {
                        $result_update[$column] = $value;
                    }
                }
                //扩展数据需要更新
                $current_extend = [];
                if (!empty($result_extend)) {
                    $current_extend = $db->name('equipment_result_extend')->where('equipment_result_id', $current_result['id'])->find();
                    foreach ($result_extend as $column => $value) {
                        if ($value != $current_extend[$column]) {
                            $extend_update[$column] = $value;
                        }
                    }
                }
                //查询用户数据
                $result_where_user = [
                    'business' => $item['business'],
                    'equipment_id' => $item['equipment_id'],
                    'reference' => $current_result['id']
                ];
                $current_result_user = $db_mysql->name('equipment_result')->where($result_where_user)->find();
                $current_extend_user = $db_mysql->name('equipment_result_extend')->where('equipment_result_id', $current_result_user['id'])->find();
                if (!empty($result_update)) {
                    //更新数据中心数据库主表
                    $db->name('equipment_result')->where('id', $current_result['id'])->update($result_update);
                    //更新用户数据库主表
                    $db_mysql->name('equipment_result')->where('id', $current_result_user['id'])->update($result_update);
                }
                if (!empty($extend_update)) {
                    //更新数据中心数据库扩展表
                    $db->name('equipment_result_extend')->where('id', $current_extend['id'])->update($extend_update);
                    //更新用户数据库扩展表
                    $db_mysql->name('equipment_result_extend')->where('id', $current_extend_user['id'])->update($extend_update);
                }
            }
            if (!empty($current_specimen)) {
                //更新样本号状态
                $update_specimen = [
                    'last_time' => $item['record_time'],
                ];
                if ($current_specimen['status'] == 0) {
                    $update_specimen['first_time'] = $item['record_time'];
                    $update_specimen['status'] = 1;
                }
                $db_mysql->name('specimen')->where('id', $current_specimen['id'])->update($update_specimen);
            }
            //同步成功消息
            if ($push_msg) {
                $staff_info = null;
                if (!empty($item['staff_uuid'])) {
                    $staff_info = $db_mysql->name('staff')->where('uuid', $item['staff_uuid'])->find();
                }
                $msg = [
                    '设备' => $device['name'],
                    '人员' => !empty($staff_info['name']) ? $staff_info['name'] : '',
                    '人员uuid' => $item['staff_uuid'],
                    '仪器样本号' => $item['k3'],
                    '系统样本号' => $item['k1'],
                    'mac_last_id' => $mac_last_id,
                ];
                \app\common\QwRobot::pushMsgFormat($msg, $business_config['name']. '机能设备数据同步成功');
            }
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '上传成功', 'data'=> []];
    }

    //获取对应的样本号
    public function get_current_specimen($db, $item) {
        $specimen_where = [
            'test_date' => $item['date'],
            'is_del' => 0,
        ];
        $specimens = $db->name('specimen')
            ->where($specimen_where)
            ->field('*')
            ->order(['test_time' => 'asc', 'specimen_no' => 'asc'])
            ->select();
        if (!empty($specimens)) {
            $specimen_no = array_column($specimens, 'specimen_no');
            $specimen_list = array_column($specimens, null, 'specimen_no');
        } else {
            $specimen_no = [];
            $specimen_list = [];
        }
        if (empty($specimen_no)) {
            return null;
        }
        //当前仪器的上一个样本号信息
        $result_where = [
            'business' => $item['business'],
            'equipment_id' => $item['equipment_id'],
            'date' => $item['date'],
            'k1' => ['<>', ''],//非空
            'is_del' => 0
        ];
        $last_specimen = $db->name('equipment_result')
            ->where($result_where)
            ->field('k1')
            ->order('id', 'desc')
            ->limit(1)
            ->find();
        if (empty($last_specimen)) {
            $current_specimen_no = $specimen_no[0];
        } else {
            $last_index = array_search($last_specimen['k1'], $specimen_no);
            $current_specimen_no = !empty($specimen_no[$last_index + 1]) ? $specimen_no[$last_index + 1] : '';
        }
        $current_specimen = null;
        if (!empty($specimen_list[$current_specimen_no])) {
            $current_specimen = $specimen_list[$current_specimen_no];
        }
        return $current_specimen;
    }

    public function pipelinelog($data) {
        return $this->pipelineLogModel->insert($data);
    }
}
