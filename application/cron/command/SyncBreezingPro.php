<?php
/**
 * BreezingPro能量代谢测试仪，csv数据解析
 */
namespace app\cron\command;
use app\model\EquipmentProcess;
use app\model\EquipmentProcessExtend;
use app\model\EquipmentRelation;
use app\model\EquipmentResult;
use app\model\EquipmentResultExtend;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;

class SyncBreezingPro extends Command{

    private $equipmentMark = 'BreezingPro';
    private $result_relation_type = 53;
    private $process_relation_type = 54;
    private $business = null;
    private $business_config = null;
    private $business_db = null;
    private $staffLogic = null;
    private $equipmentRelationModel = null;
    private $equipmentResultModel = null;
    private $equipmentResultExtendModel = null;
    private $equipmentProcessModel = null;

    public function __construct() {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        parent::__construct();
        $this->equipmentRelationModel = new EquipmentRelation();
        $this->equipmentResultModel = new EquipmentResult();
        $this->equipmentResultExtendModel = new EquipmentResultExtend();
        $this->equipmentProcessModel = new EquipmentProcess();
        $this->equipmentProcessExtendModel = new EquipmentProcessExtend();
    }

    protected function configure() {
        $this->setName('SyncBreezingPro')->setDescription('this api is for SyncBreezingPro')
            ->addArgument('business');//商户唯一标识;
    }

    //获取配置
    public function get_business_config() {
        $business_set = config('business');
        $this->business_config = !empty($business_set[$this->business]) ? $business_set[$this->business] : '';
        return $this->business_config;
    }

    //获取数据库
    public function get_business_db() {
        $business_config = $this->business_config;
        $db = Config::get('database');
        $db['database'] = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : $db['database'];
        $db['prefix'] = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : $db['prefix'];
        $db['hostname'] = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : $db['hostname'];
        $db['username'] = !empty($business_config['mysql_username']) ? $business_config['mysql_username'] : $db['username'];
        $db['password'] = !empty($business_config['mysql_password']) ? $business_config['mysql_password'] : $db['password'];
        Config::set('database', $db);
        $this->business_db = get_db();
        return $this->business_db;
    }

