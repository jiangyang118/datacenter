<?php
/**
 * 无氧功率自行车monark894e数据同步
 */
namespace app\cron\command;
use app\model\EquipmentProcess;
use app\model\EquipmentRelation;
use app\model\EquipmentResult;
use app\model\EquipmentResultExtend;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;

class SyncData extends Command{

    private $equipmentMark = 'MONARK894e';
    private $result_relation_type = 5;
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
    }

    protected function configure() {
        $this->setName('SyncData')->setDescription('this api is for SyncData')
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
                $divice_ids = $this->business_config['equipment_system_numbers'][$this->equipmentMark];
                if (!empty($divice_ids)) {
                    $devices = $this->business_db->name('equipment_hardware')->where(['is_show' => 1, 'id' => ['in', $divice_ids]])->select();
                }
                break;
            case 'tjkx':
            case 'sxkx':
            case 'shjxkx':
                $devices = $this->business_db->name('device_manage')->where(['is_show' => 1, 'data_import' => 3])->select();
                break;
            default:
                break;
        }
        return $devices;
    }
    /**
     * 数据同步脚本
     * php think SyncData $business 每分钟执行一次
     * 文件上传路径
     * ROOT_PATH . 'public/static/upload/$business/sync/wait/'.$device['id'].'/';
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
        $wait_path =  ROOT_PATH . 'public/static/upload/'.$business.'/sync/wait/'.$device['id'].'/';
        //文件同步后路径
        $finish_path =  ROOT_PATH . 'public/static/upload/'.$business.'/sync/finish/'.$device['id'].'/';
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
            //排除非excel
            if (!in_array($suffix, ['.xls', '.XLS', '.xlsx'])) {
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
            foreach ($row as $key => $value) {
                $row[$key] = iconv('GBK', 'UTF-8', $value);
            }
            $csv_data[] = $row;
        }
        fclose($file);
        return $csv_data;
    }

    public $all_staff_info = [];
    //数据入库
    public function runDb($file_path, $device) {
        $csv_data = $this->getCSVData($file_path);
        if (count($csv_data) < 2) {
            return ['code' => 1, 'message' => 'csv数据为空'];
        }
        $result_relations = $this->equipmentRelationModel->inquiryAll(['type' => $this->result_relation_type, 'is_del' => 0]);
        $result_relations = array_column($result_relations, null, 'k');
        //excel全部key 20-37
        $data_all_keys = ['weight', 'power_peak', 'power_peak_relative', 'power_peak_at_time',
            'power_average', 'power_average_relative', 'power_minimun', 'power_minimun_relative', 'power_drop',
            'power_drop_relative', 'power_drop_ws', 'power_drop_wskg', 'power_drop_per', 'velocity_peak',
            'velocity_peak_at_power', 'velocity_peak_at_time', 'power_decline', 'total_energy'];
        $engineryData = [];
        $engineryDataDetailALL = [];
        $engineryDataExtend = [];
        $group_id = '';
        $rep = 1;
        foreach ($csv_data as $key => $item) {
            if ($key == 0) {
                continue;
            }
            $split_time = !empty($item[19]) ? $item[19] : '';
            //主数据
            if (empty($split_time)) {
                $uuid = guid();
                $is_main = 0;
                if (empty($group_id)) {
                    $group_id = $uuid; //组id
                    $is_main = 1;
                }
                $date_str = !empty($item[3]) ? $item[3] : date('Y-m-d');
                $date = date('Y-m-d', strtotime($date_str));
                $record_time = !empty($item[4]) ? $date.' '.$item[4] : $date . ' 10:00:00';
                $engineryData = [
                    'equipment_id' => $device['id'],
                    'equipment_mark' => $this->equipmentMark,
                    'business' => $this->business,
                    'relation_type' => $this->result_relation_type,
                    'date' => $date,
                    'record_time' => $record_time,
                    'reference' => $file_path,
                    'filename' => $file_path,
                    'k1' => 0,
                    'k2' => 0,
                    'k5' => 0,
                    'k14' => 0,
                    'k19' => $group_id,
                    'k20' => $uuid,
                    'is_del' => 0,
                    'create_by' => 'cron',
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                $engineryDataExtend = [
                    'equipment_result_id' => 0,
                    'is_del' => 0,
                    'k21'   => $is_main,
                    'k22'   => !empty($item[13]) ? $item[13] : '',
                    'k23'   => !empty($item[18]) ? $item[18] : '',
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                $i = 20;
                foreach ($data_all_keys as $data_key) {
                    $engineryData[$result_relations[$data_key]['v']] = !empty($item[$i]) ? $item[$i] : '';
                    $i ++;
                }
                $staff_name = !empty($item[0]) ? trim(preg_replace("/\s+/", "", trim($item[0]))) : '';
                if (!empty($staff_name)) {
                    if (empty($this->all_staff_info[$staff_name])) {
                        if ($this->business == 'ahkx') {
                            $staff_res = $this->staffLogic->getStaffCreateTn($staff_name,1,$this->business_config);
                        } else {
                            $staff_res = $this->staffLogic->getStaffCreate($staff_name,1,$this->business_config);
                        }
                        if (!$staff_res['code']) {
                            $this->all_staff_info[$staff_name] = $staff_res['data'];
                        }
                    }
                    if (!empty($this->all_staff_info[$staff_name])) {
                        $engineryData['department_uuid'] = $this->all_staff_info[$staff_name]['department_uuid'];
                        $engineryData['staff_uuid'] = $this->all_staff_info[$staff_name]['uuid'];
                    }
                }
            } else {
                $engineryDataDetail = [
                    'equipment_result_id' => 0, // 关联主表id
                    'k1' => !empty($item[18]) ? $item[18] : '', // k1
                    'k2' => $rep, // k2
                    'k3' => !empty($item[21]) ? trim($item[21]) : '', // k3
                    'create_by' => 'api',
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                $engineryDataDetailALL[] = $engineryDataDetail;
                $rep ++;
            }
        }
        //入库
        try {
            //插入数据中心数据库主表
            $mac_last_id = $this->equipmentResultModel->add($engineryData);
            //插入数据中心数据库主表扩展表
            $engineryDataExtend['equipment_result_id'] = $mac_last_id;
            $this->equipmentResultExtendModel->add($engineryDataExtend);
            //插入数据中心数据库过程表
            foreach ($engineryDataDetailALL as $key => $item) {
                $engineryDataDetailALL[$key]['equipment_result_id'] = $mac_last_id;
            }
            $this->equipmentProcessModel->addALL($engineryDataDetailALL);
            //插入用户数据库主表
            $engineryData['reference']  = $mac_last_id;
            $last_id = $this->business_db->name('equipment_result')->insertGetId($engineryData);
            //插入用户数据库主表扩展表
            $engineryDataExtend['equipment_result_id'] = $last_id;
            $this->business_db->name('equipment_result_extend')->insertGetId($engineryDataExtend);
            //插入用户数据库主表过程表
            foreach ($engineryDataDetailALL as $key => $item) {
                $engineryDataDetailALL[$key]['equipment_result_id'] = $last_id;
            }
            $this->business_db->name('equipment_process')->insertAll($engineryDataDetailALL);
        }catch (Exception $e){
            return ['code' => 1, 'message' => $e->getMessage()];
        }
        return ['code' => 0, 'message' => '导入成功'];
    }
}
