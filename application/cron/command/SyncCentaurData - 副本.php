<?php

namespace app\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;

class SyncCentaurData extends Command
{

    protected function configure()
    {
        $this->setName('SyncCentaurData')->setDescription('this api is for SyncCentaurData')
            ->addArgument('business');//商户唯一标识;
    }

    //西门子ADVIA Centaur CP 数据自动同步
    protected function execute(Input $input, Output $output)
    {
        $absolutePath = 'D:\PHP\allProjectFile\tinengProject\datacenterFile\ahtks\centaur';
//        $absolutePath = '/workspace/wwwroot/datacenterfile/tnxlahtks/adviaCentaurCP/';
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
//                    if(strpos($file, '.xlsx') !== false){
                        //检查是否为同步过的文件
//                        if (strpos($file, 'Synced') !== false) {
//                            $file_name_arr[] = $file;
//                        }
                        //判断是否为需要转换格式的文件
                        if(strpos($file, '.xlsx') !== false){
                            //解析此文件
                        }else{
//                            if($file == '20240127.xls' || $file == '20240126.xls' || $file == '20200219.xls' || $file == '20240219.xls'){
//                                //文件类型无法解析//
//                                continue;
//                            }else{
//                                //文件另存为.xlsx格式
//                                // 源文件路径
//                                $sourceFilePath = $directoryPath . $file;
//                                // 目标文件路径
//                                $targetFilePath = $directoryPath . str_replace('.xls','.xlsx', $file);
//                                // 打开源文件
//                                $sourceFile = fopen($sourceFilePath, 'r');
//                                // 如果成功打开源文件
//                                if ($sourceFile) {
//                                    // 读取源文件内容
//                                    $content = fread($sourceFile, filesize($sourceFilePath));
//                                    // 关闭源文件
//                                    fclose($sourceFile);
//                                    // 创建或打开目标文件并写入内容
//                                    $targetFile = fopen($targetFilePath, 'w');
//                                    // 如果成功打开目标文件
//                                    if ($targetFile) {
//                                        // 将源文件内容写入目标文件
//                                        fwrite($targetFile, $content);
//                                        // 关闭目标文件
//                                        fclose($targetFile);
//                                        continue;
////                                        $file = str_replace('.xls','.xlsx', $file);
////                                        echo '文件已成功另存为。';
//                                    }
//                                }
//                            }
                        }
                        $file_name_arr[] = $file;
                    }
                }
            }
        }
        var_dump($file_name_arr);die;
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
            if($file_name != '20240304test.xlsx'){
                continue;
            }
            //文件绝对路径
            $file_path_ab = $directoryPath . $file_name;
            $pathinfo = pathinfo($file_path_ab);
//            if (isset($pathinfo['extension']) && $pathinfo['extension'] == 'xlsx') {
            //兼容多种excel文件
//            $inputFileType = \PHPExcel_IOFactory::identify($file_path_ab);
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
//            } else {
//                $objReader = \PHPExcel_IOFactory::createReader('Excel5');
//            }
//            $inputFileType=\PhpOffice\PhpSpreadsheet\IOFactory::identify($filename);
//            $reader=\PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
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
            foreach ($excel as $index => $item) {
                if ($index == 0) {
//                    var_dump($item);die;
                    continue;
                }
                if(empty($item[1])){
                    continue;
                }
                //样本号信息
                $test_date = date('Y-m-d', strtotime($item[1]));
                //如果样本测试过——————暂不同步
                $ori_where = ['relation_type' => 21, 'date' => $test_date, 'k5' => trim($item[3])];
                $specimen_ori = $db_data_center->name('equipment_result')->where($ori_where)->count();
                if (!empty($specimen_ori)) {
                    continue;
                }
                if(!isset($all_staff_info[$item[3]])){
                    $all_staff_info[$item[3]]['department_uuid'] = null;
                    $all_staff_info[$item[3]]['uuid'] = null;
                }
                foreach ($item as $k => $v){
                    if(empty($v)){
                        $item[$k] = '';
                    }
                }
                //结果表数据
                $result_info = array(
                    'business' => $business,
                    'equipment_id' => 25,
                    'relation_type' => 21,
                    'department_uuid' => $all_staff_info[$item[3]]['department_uuid'],
                    'staff_uuid' => $all_staff_info[$item[3]]['uuid'],
                    'date' => $test_date,
                    'k1' => $test_date,
//                    'k2' => '',
                    'k3' => $item[0],//仪器
                    'k4' => $item[2],//样本号（标本号
                    'k5' => $item[3],//姓名（人员编号
                    'k6' => $item[4],//病历号
                    'k7' => $item[5],//病人类型
                    'k8' => $item[6],//性别
                    'k9' => $item[7],//年龄
                    'k10' => $item[8],//科室
                    'k11' => $item[9],//床号
                    'k12' => $item[10],//送检医生
                    'k13' => $item[11],//送检日期
                    'k14' => $item[12],//操作员
                    'k15' => $item[13],//报告日期
                    'k16' => $item[14],//标本
                    'k17' => $item[15],//费别
                    'k18' => $item[16],//核对人
                    'k19' => $item[17],//诊断
                    'k20' => $item[18]//备注
                );
                $result_info_extend = array(
                    'k21' => $item[21],//
                    'k22' => $item[22],//
                    'k23' => $item[23],//
                    'k24' => $item[24],//
                    'k25' => $item[25],//
                    //$item[26]-FT3
                    'k26' => $item[27],//FrT4
                    'k27' => $item[28],//
                    'k28' => $item[29],//
                    'k29' => $item[30],//
                    'k30' => $item[31],//
                    'k31' => $item[32],//
                    'k32' => $item[33],//
                    'k33' => $item[34],//
                    'k34' => $item[35],//
                    'k35' => $item[36],//
                    'k36' => $item[37],//
                    'k37' => $item[38],//
                    'k38' => $item[39],//
                    'k39' => $item[40],//铁蛋白-FERRITIN-$item[40]
                    //$item[41]-血B2-Mg
                    'k40' => $item[42],//尿B2-MG
                    'k41' => $item[43],//
                    'k42' => $item[44],//
                    'k43' => $item[45],//
                    'k44' => $item[46],//
                    'k45' => $item[47],//
                    'k46' => $item[48],//
                    'k47' => $item[49],//
                    'k48' => $item[50],//
                    'k49' => $item[51],//
                    'k50' => $item[52],//TESTO
                    'k51' => $item[53],//
                    'k52' => $item[54],//
                    'k53' => $item[55],//
                    'k54' => $item[56],//COR
                    'k55' => $item[57],//TSTII
                    'k56' => $item[58],//
                    'k57' => $item[26],//FT3
                    'k58' => $item[41]//血B2-Mg
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
