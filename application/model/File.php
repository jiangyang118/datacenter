<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace app\model;
use think\Exception;

class File {
//    static $basePath = 'static' . DS . 'upload';
    static $basePath = 'static/upload';
    /**
     * 判断文件是否存在
     * @param type $file_path  数据库存储文件的值
     * @return boolean|string
     */
    public function fileExist($file_path) {
        if(!$file_path) {
            return false;
        }
        $file_path = mb_convert_encoding($file_path, 'GBK', 'UTF-8');
        $file = ROOT_PATH . DS . 'public' . DS . $file_path;
        if(!is_file($file)) {
            return false;
        }
        return $file;
    }

    /**
     * 根据模块名和文件名判断文件是否存在
     * @param type $module
     * @param type $filename    带后缀的文件名
     * @return boolean|string
     */
    public function fileExistBy($module, $filename) {
        if(empty($module) || empty($filename)) {
            return false;
        }
        $file_path = $this->getFilePath($module);
        $file_path .= DS . $filename;
        $file_path = mb_convert_encoding($file_path, 'GBK', 'UTF-8');
        $file = ROOT_PATH . DS . 'public' . DS . self::$basePath . DS . $file_path;
        if(!is_file($file)) {
            return false;
        }
        return $file;
    }

    /**
     * 获取文件路径
     * @param string $module  文件夹区分
     * @return string
     */
    public function getFilePath($module, $date_dir=true) {
        if($date_dir) {
            return $module . DS . date('Ymd');
        }
        return $module;
    }
    /**
     * 上传文件
     * @param type $name      form表单中文件 <input type="file" name=""/> name的值
     * @param type $filePartPath  文件的上一层目录
     * @param type $rule            验证规则
     * @param type $filename        上传后文件的名称 有值得话用传递过来的值，没有的话随机生成。
     * @param type $compress        上传后文件(图片),是否需要缩略图片，默认缩小，不需要则传入false
     * @param type $compressSize    压缩的尺寸，图片的最大宽度与最大高度
     * @param type $is_source    是否生成原图 默认true
     * @throws Exception
     */
    public function upload($file, $filePartPath=null, $rule=[], $filename=true, $replace=FALSE, $compress=false, $compressSize=[200,200],$is_source = true, $is_mobile=false) {
        //上传oss
        $OSSServer = \oss\OSSServer::getInstance();
        $ossConfig = $OSSServer->getConfig();
//        var_dump($ossConfig);die;
        if (!empty($ossConfig['ossDisjunctor'])) {
            $res = $this->upload_oss($file, $filePartPath, $filename, $compress, $compressSize, $is_mobile);
            if ($is_source === false ){
                $res['data'] = !empty($res['data']['file_path']) ? $res['data']['file_path'] : '';
            }
            return $res;
        }

        try {
            if(empty($file)) {
                throw new Exception("没有监测到上传文件");
            }
            $file->check($rule);
            if(!$file->check($rule)) {
                throw new Exception($file->getError());
            }
//            $fileSaveName = $file->getinfo("name");
//            $regex = "/\/|\ |\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\（|\）|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\/|\;|\'|\`|\-|\=|\\\|\||\s*/";
//            $fileSaveName = preg_replace($regex,'',$fileSaveName);
            $fileSaveName = guid();
            if($filename !== TRUE) {
//                $filename = preg_replace($regex,'',$filename);
//                $fileSaveName = $filename;
//                $filename = trim($filename, DS);
                $filename = trim($fileSaveName, DS);
            }
            $targetFilePath = trim(self::$basePath . DS . $filePartPath, DS);
            //判断文件是否存在
            $fiel_exist = $this->fileExist($targetFilePath . DS .$fileSaveName);
            if ($fiel_exist&&!$replace) {
                throw new Exception('文件已存在不可上传');
            }
            $res = $file->move(ROOT_PATH . 'public' . DS . $targetFilePath, $filename, $replace);
            if ($is_source){
                $dir = ROOT_PATH . 'public' . DS . $targetFilePath. DS ."sourceimg";
                !is_dir($dir) AND mkdir($dir,0777,true);
                $source = file_get_contents($res->getpathName(),true,null);
                // 把原图写入到指定的文件夹中
                $suffix = substr($res->getpathName(),strripos($res->getpathName(),'.'));
                $put = file_put_contents($dir . DS . $filename.$suffix,$source);
                if ($put == false){
                    throw new Exception('原图上传失败');
                }
            }
//            if($res == false) {
//                throw new Exception($file->getError());
//            }
            //是否需要进行图片缩小
            if($compress){
                $imgInfo = $res->getinfo();
                if(isset($imgInfo['type']) && strpos($imgInfo['type'], 'image') !== false){//判断文件是否为图片
                    $targetSaveFileName = $res->getSaveName();
                    //图片太大无法压缩
                    \think\Log::write('压缩图片路径：' . $res->getpathName());
                    $image = \think\Image::open($res->getpathName());
                    //实际生成的缩略图默认采用原图等比例缩放
                    
                    if($is_mobile&&($image->width() > $image->height())) {
                        $image->thumb($compressSize[0],$compressSize[1])->rotate(90)->save($targetFilePath . DS . $targetSaveFileName);
                    } else {
                        $image->thumb($compressSize[0],$compressSize[1])->save($targetFilePath . DS . $targetSaveFileName);
                    }
                }
            }
        } catch(Exception $e) {
            return array('code'=>1, 'message'=>$e->getMessage(), 'data'=>[]);
        }
        $targetSaveFileName = $res->getSaveName();
        if ($is_source === false ){
            return array('code'=>0, 'message'=>"上传成功", 'data'=>$targetFilePath . DS .$targetSaveFileName);
        }else{
            return array('code'=>0, 'message'=>"上传成功", 'data'=>['file_path' => $targetFilePath . DS .$targetSaveFileName,
                'source_file_path' => $targetFilePath.DS."sourceimg".DS.$targetSaveFileName
            ]);
        }

    }

