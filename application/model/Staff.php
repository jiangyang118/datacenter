<?php

namespace app\model;

use app\model\BaseModel;
use think\Db;
use think\Exception;

class Staff extends BaseModel{

    protected $connection;
    public function __construct($Db = array())
    {
        parent::__construct($Db);
        if (!empty($Db)){
            $this->connection = $Db;
        }
    }
    //根据姓名查询人员，如果不存在就新增
    public function getStaffCreate($username, $sex,$business_config) {
        $staff_info = $this->connection->name('staff')->where(['name'=> $username, 'is_show' => 1])->find();
        if (empty($staff_info)) {
            //人员不存在，创建人员
            $personnel_umber = $this->getCode();
            $uuid = guid();
            $params = [
                'name' => $username,
                'sex' => $sex,
                'birthday' => date('Y-m-d'),
                'station_uuid' => $business_config['createStaff']['station_uuid'],
                'personnel_umber' => $personnel_umber['data'],
                'is_out' => 0,
                'is_athlete' => 1,
                'uuid' => $uuid,
                'age' => get_age(date('Y-m-d')),
                'create_time' => date('Y-m-d H:i:s'),
                'create_by' => 'system',
                'team_status' => 100,//临时
            ];
            // 人员部门关联表
            $department_params = [
                'department_uuid' => $business_config['createStaff']['department_uuid'],
                'staff_uuid' => $uuid,
                'create_time' => date('Y-m-d H:i:s'),
                'create_by' => 'system'
            ];
            try {
                $this->connection->startTrans();
                $res = $this->connection->name('staff')->insert($params);
                if (!$res) {
                    return array("code" => 1, "message" => "创建人员失败", "data"=> []);
                }
                //添加组织人员与部门关系表
                $staff_department_result = $this->connection->name('staff_department')->insert($department_params);
                if (!$staff_department_result) {
                    throw new Exception('添加组织人员与部门关系失败');
                }
                //获取department_uuid   department 修改为子节点
                $department_uuid = array_column($department_params,'department_uuid');
                $department = $this->connection->name('department')->whereIn('uuid',$department_uuid)->update(['leaf_flag' => 1]);
                if ($department === false) {
                    throw new Exception('修改部门失败');
                }
                //修改岗位引用人数
                $station_result = $this->connection->name('station')->where(['uuid' => $params['station_uuid']])->setInc('quantity',1);
                if ($station_result === false) {
                    throw new Exception('修改岗位人数+1失败');
                }
                $staff_info['uuid'] = $uuid;
                $staff_info['department_uuid'] = $business_config['createStaff']['department_uuid'];
                $this->connection->commit();
            }catch (Exception $e){
                $this->connection->rollback();
                return array("code" => 0, "message" => $e->getMessage(), "data"=> []);
            }
        } else {
            //查询部门
            $staff_department = $this->connection->name('staff_department')->where(['staff_uuid' => $staff_info['uuid']])->field('department_uuid')->find();
            $staff_info['department_uuid'] = !empty($staff_department['department_uuid']) ? $staff_department['department_uuid'] : '';
        }
        return array("code" => 0, "message" => "创建人员成功", "data"=> $staff_info);
    }

    public function getCode()
    {
        $max = $this->connection->name('staff')->field("max(personnel_umber) personnel_umber")->find();
        if (empty($max['personnel_umber'])) {
            $personnel_umber = "0001";
        } else {
            $personnel_umber = str_pad(intval($max['personnel_umber']) + 1, 4, 0, STR_PAD_LEFT);
        }
        return ['code' => 0, 'message' => '获取成功', 'data' => $personnel_umber];
    }

