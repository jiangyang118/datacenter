<?php

namespace ocr;

require_once __DIR__ . '/../vendor/autoload.php';

use AlibabaCloud\SDK\DocumentAutoml\V20221229\DocumentAutoml;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\DocumentAutoml\V20221229\Models\PredictTemplateModelRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;

class OCRServer {

    private static $ocrAccessKeyId = 'LTAI5t5nibDkQQzLAnWt4dHp';//阿里云账号AccessKeyId
    private static $ocrAccessKeySecret = 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf';//阿里云账号AccessKeySecret
    private static $ocrEndpoint = 'documentautoml.cn-beijing.aliyuncs.com';//阿里云OCR接入点地址
    private static $model = [
        '1' => '27130',//过程模版
        '2' => '27687',//结果模版
    ];

    /**
     * 使用AK&SK初始化账号Client
     * @return DocumentAutoml Client
     */
    private static $client = null;
    public static function createClient() {
        if (is_null(self::$client)){
            $config = new Config([
                "accessKeyId" => self::$ocrAccessKeyId,
                "accessKeySecret" => self::$ocrAccessKeySecret
            ]);
            $config->endpoint = self::$ocrEndpoint;
            self::$client = new DocumentAutoml($config);
        }
        return self::$client;
    }

    /**
     * ocr模版解析图片
     * @param $img_base64 string 图片base64字符串
     * @param $model_type int 模版类型1=过程模版2=结果模版
     * @return array
     */
    public static function run($img_base64, $model_type = 1) {
        $data = [
            'code'    => 0,
            'message' => '解析成功',
            'data'    => []
        ];
        $client = self::createClient();
        $predictTemplateModelRequest = new PredictTemplateModelRequest([
            "taskId" => self::$model[$model_type],
            "content" => "",
            "binaryToText" => true,
            "body" => $img_base64
        ]);
        try {
            $response = $client->predictTemplateModelWithOptions($predictTemplateModelRequest, new RuntimeOptions([]));
            \think\Log::write('ocr_res:'.json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if ($response->statusCode != 200 || $response->body->code != 200) {
                $data = [
                    'code'    => 1,
                    'message' => '解析失败：' . $response->body->message,
                    'data'    => []
                ];
            } else {
                $data['data'] = self::formatData($response->body->data);
            }
        } catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            \think\Log::write('ocr_error:'.json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $data = [
                'code'    => 1,
                'message' => '解析失败：' . $error->message,
                'data'    => []
            ];
        }
        return $data;
    }

    //数据格式处理-获取有用数据
    private static function formatData($body_data) {
        $data['score'] = $body_data['score'];
        foreach ($body_data['data'] as $item) {
            //列表值
            if (in_array($item['fieldName'], ['list1', 'list2', 'list3'])) {
                $data[$item['fieldName']] = array_column($item['wordInfo'], 'word');
            } else {
                $data[$item['fieldName']] = $item['fieldWord'];
            }
        }
        return $data;
    }

}

