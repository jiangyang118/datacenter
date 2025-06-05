<?php
/**
 * Created by PengJu
 * User: RenPengJu
 * Motto: 现在的努力是为了小时候吹过的牛逼
 * Time: 2024/7/30/16:22
 */

namespace app\worker\controller;

use iot\IotServer;
use think\worker\Server;
use Workerman\Worker;

class RowingMachine extends Server
{
    protected $socket = 'websocket://0.0.0.0:2362';
    private $consumerGroupId = '01WMCSL6FePyMlfj72PZ000100'; // 划船机
    private $clientId = '4825A5CA-5E74-BBCD-9BEF-2565A6681072';
    protected $processes = 5;
    // 设置阈值
    protected $threshold = 4;
    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {
        // 划船机
        $type = 'publishTechSkillrowMessage';
        $iot = IotServer::getInstance($this->consumerGroupId,$connection,$type,$this->clientId);
        $iot->operateIotServer();
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
//        $connection->send('已建立连接');
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
//        $connection->send('连接断开');
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
//        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
        global $activeWorkers;
        $activeWorkers++;
        // 检查当前活跃的进程数是否超过阈值
        if ($activeWorkers > $this->threshold) {
            // 如果超过阈值，则重启 Worker
            Worker::stopAll();
            Worker::runAll();
        }
    }

}