<?php
namespace table;

require (__DIR__ . '/../vendor/autoload.php');

use Aliyun\OTS\Consts\DirectionConst;
use Aliyun\OTS\OTSClient as OTSClient;
use Aliyun\OTS\OTSServerException;
use think\Exception;

class TableServer {
    //参考文档
    //https://help.aliyun.com/zh/tablestore/developer-reference/read-data-2
    //错误码
    //https://help.aliyun.com/zh/tablestore/developer-reference/error-codes

    private $accessKeyId = '';//阿里云账号AccessKeyId
    private $accessKeySecret = '';//阿里云账号AccessKeySecret
    private $endpoint = '';//阿里云接入点地址
    private $instanceName = '';//实例名称
    private $tableName = '';//table
    private $client = null;

    public function __construct($instanceName = '', $tableName = '') {
        ini_set('memory_limit','256M');

        $tablestore = config('tablestore');
        $this->accessKeyId = $tablestore['accessKeyId'];
        $this->accessKeySecret = $tablestore['accessKeySecret'];
        $this->instanceName = !empty($instanceName) ? $instanceName : $tablestore['instanceName'];
        $this->tableName = !empty($tableName) ? $tableName : $tablestore['tableName'];
        $this->endpoint = sprintf('https://%s.cn-shanghai.ots.aliyuncs.com', $this->instanceName);
        $this->createClient();
    }

    private function createClient() {
        if (is_null($this->client)){
            $this->client = new OTSClient (array (
                'EndPoint' => $this->endpoint,
                'AccessKeyID' => $this->accessKeyId,
                'AccessKeySecret' => $this->accessKeySecret,
                'InstanceName' => $this->instanceName
            ));
            //关掉日志打印
            $this->client->getClientConfig()->errorLogHandler = null;
            $this->client->getClientConfig()->debugLogHandler = null;
        }
        return $this->client;
    }

    //获取返回
    public function getRange($startPK, $endPK) {
        $data = [
            'code'    => 0,
            'message' => '获取成功',
            'data'    => []
        ];
        try {
            $table_data = [];
            while (!empty($startPK)) {
                $request = array(
                    'table_name' => $this->tableName,
                    'max_versions' => 1,
                    'direction' => DirectionConst::CONST_FORWARD, // 方向可以为 FORWARD 或者 BACKWARD
                    'inclusive_start_primary_key' => $startPK, // 开始主键 前闭后开
                    'exclusive_end_primary_key' => $endPK, // 结束主键
                    'limit' => 10
                );
                $response = $this->client->getRange($request);
                \think\Log::write('table_response:'.count($response['rows']));
                foreach ($response['rows'] as $rowData) {
                    $row = [];
                    foreach ($rowData['primary_key'] as $primary_key) {
                        $row[$primary_key[0]] = $primary_key[1];
                    }
                    foreach ($rowData['attribute_columns'] as $attribute_column) {
                        $row[$attribute_column[0]] = $attribute_column[1];
                    }
                    $table_data[] = $row;
                }
                $startPK = $response['next_start_primary_key'];
            }
            $data['data'] = $table_data;
        } catch (OTSServerException $e) {
            $error = [
                'status' => $e->getHttpStatus(),
                'code' => $e->getOTSErrorCode(),
                'message' => $e->getOTSErrorMessage(),
                'requestid' => $e->getRequestId(),
            ];
            \think\Log::write('table_error:'.json_encode($error));
            $data = [
                'code'    => 1,
                'message' => $error['message'],
                'data'    => []
            ];
        }
        return $data;
    }
}

