<?php

namespace app\cron\command;
use app\p\logic\EquipmentRecord as EquipmentLogic;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\model\DetectionInfo;
use app\model\DetectionItem;
use app\model\Specimen;
use app\model\HardwareData as HardwareDataModel;
use app\model\Staff as StaffModel;
use app\model\Bc5385crp as Bc5385crpModel;

use think\Db;
use think\Exception;

class Syncbc5385crpData extends Command{

    protected function configure() {
        $this->setName('Syncbc5385crpData')->setDescription('this api is for Syncbc5385crpData');
    }

    /**
     * KX21N数据同步脚本
     * php think Syncbc5385crpData 每分钟执行一次
     */
    protected function execute(Input $input, Output $output) {
        echo date('Y-m-d H:i:s'). ' 数据开始同步' . PHP_EOL;
        // 指定目录路径 $directoryPath
        $directoryPath = 'public/static/sync/excel/';
        // 使用file_exists函数来检查目录是否存在

//        if (file_exists($directoryPath) && is_dir($directoryPath)) {
//            //echo '目录存在';
//            echo 2223;die;
//        } else {
//            echo 32232;die;
//            return false;//echo '目录不存在';
//
//        }
        // 打开目录
        $directory = opendir($directoryPath);
        if (!$directory) {
            return false;//die('无法打开目录！');
        }
        $index_num = 0;
        $file_name_arr = array();
//        $this->logic = new EquipmentLogic();
//        $this->model = new HardwareModel();
        $this->staffModel = new StaffModel();
//        $this->detectionInfoModel = new DetectionInfo();
//        $this->detectionItemModel = new DetectionItem();
//        $this->specimenModel = new Specimen();
        $this->Bc5385crpModel = new Bc5385crpModel();
        $this->HardwareDataModel = new HardwareDataModel();
        // 遍历目录中的文件
        //var_dump(readdir($directory));die;
        while (($file = readdir($directory)) !== false) {
            // 排除当前目录和上级目录
            if ($file != "." && $file != "..") {
                // 检查文件类型
                if (is_file($directoryPath . $file)) {
                    if(strpos($file, '.csv') !== false ||strpos($file, '.xls') !== false || strpos($file, '.XLS') !== false || strpos($file, '.xlsx') !== false){
                        $file_name_arr[] = $file;
                    }
                }
            }
        }
        // 关闭目录
        closedir($directory);
        //获取所有人员数据
        //$all_staff_info = $this->staffModel->inquiryColumn(['del_flag' => 0],'name,uuid');
        //获取所有病人类型
        //$person_type_arr = $this->logic->departmentTypeList();
        foreach ($file_name_arr as $file_name) {
            $file_path = $directoryPath . $file_name;
            //文件绝对路径
            $file_path_ab = ROOT_PATH . '/' . $file_path;
//            $pathinfo = pathinfo($file_path_ab);
//            if (isset($pathinfo['extension']) && $pathinfo['extension'] == 'xlsx') {
//                $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
//            } else {
//                $objReader = \PHPExcel_IOFactory::createReader('Excel5');
//            }
//            $obj_PHPExcel = $objReader->load($file_path_ab);
//            $excel = $obj_PHPExcel->getsheet(0)->toArray();



//$csvString = file_get_contents($file_path_ab);
//$delimiter = "\t";
//$lines = explode(PHP_EOL, $csvString);
            $file = fopen($file_path_ab, 'r');
            $delimiter = ";";
//            if (count($excel) < 2) {
//                return array("code"=>1, "message"=>"导入失败：数据为空", "data"=>[]);
//            }
            //检测项信息
//            $test_items = array_slice($excel[0],12,30);
//            $model_num = $excel[1][0];
            //$detection_items = $this->detectionItemModel->inquiryAll(['model_num' => trim($model_num),'test_item' => ['in', $test_items], 'is_del' => 0]);
            //$detection_items = array_column($detection_items, null, 'test_item');
            //$equipment = $this->model->inquiryOne(['model_num' => $model_num, 'is_show' => 1]);
            //设备信息
           // $excel = fgetcsv($file);
            $specimen_arr = array();
            $detection_infos_arr = array();
            if ($file) {
                $index = 0; // 计数器变量
                while ($item = fgets($file)) {
                    $index++; // 递增计数器
                    if ($index == 1) {
                        continue;
                    }
                    //var_dump($item);die;
//                    $item = mb_convert_encoding($item, 'utf-8', 'UTF-16LE');
//                    $item = str_getcsv($item,'  ','"');
//                    var_dump($item);die;
                    // 使用 preg_replace_callback() 结合回调函数替换引号内的 \t
                    $item = preg_replace_callback('/"(.*?)"/', function($matches) {
                        return str_replace('	', '', $matches[0]);
                    }, $item);

                    $item = str_replace('"', "",$item);
                    $item = explode("	",$item);

                    //如果样本测试过——————暂不同步
                    //根据人员信息检查是否为重复数据
                    //$ori_where = ['date' => $test_date, 'specimen_no' => trim($item[4]), 'is_del' => 0];
//                $specimen_ori = $this->specimenModel->inquiryOne($ori_where);
//                if (!empty($specimen_ori)) {
//                    continue;
//                }
//                    var_dump($item[34]);
//                    $a = str_replace(' ', '', trim($item[34]));
//                    var_dump($a);
//                    var_dump(strlen($a));
//                    var_dump(mb_convert_encoding($item[34], 'utf-8', 'UTF-16LE'));die;
                    if(count($item)<5)
                        continue;
                    $specimen = [
                        'uuid' => guid(),
                        'department_uuid' => '369838AF-96F1-1CF0-6650-C279FAA84536',
                        'staff_uuid' => 'A89E14C6-9412-04DA-38D7-FD5AF01AECC8',
                        'code' => trim($item[0]),
                        'date' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', str_replace('/','-',trim($item[1]))),
                        'Patient_type' => trim($item[2]),
                        'record_number' => trim($item[3]),
                        'name' => trim($item[4]),
                        'sex' => trim($item[5]),
                        'birthday' => trim($item[6]),
                        'age' => trim($item[7]),
                        'age_unit' => trim($item[8]),
                        'fee_type' => trim($item[9]),
                        'department' => trim($item[10]),
                        'bed_number' => trim($item[11]),
                        'sample_time' => trim($item[12]),
                        'inspection_time' => trim($item[13]),
                        'submitter' => trim($item[14]),
                        'sample_type' => trim($item[15]),
                        'clinical_diagnosis' => trim($item[16]),
                        'notes' => trim($item[17]),
                        'reference_group' => '通用',//iconv("UTF-16LE", "UTF-8", $item[18]),
                        'examiner' => trim($item[19]),
                        'ward' => trim($item[20]),
                        'report_time' => trim($item[21]),
                        'reviewer' => trim($item[22]),
                        'Custom1' => trim($item[23]),
                        'Custom2' => trim($item[24]),
                        'Custom3' => trim($item[25]),
                        'injection_mode' => '自动',
                        'blood_sample_pattern' => '静脉全血',
                        'analysis' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[28])),
                        'test_tube_number' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[29])),
                        'pipe_rack_number' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[30])),
                        'test_time' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[31])),
                        'WBC' => preg_replace('/[^\x{4e00}-\x{9fa5}A-Za-z0-9.\+-:]/u', '', trim($item[32])),
                        'NEUa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[33])),
                        'LYMa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[34])),
                        'MONa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[35])),
                        'EOSa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[36])),
                        'BASa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[37])),
                        'NEUb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[38])),
                        'LYMb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[39])),
                        'MONb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[40])),
                        'EOSb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[41])),
                        'BASb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[42])),
                        'RBC' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[43])),
                        'HGB' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[44])),
                        'HCT' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[45])),
                        'MCV' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[46])),
                        'MCH' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[47])),
                        'MCHC' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[48])),
                        'RDW-CV' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[49])),
                        'RDW-SD' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[50])),
                        'PLT' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[51])),
                        'MPV' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[52])),
                        'PDW' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[53])),
                        'PCT' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[54])),
                        'P-LCC' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[55])),
                        'P-LCR' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[56])),
                        'FR-CRP' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[57])),
                        'hs-CRP' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[58])),
                        'CRP' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[59])),
                        'ALYa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[60])),
                        'ALYb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[61])),
                        'LICa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[62])),
                        'LICb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[63])),
                        'NRBCb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[64])),
                        'NRBCa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[65])),
                        'NLR' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[66])),
                        'PLR' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[67])),
                        'MICROa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[68])),
                        'MICROb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[69])),
                        'MACROa' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[70])),
                        'MACROb' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[71])),
                        'custom_parameters' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[72])),
                        'Microscopic_examination_parameters' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[73])),
                        'WBC_Message' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[74])),
                        'RBC_Message' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[75])),
                        'PLT_Message' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[76])),
                        'CRP_Message' => preg_replace('/[^\x{4e00}-\x{9fa5}0-9.\+-:]/u', '', trim($item[77])),
                        'create_time' => date("Y-m-d H:i:s"),
                    ];
                    $device_data = [
                        'uuid' => guid(),
                        'department_uuid' => '369838AF-96F1-1CF0-6650-C279FAA84536',
                        'staff_uuid' => 'A89E14C6-9412-04DA-38D7-FD5AF01AECC8',
                        'date' => $specimen['date'],
                        'data_uuid'=>$specimen['uuid'],
                        'device_id'=>33,
                        'is_del'=>0,
                        'create_time'=>date("Y-m-d H:i:s")
                        ];

                    //赋值staff_uuid
