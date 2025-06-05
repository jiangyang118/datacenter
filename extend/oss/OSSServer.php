<?php
namespace oss;

require_once EXTEND_PATH . 'aliyun-oss-php-sdk/autoload.php';

use app\model\Config;
use OSS\OssClient;
use OSS\Core\OssException;
use think\Exception;

class OSSServer {

    private $ossDisjunctor = 0;//阿里云OSS开关
    private $ossAccessKeyId = '';//阿里云账号AccessKeyId
    private $ossAccessKeySecret = '';//阿里云账号AccessKeySecret
    private $ossEndpoint = '';//阿里云OSS接入点地址
    private $ossBucket = '';//阿里云OSSBucket
    private $ossHost = '';//阿里云OSSHost
    private $businessIdentify = '';//商户名称唯一标识

    private static $instance = null;
    public static function getInstance(){
        if (is_null(self::$instance)){
            self::$instance = new self(); //实例化自己
        }
        return self::$instance;
    }

    private function __construct() {
        //图片oss配置
        $config_keys = [
            'ossDisjunctor',
            'ossAccessKeyId',
            'ossAccessKeySecret',
            'ossEndpoint',
            'ossBucket',
            'ossHost',
            'businessIdentify',
        ];
        $configModel = new Config();
        $oss = $configModel->getConfig($config_keys);
        if (!empty($oss)) {
            $this->ossDisjunctor = $oss['ossDisjunctor'];
            $this->ossAccessKeyId = $oss['ossAccessKeyId'];
            $this->ossAccessKeySecret = $oss['ossAccessKeySecret'];
            $this->ossEndpoint = $oss['ossEndpoint'];
            $this->ossBucket = $oss['ossBucket'];
            $this->ossHost = $oss['ossHost'];
            $this->businessIdentify = $oss['businessIdentify'];
        }
    }

    public function getConfig() {
        $fileUrlPrefix = $this->ossHost . DS . $this->businessIdentify . DS;
        if (empty($this->ossDisjunctor)) {
            $fileUrlPrefix = $this->ossHost . DS;
        }
        $oss = array(
            'ossDisjunctor' => $this->ossDisjunctor,//阿里云OSS开关
            'ossAccessKeyId' => $this->ossAccessKeyId,//阿里云账号AccessKeyId
            'ossAccessKeySecret' => $this->ossAccessKeySecret,//阿里云账号AccessKeySecret
            'ossEndpoint' => $this->ossEndpoint,//阿里云OSS接入点地址
            'ossBucket' => $this->ossBucket,//阿里云OSSBucket
            'ossHost' => $this->ossHost,//阿里云OSSHost
            'businessIdentify' => $this->businessIdentify,//商户名称唯一标识
            'fileUrlPrefix' => $fileUrlPrefix,//图片路径前缀
        );
        return $oss;
    }

    /**
     * 上传文件到oss
     * @param string $file_tmp       临时图片路径
     * @param string $filePath       文件路径
     * @param string $sourceFilePath 原文件路径
     * @param boolean $compress      上传后文件(图片),是否需要缩略图片，默认缩小，不需要则传入false
     * @param array $compressSize    压缩的尺寸，图片的最大宽度与最大高度
     * @param boolean $is_mobile     是否是手机
     * @throws Exception
     * @return array
     */
    public function upload($file_tmp, $filePath, $sourceFilePath, $compress = true, $compressSize = [200,200], $is_mobile = false) {
        if (empty($this->ossDisjunctor)) {
            $data = [
                'code'    => 1,
                'message' => '上传失败：阿里云OSS开关关闭',
                'data'    => []
            ];
            return $data;
        }
        $accessKeyId = $this->ossAccessKeyId;
        $accessKeySecret = $this->ossAccessKeySecret;
        $endpoint = $this->ossEndpoint;
        $bucket= $this->ossBucket;
        $businessIdentify = $this->businessIdentify;
        //文件上传oss文件路径
        $object = $businessIdentify . DS . $filePath;
        //原文件上传oss文件路径
        $sourceObject = $businessIdentify . DS . $sourceFilePath;
        $object = str_replace('\\','/',$object);
        $sourceObject = str_replace('\\','/',$sourceObject);
        try{
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            //上传原图
            $imageRes = $ossClient->uploadFile($bucket, $sourceObject, $file_tmp);
            \think\Log::write('上传原图：' . json_encode($imageRes));
            //压缩图片
            if ($compress) {
                $style = "image/resize,w_$compressSize[0],h_$compressSize[1]";
                //oss无需反转图片，暂不处理
//                if ($is_mobile) {
//                    //图片信息
//                    $options = array(OssClient::OSS_PROCESS => 'image/info');
//                    $imageRes = $ossClient->getObject($bucket, $sourceObject, $options);
//                    $imageInfo = json_decode($imageRes, TRUE);
//                    \think\Log::write('图片信息：' . json_encode($imageInfo));
//                    $imageWidth = $imageInfo['ImageWidth']['value'];
//                    $imageHeight = $imageInfo['ImageHeight']['value'];
//                    //旋转90度
//                    if ($imageWidth > $imageHeight) {
//                        $style .= '/rotate,90';
//                    }
//                }
                $process = $style.
                    '|sys/saveas'.
                    ',o_'.$this->base64url_encode($object).
                    ',b_'.$this->base64url_encode($bucket);
                $imageRes = $ossClient->processObject($bucket, $sourceObject, $process);
                \think\Log::write('压缩图片：' . json_encode($imageRes));
            } else {
                //上传原图到指定图片路径
                $imageRes = $ossClient->uploadFile($bucket, $object, $file_tmp);
                \think\Log::write('上传原图到指定图片路径：' . json_encode($imageRes));
            }
            $data = [
                'code'    => 0,
                'message' => '上传成功',
                'data'    => []
            ];
        } catch(OssException $e) {
            $data = [
                'code'    => 1,
                'message' => '上传失败：' . $e->getMessage(),
                'data'    => []
            ];
            \think\Log::write('上传失败：' . $e->getMessage());
        }
        return $data;
    }

    public function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function checkFileExist($img,$is_delete = false){
        try {
            $accessKeyId = $this->ossAccessKeyId;
            $accessKeySecret = $this->ossAccessKeySecret;
            $endpoint = $this->ossEndpoint;
            $bucket= $this->ossBucket;
            $businessIdentify = $this->businessIdentify;
            $ossClient = new OssClient($accessKeyId,$accessKeySecret, $endpoint);
            $exist = $ossClient->doesObjectExist($bucket,$businessIdentify.'/'.$img);
            if ($exist && $is_delete){
                $ossClient->deleteObject($bucket,$businessIdentify.'/'.$img);
                return true;
            }
        }catch (OssException $e){
            \think\Log::write('检查文件失败：' . $e->getMessage());
            return false;
        }
        return $exist;

    }
}