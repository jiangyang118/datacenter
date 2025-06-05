<?php

namespace app\api\logic;
use think\App;
use think\Config;
use think\Exception;
use app\model\EquipmentRelation as EquipmentRelationModel;
use app\model\EquipmentResult as EquipmentResultModel;
use app\model\EquipmentProcess as EquipmentProcessModel;

class Channel {
    private $equipmentResultModel;

    public function __construct() {
        $this->equipmentResultModel = new EquipmentResultModel();
    }

    private function set_db_config($business_config) {
        $db = Config::get('database');
        $db['database'] = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : $db['database'];
        $db['prefix'] = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : $db['prefix'];
        $db['hostname'] = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : $db['hostname'];
        $db['sqlsrv']['hostname'] = !empty($business_config['sqlsrv_hostname']) ? $business_config['sqlsrv_hostname'] : $db['sqlsrv']['hostname'];
        $db['sqlsrv']['database'] = !empty($business_config['sqlsrv_database']) ? $business_config['sqlsrv_database'] : $db['sqlsrv']['database'];
        $db['sqlsrv']['username'] = !empty($business_config['sqlsrv_username']) ? $business_config['sqlsrv_username'] : $db['sqlsrv']['username'];
        $db['sqlsrv']['password'] = !empty($business_config['sqlsrv_password']) ? $business_config['sqlsrv_password'] : $db['sqlsrv']['password'];
        Config::set('database', $db);
    }

    /**
     * 查询设备
     */
    public function get_device($business_config, $deviceId) {
        $this->set_db_config($business_config);
        $db_srv = get_db_srv();
        return $db_srv->name('tbl_device')->where('deviceid', $deviceId)->find();
    }

    /**
     * 查询人员
     */
    public function syncStaff($business_config, $pageNo = 1, $pageSize = 15) {
        $this->set_db_config($business_config);
        $db_srv = get_db_srv();
        $sql_spl = "exec usp_SelectUserInfoNew @sort=0,@value=N'',@create_time=N'',@create_time1=N'',@PageIndex=%s,
        @PageSize=%s,@firmID=-1,@birthdayyear=N'',@GradeID=N'',@ClassID=0";
        $sql = sprintf($sql_spl, $pageNo, $pageSize);
        return $db_srv->query($sql);
    }

    /**
     * 同步数据
     * @throws Exception
     */
    public function uploadUserData($business_config, $data) {
        try {
            $this->set_db_config($business_config);
            $db_srv = get_db_srv();
            $lingkang_check = Config::get('lingkang_check');
            $equipment_results = [];
            foreach ($data as $item) {
                $date = date('Y-m-d', strtotime($item['remark']));
                $srv_array = [
                    'studentID' => $item['examNumber'],
                    'store_id' => $business_config['store_id'],
                    'testsite_id' => 1,
                    'height' => 0,
                    'weight' => 0,
                    'vital_capacity' => 0,
                    'step_index' => 0,
                    'grip_strength' => 0,
                    'sit_reach' => 0,
                    'vertical_jump' => 0,
                    'pushup' => 0,
                    'stand_one_leg' => 0,
                    'response_time' => 0,
                    'date' => $date,
                ];
                //身高体重
                if ($item['projectNumber'] == 'E016') {
                    $achievement = explode(' ', $item['achievement']);
                    $srv_array['height'] = !empty($achievement[0]) ? $achievement[0] : 0;
                    $srv_array['weight'] = !empty($achievement[1]) ? $achievement[1] : 0;
                } else {
                    $action = !empty($lingkang_check[$item['projectNumber']]) ? $lingkang_check[$item['projectNumber']] : '';
                    if (isset($srv_array[$action])) {
                        $srv_array[$action] = $item['achievement'];
                    }
                }
                //插入数据库
                $sql = "exec usp_PhysicalFitnessLingKang ";
                foreach ($srv_array as $key => $value) {
                    $sql .= "@{$key}='{$value}',";
                }
                $sql = trim($sql, ',');
                $exam_res = $db_srv->query($sql);
                $srv_id = !empty($exam_res[0][0]['id']) ? $exam_res[0][0]['id'] : 0;
                //插入数据中心数据库
                $equipment_result = [
                    'business' => $item['business'],
                    'date' => $date,
                    'record_time' => $item['remark'],
                    'reference' => App::$trace_id,
                    'k1' => $item['achievement'],
                    'k2' => $item['deviceNo'],
                    'k3' => $item['examNumber'],
                    'k4' => $item['projectNumber'],
                    'k5' => $item['planId'],
                    'k6' => $item['startTime'],
                    'k7' => $item['endTime'],
                    'k8' => $srv_id,
                    'create_by' => 'api',
                ];
                $equipment_results[] = $equipment_result;
            }
            $this->equipmentResultModel->addAll($equipment_results);
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '同步成功', 'data'=>[]];
    }

