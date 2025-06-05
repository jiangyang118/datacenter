<?php

namespace app\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;

class SyncKX21NData extends Command
{

    protected function configure()
    {
        $this->setName('SyncKX21NData')->setDescription('this api is for SyncKX21NData')
            ->addArgument('business');//商户唯一标识;
    }

    protected function execute(Input $input, Output $output)
    {
//        $absolutePath = 'D:\PHP\allProjectFile\tinengProject\datacenterFile\ahtks\kx21n';
        $absolutePath = '/workspace/wwwroot/datacenterfile/tnxlahtks/kx21n/';
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        echo date('Y-m-d H:i:s') . ' 数据开始同步' . PHP_EOL;
        $business = $input->getArgument('business');
        $count = $this->syncData($absolutePath, $business);
//        $count = $this->syncData();
        if ($count > 0) {
            echo date('Y-m-d H:i:s') . ' count:' . $count . PHP_EOL;
        }
        echo date('Y-m-d H:i:s') . ' 数据同步完成' . PHP_EOL;
    }

    //希森美康KX21N数据自动同步
    public function syncData($directoryPath='', $business='ahkx') {
        if(!$business){
            $business='ahkx';
        }
        // 指定目录路径 $directoryPath
        // 使用file_exists函数来检查目录是否存在
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
                    if(strpos($file, '.xls') !== false || strpos($file, '.XLS') !== false || strpos($file, '.xlsx') !== false){
                        //检查是否为同步过的文件
//                        if (strpos($file, 'Synced') !== false) {
//                            $file_name_arr[] = $file;
//                        }
                        $file_name_arr[] = $file;
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
            ->where(['a.del_flag' => 0, 'a.code'=>['neq','']])->column('name,a.uuid,b.department_uuid','code');
        //字段对应表
        $relation_res = $db_mysql->name('equipment_relation')->where(['type' => 9])->select();
        $relation_arr = array('result'=>[],'process'=>[]);
        foreach ($relation_res as $value){
            $relation_arr[$value['k']] = $value['v'];
        }
        $relation_res = $db_mysql->name('equipment_relation')->where(['type' => 9])->select();
        foreach ($file_name_arr as $file_name){
            //文件绝对路径
            $file_path_ab = $directoryPath . $file_name;
            $pathinfo = pathinfo($file_path_ab);
            if (isset($pathinfo['extension']) && $pathinfo['extension'] == 'xlsx') {
                $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            } else {
                $objReader = \PHPExcel_IOFactory::createReader('Excel5');
            }
            $obj_PHPExcel = $objReader->load($file_path_ab);
            $excel = $obj_PHPExcel->getsheet(0)->toArray();
            if (count($excel) < 2) {
//                return array("code"=>1, "message"=>"导入失败：数据为空", "data"=>[]);
                echo "导入失败：文件‘".$file_name."’数据为空";continue;
            }
            foreach ($excel as $index => $item) {
                if ($index == 0) {
                    continue;
                }
            }

            //检测项信息
//            $test_items = array_slice($excel[0],12,30);
//            $model_num = $excel[1][0];
//            $detection_items = $db_mysql->name('equipment_relation')->where(['type'=> 9])->select();
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
                $ori_where = ['relation_type' => 9, 'date' => $test_date, 'k8' => trim($item[4]), 'k19' => 0];
                $specimen_ori = $db_data_center->name('equipment_result')->where($ori_where)->count();
                if (!empty($specimen_ori)) {
                    continue;
                }
                if(!isset($all_staff_info[$item[4]])){
                    $all_staff_info[$item[4]]['department_uuid'] = '';
                    $all_staff_info[$item[4]]['uuid'] = '';
                }
                //结果表数据
                $result_info = array(
                    'business' => $business,
                    'equipment_id' => 12,
                    'relation_type' => 9,
                    'department_uuid' => $all_staff_info[$item[4]]['department_uuid'],
                    'staff_uuid' => $all_staff_info[$item[4]]['uuid'],
                    'date' => $item[3],
//                    'k1' => '',
//                    'k2' => '',
//                    'k3' => '',
//                    'k4' => '',
                    'k5' => $item[3],//检测日期
                    'k6' => $item[2],//样本号
                    'k7' => $all_staff_info[$item[4]]['uuid'],//检测人uuid
                    'k8' => $item[4],//检测人-编号
                    'k9' => $item[11]?$item[11]:'',//检测时间
                    'k10' => 'system_sync',//创建人
                    'k11' => $file_path_ab,//文件路径
                    'k12' => $item[9],//病人类型
                    'k13' => $item[8],//科室
                    'k14' => $item[10],//诊断
                    'k15' => '',//费别
                    'k16' => $item[1],//住院号
                    'k17' => '',//床号
                    'k18' => '',//备注
                    'k19' => 0//是否删除1是否0
                );
                $result_info_extend = array(
                    'k21' => '',//凝血时间
                    'k22' => '',//出血时间
                    'k23' => $item[15],//红细胞比积
                    'k24' => $item[16],//血红蛋白量
                    'k25' => $item[17],//淋巴细胞绝对值
                    'k26' => $item[18],//淋巴细胞百分比
                    'k27' => $item[19],//平均血红蛋白值
                    'k28' => $item[20],//平均血红蛋白浓度
                    'k29' => $item[21],//红细胞平均体积
                    'k30' => $item[22],//平均血小板体积
                    'k31' => $item[23],//中值细胞数绝对值
                    'k32' => $item[24],//中值细胞百分比
                    'k33' => $item[25],//中性粒细胞绝对值
                    'k34' => $item[26],//中性粒细胞百分比
                    'k35' => $item[28],//血小板分布幅
                    'k36' => $item[27],//大型血小板比率
                    'k37' => $item[14],//血小板数
                    'k38' => $item[13],//红细胞
                    'k39' => $item[29],//红细胞分布宽度
                    'k40' => $item[12],//白细胞
                    'k41' => $item[30],//红细胞分布幅-CV
                    'k42' => $item[29],//红细胞分布幅-SD
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
                    echo $ex->getMessage();return;
                }
            }
        }
        echo "导入成功";
    }
}
