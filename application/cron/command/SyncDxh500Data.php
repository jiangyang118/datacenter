<?php

namespace app\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;

class SyncDxh500Data extends Command
{

    protected function configure()
    {
        $this->setName('SyncDxh500Data')->setDescription('this api is for SyncDxh500Data')
            ->addArgument('business');//商户唯一标识;
    }

    protected function execute(Input $input, Output $output)
    {
        $absolutePath = '/workspace/wwwroot/datacenterfile/qhdkx/dxh500/';
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        echo date('Y-m-d H:i:s') . ' 数据开始同步' . PHP_EOL;
        $business = $input->getArgument('business');
        $count = $this->syncData($absolutePath, $business);
        if ($count > 0) {
            echo date('Y-m-d H:i:s') . ' count:' . $count . PHP_EOL;
        }
        echo date('Y-m-d H:i:s') . ' 数据同步完成' . PHP_EOL;
    }

    //希森美康KX21N数据自动同步
    public function syncData($directoryPath='', $business='qhdkx') {
        if(!$business){
            $business='qhdkx';
        }
        // 指定目录路径 $directoryPath，使用file_exists函数来检查目录是否存在
//        $directoryPath = ROOT_PATH . $directoryPath;
        if (file_exists($directoryPath) && is_dir($directoryPath)) {
            //echo '目录存在';
        } else {
            echo '目录不存在 ';return;
        }
        // 打开目录
        $directory = opendir($directoryPath);
        if (!$directory) {
            echo '目录不存在 ';return;
        }
        $file_name_arr = array();
        // 遍历目录中的文件
        while (($file = readdir($directory)) !== false) {
            // 排除当前目录和上级目录
            if ($file != "." && $file != "..") {
                // 检查文件类型
                if (is_file($directoryPath . $file)) {
                    if(strpos($file, '.csv') !== false){
                        //解析此文件
                        $creation_time = filectime($directoryPath . $file);
                        $last_time = strtotime(date('2025-05-23'));//此日期后的文件为正常文件
                        if($creation_time > $last_time){
                            $file_name_arr[] = $file;
                        }
//                        $file_name_arr[] = $file;
                    }
                }
            }
        }
        if(empty($file_name_arr)){
            echo '暂无需要同步的文件 ';return;
        }
        // 关闭目录
        closedir($directory);
        //连接项目库
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在：' . $business . '_' . PHP_EOL;
            return;
        }
        $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
        $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
        $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';
        $mysql_username = !empty($business_config['mysql_username']) ? $business_config['mysql_username'] : '';
        $mysql_password = !empty($business_config['mysql_password']) ? $business_config['mysql_password'] : '';
        $db_mysql = get_db_mysql($mysql_database, $mysql_prefix, $mysql_hostname, $mysql_username, $mysql_password);
        $db_data_center = get_db('data_center');
        //获取所有人员数据
        $all_staff_info = $db_mysql->name('staff')
            ->alias('a')
            ->join('staff_department b','a.uuid = b.staff_uuid','left')
            ->where(['a.is_show' => 1, 'a.identity_number'=>['neq','']])->column('name,a.uuid,b.department_uuid','identity_number');
        //字段对应表
        $relation_res = $db_mysql->name('equipment_relation')->where(['type' => 500])->select();
        $relation_arr = array('result'=>[],'process'=>[]);
        foreach ($relation_res as $value){
            $relation_arr[$value['k']] = $value['v'];
        }
        foreach ($file_name_arr as $file_name){
            $excel = array();
            // 文件绝对路径
            $file_path_ab = $directoryPath . DIRECTORY_SEPARATOR . $file_name;
            // 检查文件是否存在及是否是CSV文件
            if (!file_exists($file_path_ab) || pathinfo($file_path_ab, PATHINFO_EXTENSION) !== 'csv') {
                return ['error' => '文件不存在或不是有效的CSV文件'];
            }
            // 打开文件句柄
            if (($handle = fopen($file_path_ab, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) { // 注意：如果您的CSV文件使用不同的分隔符，请相应地调整此参数
                    // 将每一行的数据添加到数组中
                    $excel[] = $data;
                }
            } else {
                return ['error' => '无法打开CSV文件'];
            }
            if (count($excel) < 2) {
                echo "导入失败：文件‘".$file_name."’数据为空";continue;
            }
            foreach ($excel as $index => $item) {
                if ($index == 0) {
                    continue;
                }
            }
            //处理表格数据
            foreach ($excel as $index => $item) {
                if ($index == 0) {
                    continue;
                }
                if(empty($item[3])){
                    continue;
                }
                //样本号信息
                $test_date = date('Y-m-d', strtotime($item[3]));
                //如果样本测试过——————暂不同步
                $ori_where = ['relation_type' => 500, 'date' => $test_date, 'k8' => trim($item[1]), 'k19' => 0];
                $specimen_ori = $db_data_center->name('equipment_result')->where($ori_where)->count();
                if (!empty($specimen_ori)) {
                    continue;
                }
                if(!isset($all_staff_info[$item[1]])){
                    $all_staff_info[$item[1]]['department_uuid'] = '';
                    $all_staff_info[$item[1]]['uuid'] = '';
                }
                $item[3] = $test_date;
                //结果表数据
                $result_info = array(
                    'business' => $business,
                    'equipment_id' => 11,
                    'relation_type' => 500,
                    'department_uuid' => $all_staff_info[$item[1]]['department_uuid'],
                    'staff_uuid' => $all_staff_info[$item[1]]['uuid'],
                    'date' => $item[3],//日期：05/30/2024
                    'record_time' => $item[3],//日期：05/30/2024
//                    'k1' => '',
//                    'k2' => '',
//                    'k3' => '',
//                    'k4' => '',
                    'k5' => $item[3],//日期：05/30/2024
                    'k6' => $item[0],//样本号：216
                    'k7' => $all_staff_info[$item[1]]['uuid'],//检测人uuid
                    'k8' => $item[1],//检测人-编号：Specimen_ID
                    'k9' => $item[4]?$item[4]:'',//检测时间：16:40
                    'k10' => 'system_sync',//创建人
                    'k11' => $file_path_ab,//文件路径
                    'k12' => '',//病人类型
                    'k13' => '',//科室
                    'k14' => '',//诊断
                    'k15' => '',//费别
                    'k16' => '',//住院号
                    'k17' => '',//床号
                    'k18' => '',//备注
                    'k19' => 0//是否删除1是否0
                );
                $result_info_extend = array(
                    'k21' => trim($item[20]),//WBC——白细胞计数
                    'k22' => trim($item[22]),//RBC——红细胞计数
                    'k23' => trim($item[24]),//HGB——血红蛋白浓度
                    'k24' => trim($item[26]),//HCT——红细胞压积
                    'k25' => trim($item[28]),//MCV——平均红细胞体积
                    'k26' => trim($item[30]),//MCH——平均红细胞血红蛋白量
                    'k27' => trim($item[32]),//MCHC——平均红细胞血红蛋白浓度
                    'k28' => trim($item[34]),//RDW——红细胞分布宽度
                    'k29' => trim($item[36]),//RDW-SD——平均血小板分布宽度标准差
                    'k30' => trim($item[38]),//PLT——平均血小板计数
                    'k31' => trim($item[40]),//MPV——平均血小板体积
                    'k32' => trim($item[42]),//LY——淋巴细胞百分比
                    'k33' => trim($item[44]),//MO——单核细胞百分比
                    'k34' => trim($item[46]),//NE——中性粒细胞百分比
                    'k35' => trim($item[48]),//EO——嗜酸性粒细胞百分比
                    'k36' => trim($item[50]),//BA——嗜碱性粒细胞百分比
                    'k37' => trim($item[52]),//LY#——淋巴细胞绝对值
                    'k38' => trim($item[54]),//MO#——单核细胞绝对值
                    'k39' => trim($item[56]),//NE#——中性粒细胞绝对值
                    'k40' => trim($item[58]),//EO#——嗜酸性粒细胞绝对值
                    'k41' => trim($item[60]),//BA#——嗜碱性粒细胞绝对值
//                    'k42' => $item[],//红细胞分布幅-SD
                );
                try{
                    $db_mysql->startTrans();
                    $db_data_center->startTrans();
                    $result_data_center_res = $db_data_center->name('equipment_result')->insertGetId($result_info);
                    if(!$result_data_center_res){
                        throw new Exception("文件" . $file_name . "数据中心同步失败");
                    }
                    $result_mysql_res = $db_mysql->name('equipment_result')->insertGetId($result_info);
                    if(!$result_mysql_res){
                        throw new Exception("文件" . $file_name . "商户处同步失败");
                    }
                    $result_info_extend['equipment_result_id'] = $result_data_center_res;
                    $result_extend_data_center_res = $db_data_center->name('equipment_result_extend')->insert($result_info_extend);
                    if(!$result_extend_data_center_res){
                        throw new Exception("文件" . $file_name . "数据中心同步失败");
                    }
                    $result_info_extend['equipment_result_id'] = $result_mysql_res;
                    $result_extend_mysql_res = $db_mysql->name('equipment_result_extend')->insert($result_info_extend);
                    if(!$result_extend_mysql_res){
                        throw new Exception("文件" . $file_name . "商户处同步失败");
                    }
                    $db_mysql->commit();
                    $db_data_center->commit();
                }catch(Exception $ex) {
                    $db_mysql->rollback();
                    $db_data_center->rollback();
                    echo $ex->getMessage();
                }die;
            }
        }
        echo "导入成功";
    }
}
