<?php

namespace app\cron\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use Bluerhinos\phpMQTT;
use think\Db;
use think\Exception;

class Mqtt extends Command{

    protected function configure() {
        $this->setName('Mqtt')->setDescription('this api is for Mqtt')
            ->addArgument('business')//商户唯一标识;
            ->addArgument('op');//操作项
    }

    /**
     * tablestore数据同步脚本
     * php think SyncTableData sxkx 0 定时同步脚本-concept2 每分钟执行一次
     * php think SyncTableData sxkx 1 初始化数据脚本-concept2
     * php think SyncTableData sxkx 2 初始化数据脚本-wattbike
     * php think SyncTableData sxkx 3 定时同步脚本-wattbike 每分钟执行一次
     */
    protected function execute(Input $input, Output $output) {
        echo date('Y-m-d H:i:s'). ' 数据开始同步' . PHP_EOL;
        $business = $input->getArgument('business');
        $op = $input->getArgument('op');
        $op = empty($op) ? 0 : $op;
        switch ($op) {
           /* case 0:
                //定时同步脚本-concept2
                $this->sysc_concept2_data();
                break;
            case 1:
                //初始化数据脚本-concept2
                $this->init_concept2_data();
                break;*/
            case 2:
                //初始化数据脚本-wattbike
                $this->init_wattbike_data($business);
                break;
            case 3:
                //定时同步脚本-wattbike
                $this->sysc_wattbike_data($business);
                break;
            case 4:
                //定时同步脚本-泰诺健划船机
                $this->sysc_skillrow_data($business);
                break;
            case 5:
                //定时同步脚本-攀爬机
                $this->sysc_climber_data($business);
                break;
            default:
                break;
        }
        echo date('Y-m-d H:i:s'). ' 数据同步完成' . PHP_EOL;
    }


    //定时同步脚本-wattbike
    public function sysc_wattbike_data($business) {
        $server   = "iot-06z00dvg25czqks.mqtt.iothub.aliyuncs.com"; // MQTT服务器地址
        $port     = 1883;                                // MQTT服务器端口
        $username = "new-sxWattbike&hu5qvSNwGOd"; // 用户名
        $password = "5c284e7b8521bfc7f089bd890b347c1ca4beb45980d9a967bf9ae5f82d4db8f5";                // 密码，通常为设备的DeviceSecret
        $client_id = "hu5qvSNwGOd.new-sxWattbike|securemode=2,signmethod=hmacsha256,timestamp=1722333535513|";                // 客户端ID

        $mqtt = new phpMQTT($server, $port, $client_id);
        if (!$mqtt->connect(true, NULL, $username, $password)) {
            exit(1);
        }
        echo 111;die;
        $topic = "/{hu5qvSNwGOd}/{new-sxWattbike}/user/get"; // 订阅的Topic
        $mqtt->subscribe($topic, 0);
        while ($mqtt->proc()) {
            // 处理接收到的消息
            if ($mqtt->messages) {
                foreach ($mqtt->messages as $key => $message) {
                    echo "Received message on topic {$key}: {$message->payload}\n";
                    $mqtt->close();
                }
            }
        }

        $mqtt->close();
        echo date('Y-m-d H:i:s') . ' count:' . 1 .  PHP_EOL;
    }
}
