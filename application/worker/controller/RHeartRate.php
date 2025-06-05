<?php
namespace app\worker\controller;

use iot\IotServer;
use think\worker\Server;
use Workerman\Worker;

class RHeartRate extends Server
{
    protected $socket = 'websocket://0.0.0.0:2361';
    private $consumerGroupId = '8aKI1VI7klKfxaDqswN3000100'; // 心率带
    private $clientId = '4825A5CA-5E74-BBCD-9BEF-2565A6681075';
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
        $iot = IotServer::getInstance($this->consumerGroupId,$connection,'',$this->clientId);
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
        $connection->send('连接断开');
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

    }

}