<?php
/**
 * Created by PengJu
 * User: RenPengJu
 * Motto: 现在的努力是为了小时候吹过的牛逼
 * Time: 2024/7/31/15:59
 */

namespace iot;
require EXTEND_PATH.'vendor/autoload.php';
use Stomp\Client;
use Stomp\Exception\StompException;
use Stomp\Network\Observer\Exception\HeartbeatException;
use Stomp\Network\Observer\ServerAliveObserver;
use Stomp\StatefulStomp;
use think\Exception;
class IotServer
{
    private $accessKey = 'LTAI5tAX8ZzuEaV3CfNfHdse';
    private $accessSecret = 'PdzdYCaJ5ZkXV4d7DmWvPaXb8LudMP';
    private $consumerGroupId = '';
    private $clientId = '4825A5CA-5E74-BBCD-9BEF-2565A6681071'; // 自定义的uuid
    private $iotInstanceId = 'iot-06z00dvg25czqks';
    private $type = '';
    private $connection = null;
    private static $instance;

    private function __construct($consumerGroupId,$connection,$type = '',$clientId)
    {
        $this->consumerGroupId = $consumerGroupId;
        $this->connection = $connection;
        $this->type = $type;
        $this->clientId = $clientId;
    }

    public static function getInstance($consumerGroupId,$connection,$type = '',$clientId = '')
    {
        if (!self::$instance) {
            self::$instance = new IotServer($consumerGroupId,$connection,$type,$clientId);
        }
        return self::$instance;
    }

    public function operateIotServer()
    {
        //参数说明，请参见AMQP客户端接入说明文档。
// 工程代码泄露可能会导致 AccessKey 泄露，并威胁账号下所有资源的安全性。以下代码示例使用环境变量获取 AccessKey 的方式进行调用，仅供参考
        $accessKey = $this->accessKey;
        $accessSecret = $this->accessSecret;
        $consumerGroupId = $this->consumerGroupId;
        $clientId = $this->clientId;
//iotInstanceId：实例ID。
        $iotInstanceId = $this->iotInstanceId;
        $timeStamp = round(microtime(true) * 1000);
//签名方法：支持hmacmd5，hmacsha1和hmacsha256。
        $signMethod = "hmacsha1";
//userName组装方法，请参见AMQP客户端接入说明文档。
//若使用二进制传输，则userName需要添加encode=base64参数，服务端会将消息体base64编码后再推送。具体添加方法请参见下一章节“二进制消息体说明”。
        $userName = $clientId . "|authMode=aksign"
            . ",signMethod=" . $signMethod
            . ",timestamp=" . $timeStamp
            . ",authId=" . $accessKey
            . ",iotInstanceId=" . $iotInstanceId
            . ",consumerGroupId=" . $consumerGroupId
            . "|";
        $signContent = "authId=" . $accessKey . "&timestamp=" . $timeStamp;
//计算签名，password组装方法，请参见AMQP客户端接入说明文档。
        $password = base64_encode(hash_hmac("sha1", $signContent, $accessSecret, $raw_output = TRUE));
//接入域名，请参见AMQP客户端接入说明文档。
        $client = new Client('ssl://iot-06z00dvg25czqks.amqp.iothub.aliyuncs.com:61614');
        $sslContext = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => false], ];
        $client->getConnection()->setContext($sslContext);

//服务端心跳监听。
        $observer = new ServerAliveObserver();
        $client->getConnection()->getObservers()->addObserver($observer);
//心跳设置，需要云端每30s发送一次心跳包。
        $client->setHeartbeat(0, 30000);
        $client->setLogin($userName, $password);
        try {
            $client->connect();
        }
        catch(StompException $e) {
            echo "failed to connect to server, msg:" . $e->getMessage() , PHP_EOL;
        }
//无异常时继续执行。
        $stomp = new StatefulStomp($client);
        $stomp->subscribe('/topic/#');
//        echo "connect success";
        while (true) {
            try {
                // 检查连接状态
                if (!$client->isConnected()) {
                    echo "connection not exists, will reconnect after 10s.", PHP_EOL;
                    sleep(2);
                    $client->connect();
                    $stomp->subscribe('/topic/#');
                    echo "connect success", PHP_EOL;
                }
                //处理消息业务逻辑。
                $read = $stomp->read();
                if (!empty($read)){
                    $msg = $read->getBody();
                    if (!empty($this->type) && strpos($msg, $this->type) !== false) {
                        $this->connection->send($msg);
                    }elseif (!empty($this->type) && $this->type == 'wattbike'){ // 处理wattbike数据
                        $json_msg = json_decode($msg,true);
                        $data = [];
                        if (isset($json_msg['checkFailedData'])){
                            foreach ($json_msg['checkFailedData'] as $key => $value){
                                $data[$key] = $value['value'];
                            }
                        }
                        $msg_data = json_encode($data);
                        $this->connection->send($msg_data);
                    }elseif (empty($this->type)){
                        $this->connection->send($msg);
                    }
                }
            }
            catch(HeartbeatException $e) {
                echo 'The server failed to send us heartbeats within the defined interval.', PHP_EOL;
                $stomp->getClient()->disconnect();
            } catch(Exception $e) {
                echo 'process message occurs error: '. $e->getMessage() , PHP_EOL;
                $stomp->getClient()->disconnect();
            }

        }
    }
    public function __clone() {
        trigger_error("Clone is not allowed!");
    }

}