    //根据姓名查询人员，如果不存在就新增-体能系统
    public function getStaffCreateTn($username, $sex, $business_config, $height = 0, $weight = 0, $birthday = '') {
        $staff_info = $this->connection->name('staff')->where(['name'=> $username, 'del_flag' => 0])->find();
        if (empty($staff_info)) {
            //人员不存在，创建人员
            $uuid = guid();
            $params = [
                'name' => $username,
                'sex' => $sex,
                'birthday' => date('Y-m-d'),
                'station_uuid' => $business_config['createStaff']['station_uuid'],
                'is_out' => 0,
                'is_athlete' => 1,
                'uuid' => $uuid,
                'age' => get_age(date('Y-m-d')),
                'create_time' => date('Y-m-d H:i:s'),
                'create_by' => 'system',
            ];
            if (!empty($height)) {
                $params['height'] = $height;
            }
            if (!empty($weight)) {
                $params['weight'] = $weight;
                $params['weight_date'] = date("Y-m-d");
            }
            if (!empty($birthday)) {
                $params['birthday'] = $birthday;
                $params['age'] = get_age($birthday);
            }
            //增加人员编号
            $department = $this->connection->name('department')->where(['uuid'=> $business_config['createStaff']['department_uuid']])->find();
            $department_code = !empty($department['code']) ? $department['code'] : '';
            //队伍下最大编号值，再增加
            $staff_code_arr = array();
            $staff_code_res = $this->connection->name('staff')->where(['del_flag'=>0])->column('code');
            foreach ($staff_code_res as $code) {
                $staff_code = substr((string)$code, -3);
                if ($staff_code) {
                    if (strlen((string)$code) > 6) {//部分数大于100
                        $department_code_val = substr((string)$code, 0, 3);
                    } else {
                        $department_code_val = substr((string)$code, 0, 2);
                    }
                    if ($department_code_val == $department_code) {
                        $staff_code_arr[] = $staff_code;
                    }
                }
            }
            $staff_code = $staff_code_arr ? max($staff_code_arr) + 1 : 1;
            $code = $department_code . ($params['sex']-1) . str_pad($staff_code, 3, '0', STR_PAD_LEFT);
            $params['code'] = $code;
            // 人员部门关联表
            $department_params = [
                'department_uuid' => $business_config['createStaff']['department_uuid'],
                'staff_uuid' => $uuid,
                'create_time' => date('Y-m-d H:i:s'),
                'create_by' => 'system'
            ];
            try {
                $this->connection->startTrans();
                $res = $this->connection->name('staff')->insert($params);
                if (!$res) {
                    return array("code" => 1, "message" => "创建人员失败", "data"=> []);
                }
                //添加组织人员与部门关系表
                $staff_department_result = $this->connection->name('staff_department')->insert($department_params);
                if (!$staff_department_result) {
                    throw new Exception('添加组织人员与部门关系失败');
                }
                //获取department_uuid   department 修改为子节点
                $department_uuid = array_column($department_params,'department_uuid');
                $department = $this->connection->name('department')->whereIn('uuid',$department_uuid)->update(['leaf_flag' => 1]);
                if ($department === false) {
                    throw new Exception('修改部门失败');
                }
                //修改岗位引用人数
                $station_result = $this->connection->name('station')->where(['uuid' => $params['station_uuid']])->setInc('quantity',1);
                if ($station_result === false) {
                    throw new Exception('修改岗位人数+1失败');
                }
                $staff_info['uuid'] = $uuid;
                $staff_info['department_uuid'] = $business_config['createStaff']['department_uuid'];
                $this->connection->commit();
            }catch (Exception $e){
                $this->connection->rollback();
                return array("code" => 0, "message" => $e->getMessage(), "data"=> []);
            }
        } else {
            //查询部门
            $staff_department = $this->connection->name('staff_department')->where(['staff_uuid' => $staff_info['uuid']])->field('department_uuid')->find();
            $staff_info['department_uuid'] = !empty($staff_department['department_uuid']) ? $staff_department['department_uuid'] : '';
        }
        return array("code" => 0, "message" => "创建人员成功", "data"=> $staff_info);
    }
}