    /**
     * 上传图片-base64
     * @param $file_base64 string
     * @param $module string
     * @return string file_path
     */
    public function upload_base64($file_base64, $module = 'common', $uuid = '') {
        if (empty($uuid)) $uuid = guid();
        $type = 'jpg';
        $module_path = $this->getFilePath($module);
        $new_file = self::$basePath . DS . $module_path. DS;
        $bef = '';
        if (!file_exists($bef.$new_file)) {
            //检查是否有该文件夹，如果没有就创建，并给予最高权限
            mkdir($bef.$new_file, 0700, true);
        }
        $file_path = $new_file.$uuid.".{$type}";
        file_put_contents($bef.$file_path, base64_decode($file_base64));

        //上传oss
        $OSSServer = \oss\OSSServer::getInstance();
        $ossConfig = $OSSServer->getConfig();
        if (!empty($ossConfig['ossDisjunctor'])) {
            $OSSServer->upload($bef.$file_path, $file_path, $file_path, false);
            //删除服务器上的图片
            unlink($bef.$file_path);
            $file_path = str_replace('\\','/', $file_path);
        }
        return $file_path;
    }

    /**
     * 上传文件到oss
     * @param file $file          form表单中文件 <input type="file" name=""/> name的值
     * @param string $folderModulePath 文件模块文件夹
     * @param string $filename    上传后文件的名称 有值得话用传递过来的值，没有的话随机生成。
     * @param boolean $compress   上传后文件(图片),是否需要缩略图片，默认缩小，不需要则传入false
     * @param array $compressSize 压缩的尺寸，图片的最大宽度与最大高度
     * @param boolean $is_mobile  是否是手机
     * @throws Exception
     * @return array
     */
    public function upload_oss($file, $folderModulePath, $filename = '', $compress = true, $compressSize = [200,200], $is_mobile = false) {
        try {
            if (empty($file)) {
                throw new Exception("没有监测到上传文件");
            }
            //文件所在文件夹目录
            $folderPath = self::$basePath . DS . $folderModulePath;
            $fileInfo = $file->getInfo();
            //文件后缀
            $suffix = substr($fileInfo['name'], strrpos($fileInfo['name'],'.'));
            //文件保存名称
            $fileSaveName = !empty($filename) ? substr($filename, 0, strrpos($filename,'.')) : guid();
            $fileSaveName = !empty($fileSaveName) ? $fileSaveName : guid();
            //文件路径
            $filePath = $folderPath . DS . $fileSaveName . $suffix;
            //原文件路径
            $sourceFilePath = $folderPath . DS . 'sourceimg' . DS . $fileSaveName . $suffix;
            $OSSServer = \oss\OSSServer::getInstance();
            $res = $OSSServer->upload($fileInfo['tmp_name'], $filePath, $sourceFilePath, $compress, $compressSize, $is_mobile);
            if ($res['code'] != 0) {
                throw new Exception($res['message']);
            }
            $data = [
                'code'    => 0,
                'message' => "上传成功",
                'data'    => [
                    'file_path'        => str_replace('\\','/',$filePath),
                    'source_file_path' => str_replace('\\','/',$sourceFilePath),
                ]
            ];
        } catch(Exception $e) {
            $data = [
                'code'    => 1,
                'message' => "文件上传失败：" . $e->getMessage(),
                'data'    => []
            ];
        }
        return $data;
    }
}