//                if($specimen['staff_name']){
//                    //判断是否在系统中存在
//                    if(isset($all_staff_info[$specimen['staff_name']])){
//                        $specimen['staff_uuid'] = $all_staff_info[$specimen['staff_name']];
//                    }else{
//                        //不存在时则新增一个
//                        //创建人员
//                        $staff_res = $this->logic->getStaffCreate($specimen['staff_name']);
//                        if ($staff_res['code']) {
//                            return $staff_res;
//                        }
//                        $specimen['staff_uuid'] = $staff_res['data']['uuid'];
//                    }
//                }else{
//                    continue;
//                }

                    $specimen_arr[] = $specimen;
                    $specimen_data[] = $device_data;
                }
            }
            $index_num++;
            rename($file_path_ab, ROOT_PATH . '/' .'public/static/sync/source_excel/'.$file_name);
        }
        //var_dump($specimen_arr);die;
        if(!empty($specimen_arr)){
            $this->Bc5385crpModel->addAll($specimen_arr);
            //echo $this->Bc5385crpModel->getLastSql();die;
            $this->HardwareDataModel->addAll($specimen_data);
            //echo $this->Bc5385crpModel->getLastSql();die;
        }

        echo date('Y-m-d H:i:s'). ' 数据同步完成文件 count：' . $index_num . PHP_EOL;
    }
}
