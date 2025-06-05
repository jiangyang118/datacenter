<?php
/**
 * 心肺功能测试仪德国Cortex，xml数据解析
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

class SyncCortex extends Command{

    private $equipmentMark = 'Cortex';
    private $result_relation_type = 37;
    private $process_relation_type = 38;
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
        $this->setName('SyncCortex')->setDescription('this api is for SyncCortex')
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
     * php think SyncCortex $business 每分钟执行一次
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
        $wait_path =  ROOT_PATH . 'public/static/upload/'.$business.'/cortex/wait/'.$device['id'].'/';
        //文件同步后路径
        $finish_path =  ROOT_PATH . 'public/static/upload/'.$business.'/cortex/finish/'.$device['id'].'/';
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
            //排除非xml
            if (!in_array($suffix, ['.xml'])) {
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
        $xml = simplexml_load_string(file_get_contents($file_path));
        $result_data = [];
        $process_data = [];
        $line = 0;
        foreach ($xml->Worksheet->Table->children() as $child) {
            $child_name = $child->getName();
            $child_count = $child->count();
            if ($child_name != 'Row') {
                continue;
            }
            $line ++;
            if ($child_count < 2) {
                continue;
            }
            $child_arr = json_decode(json_encode($child), true);
            $cell = $child_arr['Cell'];
            //结果数据
            if ($line < 108) {
                $result_k = 'line'.$line;
                $result_v = !empty($cell[1]['Data']) ? $cell[1]['Data'] : '';
                //数据处理
                switch ($result_k) {
                    case 'line27':
                        $result_v = trim($result_v);
                        break;
                    case 'line31':
                        $result_v = date('Y-m-d', strtotime($result_v));
                        break;
                    case 'line35':
                    case 'line36':
                    case 'line43':
                    case 'line52':
                    case 'line53':
                    case 'line54':
                    case 'line55':
                    case 'line57':
                    case 'line58':
                    case 'line59':
                    case 'line60':
                    case 'line62':
                    case 'line102':
                    case 'line103':
                    case 'line104':
                        preg_match('/\d+(\.\d+)?/', $result_v, $arr);
                        $result_v = !empty($arr[0]) ? $arr[0] : '';
                        break;
                    case 'line56':
                        preg_match_all('/\d+(\.\d+)?/', $result_v, $arr);
                        $result_v = !empty($arr[0][1]) ? $arr[0][1] : '';
                        break;
                    case 'line96':
                        $result_v = date('Y-m-d H:i:s', strtotime($result_v));
                        break;
                    default:
                        break;
                }
                $result_data[$result_k] = $result_v;
            }
            //过程数据-summary
            if ($line >= 109 && $line <= 154) {
                $item = [];
                $item['type'] = 1;
                foreach ($cell as $key => $value) {
                    $process_k = 'line108_'.$key;
                    $item[$process_k] = !empty($value['Data']) ? $value['Data'] : '';
                }
                $process_data[] = $item;
            }
            //过程数据-measurement
            if ($line >= 159) {
                $item = [];
                $item['type'] = 2;
                foreach ($cell as $key => $value) {
                    $process_k = 'line158_'.$key;
                    $item[$process_k] = !empty($value['Data']) ? $value['Data'] : '';
                }
                $process_data[] = $item;
            }
        }
        $data['result'] = $result_data;
        $data['process'] = $process_data;
        return $data;
    }

    public $all_staff_info = [];
    //数据入库
    public function runDb($file_path, $device) {
        $data = $this->getCSVData($file_path);
        $result_data = $data['result'];
        $process_data = $data['process'];
        $result_relations = $this->equipmentRelationModel->inquiryAll(['type' => $this->result_relation_type, 'is_del' => 0]);
        $process_relations = $this->equipmentRelationModel->inquiryAll(['type' => $this->process_relation_type, 'is_del' => 0]);
        //结果数据
        $equipment_result_data = [
            'equipment_id' => $device['id'],
            'equipment_mark' => $this->equipmentMark,
            'business' => $this->business,
            'relation_type' => $this->result_relation_type,
            'date' => date('Y-m-d', strtotime($result_data['line96'])),
            'record_time' => $result_data['line96'],
            'reference' => $file_path,
            'filename' => $file_path,
            'is_del' => 0,
            'create_by' => 'cron',
        ];
        $equipment_result_extend_data = [];
        foreach ($result_relations as $result_relation) {
            $result_value = !empty($result_data[$result_relation['k']]) ? $result_data[$result_relation['k']] : '';
            if (trim($result_relation['v'], 'k') <= 20) {
                $equipment_result_data[$result_relation['v']] = $result_value;
            } else {
                $equipment_result_extend_data[$result_relation['v']] = $result_value;
            }
        }
        $staff_name = $result_data['line27'];
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
                $equipment_result_data['department_uuid'] = $this->all_staff_info[$staff_name]['department_uuid'];
                $equipment_result_data['staff_uuid'] = $this->all_staff_info[$staff_name]['uuid'];
            }
        }
        $equipment_process_all = [];
        //过程数据
        foreach ($process_data as $item) {
            $equipment_process_data = [
                'equipment_result_id' => 0,
                'create_by' => 'api',
                'extend' => [],
            ];
            foreach ($process_relations as $process_relation) {
                $process_value = !empty($item[$process_relation['k']]) ? $item[$process_relation['k']] : '';
                if (trim($process_relation['v'], 'k') <= 20) {
                    $equipment_process_data[$process_relation['v']] = $process_value;
                } else {
                    $equipment_process_data['extend'][$process_relation['v']] = $process_value;
                }
            }
            $equipment_process_all[] = $equipment_process_data;
        }
        //入库
        try {
            //插入数据中心数据库主表
            $mac_last_id = $this->equipmentResultModel->add($equipment_result_data);
            //插入数据中心数据库主表扩展表
            $equipment_result_extend_data['equipment_result_id'] = $mac_last_id;
            $this->equipmentResultExtendModel->add($equipment_result_extend_data);
            //插入用户数据库主表
            $equipment_result_data['reference']  = $mac_last_id;
            $last_id = $this->business_db->name('equipment_result')->insertGetId($equipment_result_data);
            //插入用户数据库主表扩展表
            $equipment_result_extend_data['equipment_result_id'] = $last_id;
            $this->business_db->name('equipment_result_extend')->insertGetId($equipment_result_extend_data);
            foreach ($equipment_process_all as $equipment_process) {
                $equipment_process_extend = $equipment_process['extend'];
                unset($equipment_process['extend']);
                //插入数据中心数据库过程表
                $equipment_process['equipment_result_id'] = $mac_last_id;
                $process_mac_last_id = $this->equipmentProcessModel->add($equipment_process);
                //插入数据中心数据库过程扩展表
                $equipment_process_extend['equipment_process_id'] = $process_mac_last_id;
                $this->equipmentProcessExtendModel->add($equipment_process_extend);

                //插入用户数据库过程表
                $equipment_process['equipment_result_id'] = $last_id;
                $equipment_process['reference'] = $process_mac_last_id;
                $process_last_id = $this->business_db->name('equipment_process')->insertGetId($equipment_process);
                //插入用户数据库过程扩展表
                $equipment_process_extend['equipment_process_id'] = $process_last_id;
                $this->business_db->name('equipment_process_extend')->insertGetId($equipment_process_extend);
            }
        }catch (Exception $e){
            return ['code' => 1, 'message' => $e->getMessage()];
        }
        return ['code' => 0, 'message' => '导入成功'];
    }
}
