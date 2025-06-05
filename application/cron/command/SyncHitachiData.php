<?php

namespace app\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;

class SyncHitachiData extends Command
{

    protected function configure()
    {
        $this->setName('SyncHitachiData')->setDescription('this api is for SyncHitachiData')
            ->addArgument('business');//商户唯一标识;
    }

    //日立7100 数据自动同步
    protected function execute(Input $input, Output $output)
    {
//        $absolutePath = 'D:\PHP\allProjectFile\tinengProject\datacenterFile\ahtks\kx21n';
        $absolutePath = '/workspace/wwwroot/datacenterfile/tnxlahtks/hitachi7100/';
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

    //西门子数据自动同步
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
            //处理表格数据
            $rows = array();
            $system_sync = 'system_sync';
            foreach ($excel as $index => $item) {
                if ($index < 3) {
                    continue;
                }
                if(empty($item[0])){
                    continue;
                }
                if(empty($item[4])){
                    //判断‘报告员：’这个字符是否在$item[4]中存在
                    if(strpos($item[0], '报告员：') !== false){
                        $system_sync = str_replace('报告员：', '', $item[0]);
                    }
                    continue;
                }
                //按人员将数据分组
                if(isset($rows[$item[4]])){
                    $rows[$item[4]]['data'][$item[2]] = trim($item[3]);
                }else{
                    $rows[$item[4]] = array(
                        'test_date' =>  date('Y-m-d', strtotime($item[0])),
                        'specimen_no' => $item[1],
                        'data' => array($item[2]=>trim($item[3])),
                        'staff_name' => $item[4],
                        'sex' => $item[5],
                        'age' => $item[6],
                        'department_name' => $item[7]
                    );
                }
            }
            foreach ($rows as $staff_name => $item) {
                //如果样本测试过——————暂不同步
                $ori_where = ['relation_type' => 7100, 'k5' => $item['test_date'], 'k8' => $staff_name];
                $specimen_ori = $db_data_center->name('equipment_result')->where($ori_where)->count();
                if (!empty($specimen_ori)) {
                    continue;
                }
                if(!isset($all_staff_info[$staff_name])){
//                    continue;
                    $all_staff_info[$staff_name]['department_uuid'] = '';
                    $all_staff_info[$staff_name]['uuid'] = '';
                }
                foreach ($item as $k => $v){
                    if(empty($v)){
                        $item[$k] = '';
                    }
                }
                //结果表数据
                $result_info = array(
                    'business' => $business,
                    'equipment_id' => 26,
                    'relation_type' => 7100,
                    'department_uuid' => $all_staff_info[$staff_name]['department_uuid'],
                    'staff_uuid' => $all_staff_info[$staff_name]['uuid'],
                    'date' => $item['test_date'],
//                    'k1' => '',
//                    'k2' => '',
//                    'k3' => '',
//                    'k4' => '',
                    'k5' => $item['test_date'],//检测日期
                    'k6' => $item['specimen_no'],//样本号
                    'k7' => $all_staff_info[$staff_name]['uuid'],//检测人uuid
                    'k8' => $staff_name,//检测人
                    'k9' => $item['sex'],//检测人性别
                    'k10' => $item['age'],//检测人年龄
                    'k11' => $item['department_name'],//样本类型
                    'k12' => $all_staff_info[$staff_name]['department_uuid'],//部门
                    'k13' => $file_path_ab,//文件路径
                    'k14' => $system_sync,//操作员
                    'k15' => $item['test_date'],//报告日期
                    'k16' => 0//是否删除1是否0
                );
                $result_info_extend = array(
                    'k21' => isset($item['data']['ALT']) ? $item['data']['ALT'] : '',//谷丙转氨酶-ALT
                    'k22' => isset($item['data']['AST']) ? $item['data']['AST'] : '',//谷草转氨酶-AST
                    'k23' => isset($item['data']['AST/ALT']) ? $item['data']['AST/ALT'] : '',//谷草/谷丙-AST/ALT
                    'k24' => isset($item['data']['TP']) ? $item['data']['TP'] : '',//总蛋白-TP
                    'k25' => isset($item['data']['ALB']) ? $item['data']['ALB'] : '',//白蛋白-ALB
                    'k26' => isset($item['data']['GLB']) ? $item['data']['GLB'] : '',//球蛋白-GLB
                    'k27' => isset($item['data']['A/G']) ? $item['data']['A/G'] : '',//白球比-A/G
                    'k28' => isset($item['data']['TBIL']) ? $item['data']['TBIL'] : '',//总胆红素-TBIL
                    'k29' => isset($item['data']['DBIL']) ? $item['data']['DBIL'] : '',//直接胆红素-DBIL
                    'k30' => isset($item['data']['IBIL']) ? $item['data']['IBIL'] : '',//间接胆红素-IBIL
                    'k31' => isset($item['data']['ALP']) ? $item['data']['ALP'] : '',//碱性磷酸酶-ALP
                    'k32' => isset($item['data']['GGT']) ? $item['data']['GGT'] : '',//谷既转肤酶-GGT
                    'k33' => isset($item['data']['GLU']) ? $item['data']['GLU'] : '',//葡萄糖-GLU
                    'k34' => isset($item['data']['BUN']) ? $item['data']['BUN'] : '',//尿素氨-BUN
                    'k35' => isset($item['data']['CREA']) ? $item['data']['CREA'] : '',//肌醉-CREA
                    'k36' => isset($item['data']['UA']) ? $item['data']['UA'] : '',//尿酸-UA
                    'k37' => isset($item['data']['CHO1']) ? $item['data']['CHO1'] : '',//总胆固醇-CHO1
                    'k38' => isset($item['data']['TG']) ? $item['data']['TG'] : '',//甘油三醋-TG
                    'k39' => isset($item['data']['HDL-C']) ? $item['data']['HDL-C'] : '',//高密度胆固醇-HDL-C
                    'k40' => isset($item['data']['LDL-C']) ? $item['data']['LDL-C'] : '',//低密度胆固醇-LDL-C
                    'k41' => isset($item['data']['CK']) ? $item['data']['CK'] : '',//磷酸肌酸激酶-CK
                    'k42' => isset($item['data']['LDH']) ? $item['data']['LDH'] : '',//乳酸脱氢酶-LDH
                    'k43' => isset($item['data']['CK-MB']) ? $item['data']['CK-MB'] : '',//肌酸激酶同工酶-CK-MB
                    'k44' => isset($item['data']['Mb']) ? $item['data']['Mb'] : '',//肌红蛋白-Mb
                    'k45' => isset($item['data']['HBsAg']) ? $item['data']['HBsAg'] : '',//乙肝表面抗原HBsAg
                    'k46' => isset($item['data']['Fer']) ? $item['data']['Fer'] : '',//铁蛋白Fer
                    'k47' => isset($item['data']['IgA']) ? $item['data']['IgA'] : '',//免疫球蛋白A
                    'k48' => isset($item['data']['IgG']) ? $item['data']['IgG'] : '',//免疫球蛋白G
                    'k49' => isset($item['data']['IgM']) ? $item['data']['IgM'] : '',//免疫球蛋白M
                    'k50' => isset($item['data']['C3']) ? $item['data']['C3'] : '',//补体C3b
                    'k51' => isset($item['data']['C4']) ? $item['data']['C4'] : '',//补体C4
                    'k52' => isset($item['data']['CRP']) ? $item['data']['CRP'] : ''//C反应蛋白
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
