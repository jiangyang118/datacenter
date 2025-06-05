<?php

namespace app\api\logic;
use app\model\EquipmentResult;
use think\Exception;
use app\model\EquipmentRelation as EquipmentRelationModel;
use app\model\EquipmentResult as EquipmentResultModel;
use app\model\EquipmentProcess as EquipmentProcessModel;

class Keiser {
    private $EquipmentRelationModel;
    private $EquipmentResultModel;
    private $EquipmentProcessModel;

    public function __construct() {
        $this->EquipmentRelationModel = new EquipmentRelationModel();
        $this->EquipmentResultModel = new EquipmentResultModel();
        $this->EquipmentProcessModel = new EquipmentProcessModel();
    }

    /**
     * 同步数据
     * @throws Exception
     */
    public function add($data, $business, $equipment_id) {
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
            //数据中心数据库
            //$db = get_db();
            //山西数据库
//            $db_mysql = get_db($mysql_database, $mysql_prefix);
            $relation_data = $this->EquipmentRelationModel->inquiryColumn(['is_del'=>0,'type'=>5001],'k,v');
            unset($relation_data['set']);
            unset($relation_data['date']);
            $insert = [];
            foreach($data as $sets)
            {
                $result = $sets['sets'];
                if(count($result) > 0)
                {
                    foreach($result as $value)
                    {
                        $insert_data1 = [];
                        $insert_data = [];
                        foreach($relation_data as $relation_key=>$relation_value)
                        {
                            $insert_data[$relation_value] = $value[$relation_key];
                        }
                        $timestamp = strtotime($value['time']);
                        $date = date('Y-m-d', $timestamp);
                        $time = date('H:i:s', $timestamp);

                        $insert_data1['equipment_id'] = $equipment_id;
                        $insert_data1['business'] = $business;
                        $insert_data1['record_time'] = date('Y-m-d H:i:s', $timestamp);

                        $relation_data_count = $this->EquipmentResultModel->inquiryCount($insert_data1);
                        if($relation_data_count)
                            continue;

                        $insert_data1['date'] = $date;
                        $data_insert_id = $this->EquipmentResultModel->add($insert_data1);
                        $data_insert_id1 = $db_mysql->name('equipment_result')->insertGetId($insert_data1);
//                        $insert_data1['equipment_id'] = $equipment_id;
//                        $insert_data1['equipment_id'] = $equipment_id;


                        $insert_data['equipment_result_id'] = $data_insert_id;
                        $insert_data['k2'] = $date;
                        $insert_data['k3'] = $time;
                        //$insert_data['date'] = $date;
                        //$insert_data['record_time'] = $date.' '.$time;
                        //$insert_data['business'] = $business;//前端传
                        //$insert_data['equipment_id'] = $equipment_id;


                        //var_dump($insert_data);die;
                        //判断是否重复插入
                        //$is_insert = $this->EquipmentProcessModel->inquiryCount($insert_data);
                        //var_dump($is_insert);die;
//                        if($is_insert == 0)
//                        {

                        $insert_data['k1'] = 1;//组的概念
                        $insert[] = $insert_data;
                        $insert_data['equipment_result_id'] = $data_insert_id1;
                        $insert1[] = $insert_data;

//                        }
                    }
                }
            }
            if(count($insert))
            {
                $this->EquipmentResultModel->startTrans();
                $res = $this->EquipmentProcessModel->addAll($insert);
                if(!$res)
                    throw new Exception("数据中心过程存储失败");

                $res1 = $db_mysql->name('equipment_process')->insertAll($insert1);
                if(!$res1)
                    throw new Exception("客户过程存储失败");
                $this->EquipmentResultModel->commit();
            }
        } catch (Exception $ex) {
            $this->EquipmentResultModel->rollback();
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '同步成功', 'data'=>[]];
    }
}
