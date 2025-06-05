<?php
/**
 * 名称：瀚雅心肺功能测试仪 excel解析
 * 品牌：Highermed
 * 品牌：瀚雅
 * 型号：Smax58ce
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

class SyncHighermed extends Command{

    private $equipmentMark = 'Highermed';
    private $result_relation_type = 45;
    private $process_relation_type = 46;
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
        $this->setName('SyncHighermed')->setDescription('this api is for SyncHighermed')
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
            case 'tjkx':
                $devices = $this->business_db->name('device_manage')->where(['is_show' => 1, 'ruimei_yq' => 'Highermed'])->select();
                break;
            default:
                break;
        }
        return $devices;
    }
    /**
     * 数据同步脚本
     * php think SyncHighermed $business 每分钟执行一次
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

    //获取excel数据
    public function getExcelData($file_path) {
        $pathinfo = pathinfo($file_path);
        if (isset($pathinfo['extension']) && $pathinfo['extension'] == 'xlsx') {
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
        } else {
            $objReader = \PHPExcel_IOFactory::createReader('Excel5');
        }
        $obj_PHPExcel = $objReader->load($file_path);
        $excel_data = $obj_PHPExcel->getsheet(0)->toArray();
        return $excel_data;
    }

    public function get_arr_data($excel_data, $l, $r) {
        return isset($excel_data[$l]) && isset($excel_data[$l][$r]) ? $excel_data[$l][$r] : '';
    }
    public $all_staff_info = [];
    //数据入库
    public function runDb($file_path, $device) {
        $excel_data = $this->getExcelData($file_path);
        if (count($excel_data) < 2) {
            return ['code' => 1, 'message' => 'Excel数据为空'];
        }
        //主数据
        $engineryData = [
            'business' => $this->business,
            'equipment_id' => $device['id'],
            'equipment_mark' => $this->equipmentMark,
            'relation_type' => $this->result_relation_type,
            'department_uuid' => '',
            'staff_uuid' => '',
            'staff_height' => $this->get_arr_data($excel_data, 13, 15),
            'staff_weight' => $this->get_arr_data($excel_data, 13, 26),
            'staff_age' => $this->get_arr_data($excel_data, 11, 38),
            'date' => '',
            'record_time' => '',
            'datetimes' => $this->get_arr_data($excel_data, 9, 5),
            'reference' => $file_path,
            'filename' => $file_path,
            'k1' => $this->get_arr_data($excel_data, 11, 5),//line11_5 病历号
            'k2' => $this->get_arr_data($excel_data, 24, 16),//VO2_Peak 最大摄氧量绝对值
            'k3' => $this->get_arr_data($excel_data, 29, 16),//VO2/kg_Peak 最大摄氧量相对值
            'k4' => $this->get_arr_data($excel_data, 23, 16),//VE_Peak 最大通气量
            'k5' => $this->get_arr_data($excel_data, 23, 13),//VE_AT 通气无氧阈
            'k6' => $this->get_arr_data($excel_data, 36, 16),//HR_Peak 最大心率
            'k7' => $this->get_arr_data($excel_data, 36, 13),//HR_AT 心率无氧阈
            'k8' => $this->get_arr_data($excel_data, 11, 15),//line11_15 姓名
            'k9' => $this->get_arr_data($excel_data, 11, 26),//line11_26 性别
            'k10' => $this->get_arr_data($excel_data, 13, 5),//line13_5 出生日期
            'k11' => $this->get_arr_data($excel_data, 13, 38),//line13_38 BMI
            'k12' => $this->get_arr_data($excel_data, 16, 22),//line16_22 运动设备
            'k13' => $this->get_arr_data($excel_data, 16, 34),//line16_34 运动方案
            'k14' => $this->get_arr_data($excel_data, 42, 12),//line42_12 Anaerobic Threshold（无氧阈）
            'k15' => $this->get_arr_data($excel_data, 42, 30),//line42_30 Respiratory（呼吸储备）
            'k16' => $this->get_arr_data($excel_data, 44, 12),//line44_12 VO2/VO2pred
            'k17' => $this->get_arr_data($excel_data, 44, 30),//line44_30 Breathing reserve
            'is_del' => 0,
            'create_by' => 'cron',
            'create_time' => date('Y-m-d H:i:s'),
        ];
        $staff_name = $engineryData['k8'];
        $sex = $engineryData['k9'] == '男' ? 1 : 2;
        if (!empty($staff_name)) {
            if (empty($this->all_staff_info[$staff_name])) {
                $staff_res = $this->staffLogic->getStaffCreate($staff_name, $sex, $this->business_config);
                if (!$staff_res['code']) {
                    $this->all_staff_info[$staff_name] = $staff_res['data'];
                }
            }
            if (!empty($this->all_staff_info[$staff_name])) {
                $engineryData['department_uuid'] = $this->all_staff_info[$staff_name]['department_uuid'];
                $engineryData['staff_uuid'] = $this->all_staff_info[$staff_name]['uuid'];
            }
        }
        $datetimes = $engineryData['datetimes'];
        if (!empty($datetimes)) {
            $engineryData['date'] = date('Y-m-d', strtotime($datetimes));
            $engineryData['record_time'] = date('Y-m-d H:i:s', strtotime($datetimes));
        }
        //子数据
        $engineryDataDetailALL = [];
        $key_list = [20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 36, 37, 38, 39, 40];
        foreach ($excel_data as $key => $value) {
            if (!in_array($key, $key_list)) {
                continue;
            }
            $k1 = $k2 = '';
            if (!empty($value[0])) {
                $name = $value[0];
                $name_list = explode('(', $name);
                $k1 = $name_list[0];
                if (!empty($name_list[1])) {
                    $k2 = trim($name_list[1], ')');
                }
            }
            $item = [
                'equipment_result_id' => 0,
                'k1' => $k1,//Name
                'k2' => $k2,//unit
                'k3' => isset($value[7]) ? $value[7] : '',//Rest
                'k4' => isset($value[9]) ? $value[9] : '',//Ref
                'k5' => isset($value[13]) ? $value[13] : '',//AT
                'k6' => isset($value[16]) ? $value[16] : '',//Peak
                'k7' => isset($value[19]) ? $value[19] : '',//Peak%Pred
                'k8' => isset($value[24]) ? $value[24] : '',//AT%Peak
                'k9' => isset($value[28]) ? $value[28] : '',//Rec1
                'k10' => isset($value[35]) ? $value[35] : '',//Rec3
                'is_del' => 0,
                'create_by' => 'cron',
                'create_time' => date('Y-m-d H:i:s'),
            ];
            $engineryDataDetailALL[] = $item;
        }
        //入库
        try {
            //插入数据中心数据库主表
            $mac_last_id = $this->equipmentResultModel->add($engineryData);
            //插入数据中心数据库过程表
            foreach ($engineryDataDetailALL as $key => $item) {
                $engineryDataDetailALL[$key]['equipment_result_id'] = $mac_last_id;
            }
            $this->equipmentProcessModel->addALL($engineryDataDetailALL);
            //插入用户数据库主表
            $engineryData['reference']  = $mac_last_id;
            $last_id = $this->business_db->name('equipment_result')->insertGetId($engineryData);
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