    /**
     * 查询设备
     */
    public function get_device_tnxl($business_config, $deviceId) {
        $this->set_db_config($business_config);
        $db_mysql = get_db();
        return $db_mysql->name('equipment_hardware')->where('device_id', $deviceId)->find();
    }

    /**
     * 查询人员
     */
    public function syncStaffTnxl($business_config, $page = 1, $pageSize = 15) {
        $this->set_db_config($business_config);
        $db_mysql = get_db();
        $where = [
            'status' => 1,
            'del_flag' => 0,
            'is_athlete' => 1,
            'code' => ['<>', ''],
        ];
        $order = [
            'code' => 'asc'
        ];
        $rows = $db_mysql->name('staff')
            ->where($where)
            ->order($order)
            ->page($page, $pageSize)
            ->select();
        $count = $db_mysql->name('staff')
            ->where($where)
            ->count();
        $data = [
            'rows' => $rows,
            'count' => $count,
        ];
        return $data;
    }

    /**
     * 同步数据
     * @throws Exception
     */
    public function uploadUserDataTnxl($business_config, $data, $device) {
        try {
            $this->set_db_config($business_config);
            $db_mysql = get_db();
            //查询人员-部门
            $examNumbers = array_column($data, 'examNumber');
            $staffs = [];
            $staff_departments = [];
            if (!empty($examNumbers)) {
                $staffs = $db_mysql->name('staff')->whereIn('code', $examNumbers)->select();
                if (!empty($staffs)) {
                    $staffs = array_column($staffs, null, 'code');
                    $staff_uuids = array_column($staffs, 'uuid');
                    $staff_departments = $db_mysql->name('staff_department')->whereIn('staff_uuid', $staff_uuids)->select();
                    $staff_departments = array_column($staff_departments, null, 'staff_uuid');
                }
            }
            foreach ($data as $item) {
                $date = date('Y-m-d', strtotime($item['remark']));
                $staff = !empty($staffs[$item['examNumber']]) ? $staffs[$item['examNumber']] : null;
                $staff_department = !empty($staff_departments[$staff['uuid']]) ? $staff_departments[$staff['uuid']]: null;
                //插入数据中心数据库
                $equipment_result = [
                    'business' => $item['business'],
                    'equipment_id' => $device['id'],
                    'date' => $date,
                    'department_uuid' => !empty($staff_department['department_uuid']) ? $staff_department['department_uuid'] : '',
                    'staff_uuid' => !empty($staff['uuid']) ? $staff['uuid'] : '',
                    'record_time' => $item['remark'],
                    'reference' => App::$trace_id,
                    'k1' => $item['achievement'],//成绩
                    'k2' => $item['deviceNo'],//设备唯一标示
                    'k3' => $item['examNumber'],//学生学号
                    'k4' => $item['projectNumber'],//项目编号
                    'k5' => $item['planId'],//测试计划编码
                    'k6' => $item['startTime'],//开始测试时间
                    'k7' => $item['endTime'],//测试结束时间
                    'create_by' => 'api',
                ];
                //插入数据中心数据库主表
                $mac_last_id = $this->equipmentResultModel->add($equipment_result);
                //插入用户数据库主表
                $equipment_result['reference']  = $mac_last_id;
                $db_mysql->name('equipment_result')->insert($equipment_result);
                //更新体能大比武测试结果表
                if($item['achievement'] == '-1002'||$item['achievement'] == '-1006')
                    $tndbw_result = [
                        'aerobic_time_txt' => 0,
                        'aerobic_time' => 0,
                        'aerobic_complete' =>0,
                        'update_time' => date('Y-m-d H:i:s'),
                    ];
                else
                {
                    $time_array = explode(".", $item['achievement']);
                    $totalSeconds = ($time_array[0] * 60) + $time_array[1];
                    $achievement = $time_array[0].':'.$time_array[1];
                    $tndbw_result = [
                        'aerobic_time_txt' => $achievement,
                        'aerobic_time' => $totalSeconds,
                        'aerobic_complete' => 1,
                        'update_time' => date('Y-m-d H:i:s'),
                    ];
                }
                $tndbw_result_where = ['staff_code'=>$item['examNumber'], 'aerobic_complete'=>0];
                $db_mysql->name('tndbw_result')->where($tndbw_result_where)->update($tndbw_result);
            }
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '同步成功', 'data'=>[]];
    }
}
