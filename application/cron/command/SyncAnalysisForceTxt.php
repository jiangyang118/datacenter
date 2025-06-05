<?php

namespace app\cron\command;

use app\model\DeviceManage;
use app\model\EngineryData;
use app\model\EngineryDataDetail;
use app\model\EquipmentProcess;
use app\model\EquipmentResult;
use app\model\TableWattbike;
use table\TableServer;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Exception;

class SyncAnalysisForceTxt extends Command
{

    protected function configure()
    {
        $this->setName('SyncAnalysisForceTxt')->setDescription('this api is for SyncAnalysisForceTxt')
            ->addArgument('business');//商户唯一标识;
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        echo date('Y-m-d H:i:s') . ' 数据开始同步' . PHP_EOL;
        $business = $input->getArgument('business');
        $count = $this->syncData($business);
        if ($count > 0) {
            echo date('Y-m-d H:i:s') . ' count:' . $count . PHP_EOL;
        }
        echo date('Y-m-d H:i:s') . ' 数据同步完成' . PHP_EOL;
    }

    public function syncData($business)
    {
        $wait_path = ROOT_PATH . 'public/static/upload/'.$business.'/sync/textwait/';
        $finish_path = ROOT_PATH . 'public/static/upload/'.$business.'/sync/textfinish/';
        if (!file_exists($wait_path) || !is_dir($wait_path)) {
            echo date('Y-m-d H:i:s') . ' 目录不存在：' . PHP_EOL;
            return 0;
        }
        //打开目录
        $directory = opendir($wait_path);
        if (!$directory) {
            echo date('Y-m-d H:i:s') . ' 无法打开目录：' . PHP_EOL;
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
            $suffix = substr($file, strrpos($file, '.'));
            $filename = substr($file, 0, strrpos($file, '.'));
            //排除非excel
            if (!in_array($suffix, ['.txt'])) {
                continue;
            }
            //文件保存名称
            $file_save_name = $filename;//.date('_YmdHis');
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
            echo date('Y-m-d H:i:s') . '目录为空：' . PHP_EOL;
            return 0;
        }
        foreach ($target_path_arr as $target_path) {
            //数据入库
            $res = $this->runDb($target_path,$business);
            echo date('Y-m-d H:i:s') . ' message：' . $res['message'] . $target_path . PHP_EOL;
        }
        return count($target_path_arr);
    }

    public function getTxtData($filePath = '', $process_k_v = [])
    {
        // 打开文件以进行读取
        $file = fopen($filePath, "r");
        $process_data = [];
        $title = [];
        if (!$file) {
            return [];
        }
        // 读取文件内容并输出
        $i = 0;
        while (!feof($file)) {
            $line = fgets($file);
            if (empty($line)) {
                continue;
            }
            $line = preg_replace("/\s+/", ",", trim($line));
            $arr = explode(",", $line);
            if ($i++ == 0) {
                foreach ($arr as $v) {
                    if (empty($process_k_v[$v])) {
                        throw new Exception("数据映射不对");
                    }
                    $title[] = $process_k_v[$v];
                }
            } else {
                $process_data[] = array_combine($title, $arr);
            }
        }
        // 关闭文件
        fclose($file);
        return $process_data;
    }

    public function runDb($filePath,$business)
    {
        $data_center = get_db('data_center');//->where(['is_del'=>0])->field('type,k,v')->select();
        $relation_data = $data_center->name("equipment_relation")->where(['is_del' => 0])->field('type,k,v')->select();
        $result_k_v = [];
        $process_k_v = [];
        foreach ($relation_data as $key => $value) {
            if ($value['type'] == config('force')['process_config']) {
                $process_k_v[$value['k']] = $value['v'];
            } elseif ($value['type'] == config('force')['result_config']) {
                $result_k_v[$value['k']] = $value['v'];
            }
        }
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在' . PHP_EOL;
            return;
        }
        $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
        $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
        $process_data = $this->getTxtData($filePath, $process_k_v);
        $result_data[$result_k_v['start']] = 1;
        $result_data[$result_k_v['end']] = count($process_data);
        $result_data[$result_k_v['period']] = count($process_data) - 1;
        $result_data[$result_k_v['sl_maximum']] = searchMaxOrMinValue($process_data, $process_k_v['steplength'], 'max');
        $result_data[$result_k_v['sl_minimum']] = searchMaxOrMinValue($process_data, $process_k_v['steplength'], 'min');
        $result_data[$result_k_v['sl_average']] = round(array_sum(array_column($process_data, $process_k_v['steplength'])) / count($process_data), 3);
        $result_data[$result_k_v['sr_maximum']] = searchMaxOrMinValue($process_data, $process_k_v['steprate'], 'max');
        $result_data[$result_k_v['sr_minimum']] = searchMaxOrMinValue($process_data, $process_k_v['steprate'], 'min');
        $result_data[$result_k_v['sr_average']] = round(array_sum(array_column($process_data, $process_k_v['steprate'])) / count($process_data), 3);
        $result_data['equipment_id'] = config('force')['equipment_id'];
        $result_data['create_by'] = 'system';
        $result_data['record_time'] = date('Y-m-d H:i:s');
        $result_data['business'] = $business_set[$business]['business'];
        $result_data['reference'] = $filePath;
        $business_db = get_db($mysql_database,$mysql_prefix);
//        $equipment_result_model = new EquipmentResult();
//        $equipment_process_model = new EquipmentProcess();
        // 拼装主表的数据
        $data_center->startTrans();
        $business_db->startTrans();
        $connect_data = [$data_center,$business_db];
        try {
            $center_last_id = 0;
            foreach ($connect_data as $db){
                // 往数据中心主表插入数据
                if ($center_last_id){
                    $result_data['reference'] = $center_last_id;
                }
                $result_add = $db->name('equipment_result')->insertGetId($result_data);
                if (!$result_add) {
                    echo date('Y-m-d H:i:s') . '设备主表数据添加失败';
                }
                foreach ($process_data as $k => $v) {
                    $process_data[$k]['equipment_result_id'] = $result_add;
                    $process_data[$k]['create_by'] = 'system';
                }
                $equipment_process_add = $db->name('equipment_process')->insertAll($process_data);
                if (!$equipment_process_add) {
                    echo date('Y-m-d H:i:s') . '过程主表数据添加失败';
                }
                $center_last_id = $result_add;

            }
            $data_center->commit();
            $business_db->commit();
        } catch (Exception $e) {
            echo $e->getMessage();
            $data_center->rollback();
            $business_db->rollback();
        }

