<?php

namespace app\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;

class SyncUrit8400Data extends Command
{

    protected function configure()
    {
        $this->setName('SyncUrit8400Data')->setDescription('this api is for SyncUrit8400Data')
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

    //数据自动同步
    public function syncData($directoryPath='E:\Desktop\秦皇岛训练基地\/', $business='qhdkx') {
        if(!$business){
            $business='qhdkx';
        }
        // 指定目录路径 $directoryPath
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
                    if(strpos($file, '.txt') !== false){
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
            ->where(['a.is_show' => 1, 'a.identity_number'=>['neq','']])->column('name,a.uuid,b.department_uuid','identity_number');
        //字段对应表
        $relation_res = $db_mysql->name('equipment_relation')->where(['type' => 8400])->select();
        $relation_arr = array('result'=>[],'process'=>[]);
        foreach ($relation_res as $value){
            $relation_arr[$value['k']] = $value['v'];
        }
        foreach ($file_name_arr as $file_name){
            $excel = array();
            // 文件绝对路径
            $file_path_ab = $directoryPath . DIRECTORY_SEPARATOR . $file_name;
            // 检查是否是.txt文件
            if (!file_exists($file_path_ab) || pathinfo($file_path_ab, PATHINFO_EXTENSION) !== 'txt') {
                return ['error' => '文件不存在或不是有效的TXT文件'];
            }
            $excel = [];
            $handle = fopen($file_path_ab, "r");
            if ($handle) {
                // 添加编码转换过滤器（假设原文件是 GBK）
                stream_filter_append($handle, 'convert.iconv.GBK/UTF-8');
                while (($line = fgets($handle)) !== false) {
                    $line = trim($line);
                    if ($line !== '') {
                        $excel[] = $line;
                    }
                }
                fclose($handle);
            } else {
                return ['error' => '无法打开文件'];
            }
            if (count($excel) < 2) {
//                return array("code"=>1, "message"=>"导入失败：数据为空", "data"=>[]);
                echo "导入失败：文件‘".$file_name."’数据为空";continue;
            }
            foreach ($excel as $index => $item) {
                if ($index == 0) {
                    continue;
                }
            }
            $excel_res = [];
            //原始数据处理
            foreach ($excel as $index => $item_str) {
                // 使用正则表达式分割多个空格
                $data = preg_split('/\s+/', $item_str);
                // 过滤掉空元素
                $data = array_values(array_filter($data));
                if(in_array(count($data),[5,6,7])){
                    $date = date('Y-m-d', strtotime($data[count($data)-3]));//固定倒数第三位为测试日期
                    if(!$date || strtotime($date) < strtotime('-2 week')){
                        //测试日期为空，不做数据同步+//日期为两周前
                        continue;
                    }
                    //详细测试数组
                    // 去除开头的冒号和结尾的分号
                    $str = trim(end($data), ":;");
                    if(!$str){
                        //测试结果为空，不做数据同步
                        continue;
                    }
                    // 按分号拆分成多个键值对
                    $pairs = explode(";", $str);
                    // 初始化结果数组
                    $details = [];
                    //遍历每一对 key=value
                    foreach ($pairs as $pair) {
                        list($key, $value) = explode("=", $pair, 2);
                        $details[trim($key)] = trim($value);
                    }
                    //基础元素对应
                    $item = array(
                        'id' => $data[0],
                        'staff_code' => in_array($data[1], ['男', '女']) ? '' :  $data[1],//测试人员数据为空
                        'date' => $date,
                        'details' => $details
                    );
                    $excel_res[] = $item;
                }
            }
            //处理表格数据
            foreach ($excel_res as $index => $item) {
                //如果样本测试过——————暂不同步
                $ori_where = ['relation_type' => 8400, 'date' => $item['date'], 'k8' => trim($item['staff_code']), 'k19' => 0];
                $specimen_ori = $db_data_center->name('equipment_result')->where($ori_where)->count();
                if (!empty($specimen_ori)) {
                    continue;
                }
                if(!isset($all_staff_info[$item['staff_code']])){
                    $all_staff_info[$item['staff_code']]['department_uuid'] = '';
                    $all_staff_info[$item['staff_code']]['uuid'] = '';
                }
                //结果表数据
                $result_info = array(
                    'business' => $business,
                    'equipment_id' => 12,
                    'relation_type' => 8400,
                    'department_uuid' => $all_staff_info[$item['staff_code']]['department_uuid'],
                    'staff_uuid' => $all_staff_info[$item['staff_code']]['uuid'],
                    'date' => $item['date'],//日期：05/30/2024
                    'record_time' => $item['date'],//日期：05/30/2024
//                    'k1' => '',
//                    'k2' => '',
//                    'k3' => '',
//                    'k4' => '',
                    'k5' => $item['date'],//日期：05/30/2024
                    'k6' => $item['staff_code'],//样本号：216
                    'k7' => $all_staff_info[$item['staff_code']]['uuid'],//检测人uuid
                    'k8' => $item['staff_code'],//检测人-编号：Specimen_ID
                    'k9' => '',//检测时间：16:40
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
                $details = $item['details'];
                $result_info_extend = array(
                    'k21' => isset($details['UREA']) ? trim($details['UREA']) : '',//UREA——尿素
                    'k22' => isset($details['CRE']) ? trim($details['CRE']) : '',//CRE——肌酐
                    'k23' => isset($details['GLU']) ? trim($details['GLU']) : '',//GLU——葡萄糖
                    'k24' => isset($details['ACE']) ? trim($details['ACE']) : '',//ACE——血管紧张素转换酶
                    'k25' => isset($details['CK']) ? trim($details['CK']) : '',//CK——肌酸激酶
                    'k26' => isset($details['LDH']) ? trim($details['LDH']) : '',//LDH——乳酸脱氢酶
                    'k27' => isset($details['CK_MB']) ? trim($details['CK_MB']) : '',//CK_MB——肌酸激酶同工酶
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

    function parseFixedWidthLine($row) {
        // 按照固定宽度截取字段
        $fields = [
            'id'      => substr($row, 0, 12),
            'name'    => substr($row, 12, 10),
            'gender'  => substr($row, 22, 2),
            'team'    => substr($row, 24, 20),
            'date'    => substr($row, 44, 10),
            'server'  => substr($row, 54, 10),
            'details' => trim(substr($row, 64)),
        ];
        foreach ($fields as &$value) {
            $value = trim($value);
        }
        // 解析 details 参数（可选）
        if (!empty($fields['details'])) {
            parse_str(str_replace(';', '&', $fields['details']), $params);
            $fields['params'] = $params;
        }
        return $fields;
    }
}
