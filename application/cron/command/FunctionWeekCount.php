<?php

namespace app\cron\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;

class FunctionWeekCount extends Command
{

    protected function configure()
    {
        $this->setName('FunctionWeekCount')->setDescription('this api is for FunctionWeekCount')
            ->addArgument('business');//商户唯一标识;
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        echo date('Y-m-d H:i:s') . ' 数据开始统计' . PHP_EOL;
        $count = $this->weekCount();
        if ($count > 0) {
            echo date('Y-m-d H:i:s') . ' count:' . $count . PHP_EOL;
        }
        echo date('Y-m-d H:i:s') . ' 数据统计完成' . PHP_EOL;
    }

    //一周同步数据统计
    public function weekCount() {
        //上周周一至上周周日的日期
        $week_start = date('Y-m-d', strtotime("last week Monday"));
        $week_end = date('Y-m-d', strtotime("last week Sunday"));
        //连接数据中心数据库
        $db_data_center = get_db('data_center');
        $ori_where = ['relation_type' => 9, 'date' => ['between ', [$week_start, $week_end]]];
        //获取全部数据
        $all_data = $db_data_center->name('equipment_result')->where($ori_where)->select();
        $configInfo = $this->configInfo();
        $bussiness_list = $configInfo['business_list'];
        $equipment_list = $configInfo['equipment_list'];
        //根据商户做数据区分
        //根据机能设备做数据区分

        //整理数据格式

        //数据推送至微信群

        echo "统计完成";
    }

    public function configInfo(){
        $data = [];
        $data['business_list'] = array(
            //贵州体科所
            ''
            //安徽体科所
            //
        );
        $data['equipment_list'] = array(

        );
        return $data;
    }
}