        return ['code' => 0, 'message' => '添加成功'];
    }

    public function analysisDatFile()
    {
        // 文件路径
        $filename = ROOT_PATH . '/application/syncFile/';
        $relation_data = Db::name("equipment_relation")->where(['is_del' => 0])->field('type,k,v')->select();
        $result_k_v = [];
        $process_k_v = [];
        foreach ($relation_data as $key => $value) {
            if ($value['type'] == config('force')['process_config']) {
                $process_k_v[$value['k']] = $value['v'];
            } elseif ($value['type'] == config('force')['result_config']) {
                $result_k_v[$value['k']] = $value['v'];
            }
        }
        // 检查文件是否存在
        if (file_exists($filename)) {
            // 打开文件以进行读取
            $file = fopen($filename, "r");
            $process_data = [];
            $title = [];
            if ($file) {
                // 读取文件内容并输出
                $i = 0;
                while (!feof($file)) {
                    $line = fgets($file);
                    if (empty($line)) {
                        continue;
                    }
                    $line = preg_replace("/\s+/", ",", trim($line));
                    $arr = explode(",", $line);
                    if ($i++ == 0) {
                        foreach ($arr as $v) {
                            if (empty($process_k_v[$v])) {
                                throw new Exception("数据映射不对");
                            }
                            $title[] = $process_k_v[$v];
                        }
                    } else {
                        $process_data[] = array_combine($title, $arr);
                    }
                }
                // 关闭文件
                fclose($file);
                $result_data[$result_k_v['start']] = 1;
                $result_data[$result_k_v['end']] = count($process_data);
                $result_data[$result_k_v['period']] = count($process_data) - 1;
                $result_data[$result_k_v['sl_maximum']] = searchMaxOrMinValue($process_data, $process_k_v['steplength'], 'max');
                $result_data[$result_k_v['sl_minimum']] = searchMaxOrMinValue($process_data, $process_k_v['steplength'], 'min');
                $result_data[$result_k_v['sl_average']] = round(array_sum(array_column($process_data, $process_k_v['steplength'])) / count($process_data), 3);
                $result_data[$result_k_v['sr_maximum']] = searchMaxOrMinValue($process_data, $process_k_v['steprate'], 'max');
                $result_data[$result_k_v['sr_minimum']] = searchMaxOrMinValue($process_data, $process_k_v['steprate'], 'min');
                $result_data[$result_k_v['sr_average']] = round(array_sum(array_column($process_data, $process_k_v['steprate'])) / count($process_data), 3);
                $result_data['equipment_id'] = config('force')['equipment_id'];
                $result_data['create_by'] = 'system';
                $result_data['business'] = config('force')['business'];
                $equipment_result_model = new EquipmentResult();
                $equipment_process_model = new EquipmentProcess();
                // 拼装主表的数据
                try {
                    Db::startTrans();
                    $result_add = $equipment_result_model->add($result_data);
                    if (!$result_add) {
                        throw new Exception('设备主表数据添加失败');
                    }
                    foreach ($process_data as $k => $v) {
                        $process_data[$k]['equipment_result_id'] = $result_add;
                        $process_data[$k]['create_by'] = 'system';
                    }
                    $equipment_process_add = $equipment_process_model->addAll($process_data);
                    if (!$equipment_process_add) {
                        throw new Exception('过程主表数据添加失败');
                    }
                    Db::commit();
                } catch (Exception $e) {
                    var_dump($e->getMessage());
                    exit();
                    Db::rollback();
                }
            } else {
                echo "无法打开文件。";
            }

        } else {
            echo "文件不存在。";
        }
    }
}