    //获取设备
    public function get_devices() {
        $devices = null;
        switch ($this->business) {
            case 'ahkx':
            case 'tnxljstks':
                $divice_ids = $this->business_config['equipment_system_numbers'][$this->equipmentMark];
                if (!empty($divice_ids)) {
                    $devices = $this->business_db->name('equipment_hardware')->where(['is_show' => 1, 'id' => ['in', $divice_ids]])->select();
                }
                break;
            case 'tjkx':
                $devices = $this->business_db->name('device_manage')->where(['is_show' => 1, 'data_import' => 3])->select();
                break;
            case 'qhdkx':
                $devices = $this->business_db->name('device_manage')->where(['is_show' => 1, 'data_import' => 3])->select();
                break;
            default:
                break;
        }
        return $devices;
    }
    /**
     * 数据同步脚本
     * php think SyncBreezingPro $business 每分钟执行一次
     * 文件上传路径
     * ROOT_PATH . 'public/static/upload/$business/BreezingPro/wait/'.$device['id'].'/';
     */
    protected function execute(Input $input, Output $output) {
        echo date('Y-m-d H:i:s'). ' 数据同步开始' . PHP_EOL;

        $this->business = $input->getArgument('business');
        $this->get_business_config();
        if (empty($this->business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在' . PHP_EOL;
            return;
        }
        $this->get_business_db();
        $this->staffLogic = new \app\model\Staff($this->business_db);
        //查询设备
        $devices = $this->get_devices();
        if (empty($devices)) {
            echo date('Y-m-d H:i:s'). '没有需要同步数据的设备';
            return;
        }
        foreach ($devices as $device) {
            echo date('Y-m-d H:i:s'). ' 开始同步设备id：' . $device['id'] .PHP_EOL;
            $count = $this->syncData($device);
            //消息监控
            if ($count > 0) {
                $msg = [
                    '设备id' => $device['id'],
                    '设备名称' => $device['name'],
                    '同步条数' => $count,
                    'business' => $this->business,
                ];
                \app\common\QwRobot::pushMsgFormat($msg, $this->business_config['name'].'设备数据同步');
            }
            echo date('Y-m-d H:i:s'). ' 结束同步设备id：' . $device['id'] . ' count:' . $count .PHP_EOL;
        }
        echo date('Y-m-d H:i:s'). ' 数据同步结束' . PHP_EOL;
    }

    //同步数据
    public function syncData($device) {
        $business = $this->business;
        //文件上传路径
        $wait_path =  ROOT_PATH . 'public/static/upload/'.$business.'/'.$this->equipmentMark.'/wait/'.$device['id'].'/';
        //文件同步后路径
        $finish_path =  ROOT_PATH . 'public/static/upload/'.$business.'/'.$this->equipmentMark.'/finish/'.$device['id'].'/';
        //判断目录
        if (!file_exists($wait_path) || !is_dir($wait_path)) {
            echo date('Y-m-d H:i:s'). ' 目录不存在：' . $wait_path . PHP_EOL;
            return 0;
        }
        //打开目录
        $directory = opendir($wait_path);
        if (!$directory) {
            echo date('Y-m-d H:i:s'). ' 无法打开目录：' . $wait_path . PHP_EOL;
            return 0;
        }
        // 遍历目录中的文件
        $target_path_arr = [];
        while (($file = readdir($directory)) !== false) {
            // 排除当前目录和上级目录和文件夹
            $source_path = $wait_path . $file;//拼装路径
            if ($file == "." || $file == ".." || !is_file($source_path)) {
                continue;
            }
            //文件后缀
            $suffix = substr($file, strrpos($file,'.'));
            $filename = substr($file, 0, strrpos($file,'.'));
            //排除非csv
            if (!in_array($suffix, ['.csv'])) {
                continue;
            }
            //文件保存名称
            $file_save_name = $filename.date('_YmdHis');
            //目标文件路径
            $target_path = $finish_path . $file_save_name . $suffix;
            // 检查目标目录是否存在，如果不存在则创建
            if (!is_dir($finish_path)) {
                mkdir($finish_path, 0777, true);
            }
            rename($source_path, $target_path);
            //copy($source_path, $target_path);
            $target_path_arr[] = $target_path;
        }
        //关闭目录
        closedir($directory);

        if (empty($target_path_arr)) {
            echo date('Y-m-d H:i:s') . ' 目录为空：' . $wait_path .PHP_EOL;
            return 0;
        }
        foreach ($target_path_arr as $target_path) {
            //数据入库
            $res = $this->runDb($target_path, $device);
            echo date('Y-m-d H:i:s'). ' message：' . $res['message'] . $target_path. PHP_EOL;
        }
        return count($target_path_arr);
    }

    //获取csv数据
    public function getCSVData($file_path) {
        $file = fopen($file_path, 'r');
        if (!$file) {
            return [];
        }
        $csv_data = [];
        while ($row = fgetcsv($file, 0, '	')) {
            $csv_data[] = !empty($row[0]) ? explode(',', $row[0]) : [];
        }
        fclose($file);
        return $csv_data;
    }

    public $all_staff_info = [];
    //数据入库
    public function runDb($file_path, $device) {
        $data = $this->getCSVData($file_path);
        $last_index = 13;
        if (count($data) <= $last_index) {
            return ['code' => 1, 'message' => '测试数据为空'];
        }
        //数据格式化处理
        $k1 = $this->get_map_data($data, 3, 1);
        $k2 = $this->get_map_data($data, 4, 1);
        $k3 = $this->get_map_data($data, 5, 1);
        $k4 = $this->get_date($this->get_map_data($data, 6, 1));//日期
        $k5 = $this->get_num($this->get_map_data($data, 7, 1));//数字
        $k6 = $this->get_num($this->get_map_data($data, 8, 1));//数字
        $k7 = $this->get_map_data($data, 9, 1);
        $arr_data = [];
        foreach ($data as $k => $v) {
            if ($k < $last_index) {
                continue;
            }
            $arr = [
                'k8' => $this->get_arr_data($v,0),
                'k9' => $this->get_date($this->get_arr_data($v,1)),//日期
                'k10' => $this->get_arr_data($v,2),
                'k11' => $this->get_arr_data($v,3),
                'k12' => $this->get_arr_data($v,4),
                'k13' => $this->get_arr_data($v,5),
                'k14' => $this->get_arr_data($v,6),
                'k15' => $this->get_arr_data($v,7),
                'k16' => $this->get_num($this->get_arr_data($v,8)),//数字
                'k17' => $this->get_arr_data($v,9),
                'k18' => $this->get_arr_data($v,10),
                'k19' => $this->get_arr_data($v,11),
            ];
            $arr_data[] = $arr;
        }
        $k8 = $arr_data[0]['k8'];
        $k9 = $arr_data[0]['k9'];
        $k10 = $arr_data[0]['k10'];
        $k11 = $arr_data[0]['k11'];
        $k12 = $arr_data[0]['k12'];
        $k13 = $arr_data[0]['k13'];
        $k14 = $arr_data[0]['k14'];
        $k15 = $arr_data[0]['k15'];
        $k16 = $arr_data[0]['k16'];
        $k17 = $arr_data[0]['k17'];
        $k18 = $arr_data[0]['k18'];
        $k19 = $arr_data[0]['k19'];
        //结果数据
        $equipment_result_data = [
            'business' => $this->business,
            'equipment_id' => $device['id'],
            'equipment_mark' => $this->equipmentMark,
            'relation_type' => $this->result_relation_type,
            'department_uuid' => '',
            'staff_uuid' => '',
            'date' => $k9,
            'record_time' => $k9 . date(' H:i:s'),
            'reference' => $file_path,
            'filename' => $file_path,
            'is_del' => 0,
            'create_by' => 'cron',
            'k1' => $k1,
            'k2' => $k2,
            'k3' => $k3,
            'k4' => $k4,
            'k5' => $k5,
            'k6' => $k6,
            'k7' => $k7,
            'k8' => $k8,
            'k9' => $k9,
            'k10' => $k10,
            'k11' => $k11,
            'k12' => $k12,
            'k13' => $k13,
            'k14' => $k14,
            'k15' => $k15,
            'k16' => $k16,
            'k17' => $k17,
            'k18' => $k18,
            'k19' => $k19
        ];
        $staff_name = $k2.$k3;
        $sex = $equipment_result_data['k1'] == 'Female' ? 2 : 1;
        $height = $equipment_result_data['k5'];
        $weight = $equipment_result_data['k6'];
        $birthday = $equipment_result_data['k4'];
        if (!empty($staff_name)) {
            if (empty($this->all_staff_info[$staff_name])) {
                if ($this->business == 'tnxljstks') {
                    $staff_res = $this->staffLogic->getStaffCreateTn($staff_name, $sex, $this->business_config, $height, $weight, $birthday);
                } else {
                    $staff_res = $this->staffLogic->getStaffCreate($staff_name, $sex, $this->business_config);
                }
                if (!$staff_res['code']) {
                    $this->all_staff_info[$staff_name] = $staff_res['data'];
                }
            }
            if (!empty($this->all_staff_info[$staff_name])) {
                $equipment_result_data['department_uuid'] = $this->all_staff_info[$staff_name]['department_uuid'];
                $equipment_result_data['staff_uuid'] = $this->all_staff_info[$staff_name]['uuid'];
            }
        }
        //过程数据
        $equipment_process_all = [];
        foreach ($arr_data as $item) {
            $equipment_process_data = [
                'equipment_result_id' => 0,
                'create_by' => 'cron',
                'k1' => $k1,
                'k2' => $k2,
                'k3' => $k3,
                'k4' => $k4,
                'k5' => $k5,
                'k6' => $k6,
                'k7' => $k7,
            ];
            foreach ($item as $k => $v) {
                $equipment_process_data[$k] = $v;
            }
            $equipment_process_all[] = $equipment_process_data;
        }
        //入库
        try {
            //插入数据中心数据库主表
            $mac_last_id = $this->equipmentResultModel->add($equipment_result_data);
            //插入用户数据库主表
            $equipment_result_data['reference']  = $mac_last_id;
            $last_id = $this->business_db->name('equipment_result')->insertGetId($equipment_result_data);
            foreach ($equipment_process_all as $equipment_process) {
                //插入数据中心数据库过程表
                $equipment_process['equipment_result_id'] = $mac_last_id;
                $process_mac_last_id = $this->equipmentProcessModel->add($equipment_process);
                //插入用户数据库过程表
                $equipment_process['equipment_result_id'] = $last_id;
                $equipment_process['reference'] = $process_mac_last_id;
                $process_last_id = $this->business_db->name('equipment_process')->insertGetId($equipment_process);
            }
            //插入状态记录
            $this->addStatusRecord($equipment_result_data);
        } catch (Exception $e){
            return ['code' => 1, 'message' => $e->getMessage()];
        }
        return ['code' => 0, 'message' => '导入成功'];
    }

    //插入状态记录
    public function addStatusRecord($equipment_result) {
        //江苏体科所
        if ($this->business != 'tnxljstks') {
            return;
        }
        foreach ($this->status_record_relation as $type => $v) {
            //查询当前人员当天的历史数据
            $item = [
                'staff_uuid' => $equipment_result['staff_uuid'],
                'type' => $type,
                'date' => $equipment_result['date'],
            ];
            $dataOri = $this->business_db->name('status_record')->where($item)->find();
            if (empty($dataOri)) {//新增
                $item['value'] = $equipment_result[$v];
                $item['create_time'] = date('Y-m-d H:i:s');
                $item['create_by'] = 'api';
                $this->business_db->name('status_record')->insertGetId($item);
            } else {//更新
                $this->business_db->name('status_record')
                    ->where(['id' => $dataOri['id']])
                    ->update(['value' => $equipment_result[$v]]);
            }
        }
    }

    /*
     * 能量代谢测试表对应关系status_record
     * */
    public $status_record_relation = [
        '20' => 'k10', // 静息代谢率
        '21' => 'k11', // 预估静息代谢率
        '22' => 'k12', // 实际与预测差异比率
        '23' => 'k13', // 总能量消耗
        '24' => 'k14', // 摄氧量
        '25' => 'k15', // 二氧化碳呼出量
        '26' => 'k16', // 呼吸商
        '27' => 'k17', // 每分钟通气量
        '28' => 'k18', // 呼吸频率
    ];

    //获取一维数组的值
    public function get_arr_data($data, $index) {
        return isset($data[$index]) ? trim($data[$index]) : '';
    }

    //获取多维数组的值
    public function get_map_data($data, $l, $r) {
        return isset($data[$l]) && isset($data[$l][$r]) ? trim($data[$l][$r]) : '';
    }

    //获取数字
    public function get_num($str) {
        if (empty($str)) {
            return '';
        }
        if (preg_match('/\d+\.\d+|\d+/', $str, $arr)) {
            return $arr[0];
        }
        return 0;
    }

    //日期转换'08/12/2020' => 2024-08-12
    public function get_date($str) {
        if (empty($str)) {
            return date('Y-m-d');
        }
        return date('Y-m-d', strtotime($str));
    }
}
