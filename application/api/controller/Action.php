<?php

namespace app\api\controller;

use app\base\ApiController;
use think\Exception;
use app\api\logic\Action as ActionLogic;
use app\model\File as fileModel;
class Action extends ApiController{
    private $logic;
    private $fileModel;

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->logic = new ActionLogic();
        $this->fileModel = new fileModel();
    }

    /**
     * eliteform ocr同步数据
     */
    public function ocrData() {
        try {
            $data = $this->request->post('data');
            $file = $this->request->file('file');
            $business = $this->request->post('business', 'sxkx');
            if (empty($data)) {
                throw new Exception('缺少参数');
            }
            $data = json_decode($data, true);
            $res = $this->logic->ocrData($data, $file, $business);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
        } catch (Exception $ex) {
            return json(['code' => 1,'message' => $ex->getMessage(), 'data'=>[]]);
        }
        \think\Log::write('ocr_runtime:'.round(microtime(true) - THINK_START_TIME, 10));
        return json(['code' => 0, 'message' => "同步成功", 'data' => $res['data']]);
    }

    /**
     * 仅上传图片（弃用）
     */
    public function addFile() {
        try {
            $file = $this->request->file('file');
            if (empty($file)) {
                throw new Exception('缺少参数');
            }
            $res = $this->logic->addFile($file);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
        } catch (Exception $ex) {
            return json(['code' => 1,'message' => $ex->getMessage(), 'data'=>[]]);
        }
        \think\Log::write('ocr_runtime:'.round(microtime(true) - THINK_START_TIME, 10));
        return json(['code' => 0, 'message' => "同步成功", 'data' => $res['data']]);
    }

    /**
     * 机能设备数据上传
     */
    public function engineryData() {
        try {
            $business = $this->request->post('business');
            $data = $this->request->post('data');
            if (empty($business) || empty($data)) {
                throw new Exception('缺少参数');
            }
            $data = json_decode($data, true);
            $res = $this->logic->engineryData($business, $data);
            if ($res['code']) {
                throw new Exception($res['message']);
            }
        } catch (Exception $ex) {
            return json(['code' => 1,'message' => $ex->getMessage(), 'data'=>[]]);
        }
        return json(['code' => 0, 'message' => "同步成功", 'data' => $res['data']]);
    }

    /**
     * 云效流水线日志
     */
    public function pipelinelog() {
        try {
            $project_name = $this->request->post('project_name');
            $build_env = $this->request->post('build_env');
            $build_user = $this->request->post('build_user');
            $build_version = $this->request->post('build_version');
            $build_business = $this->request->post('build_business');
            $build_time = $this->request->post('build_time');
            $pipeline_name = $this->request->post('pipeline_name');
            $build_remark = $this->request->post('build_remark');
            $build_number = $this->request->post('build_number');
            $create_by = $this->request->post('create_by');
            $data = [
                'project_name' => $project_name,
                'build_env' => $build_env,
                'build_user' => $build_user,
                'build_version' => $build_version,
                'build_business' => $build_business,
                'build_time' => $build_time,
                'pipeline_name' => $pipeline_name,
                'build_remark' => $build_remark,
                'build_number' => $build_number,
                'create_by' => $create_by,
            ];
            $res = $this->logic->pipelinelog($data);
            if (!$res) {
                throw new Exception('云效流水线日志记录失败');
            }
        } catch (Exception $ex) {
            return json(['code' => 1,'message' => $ex->getMessage(), 'data'=>[]]);
        }
        return json(['code' => 0, 'message' => "记录成功", 'data' => $res['data']]);
    }

    /**
     * eliteform 文件上传
     */
    public function fileUpload()
    {
        try{
            $module = "EliteForm";
            $file = $this->request->file('file');
            $device_id = $this->request->post('device_id');
            $business = $this->request->post('business', 'sxkx');
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            if (empty($business_config)) {
                throw new Exception("用户不存在");
            }
            $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
            $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
            $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';
            $mysql_username = !empty($business_config['mysql_username']) ? $business_config['mysql_username'] : '';
            $mysql_password = !empty($business_config['mysql_password']) ? $business_config['mysql_password'] : '';

            $db_mysql = get_db_mysql($mysql_database, $mysql_prefix, $mysql_hostname, $mysql_username, $mysql_password);

            if(empty($file)) {
                throw new Exception("请上传文件");
            }
            //$filename = $file->getInfo("name");
            $filename = $file->getInfo("name");
            $file_path = $this->fileModel->getFilePath($module);
            $file_path = $file_path . DS . $device_id;
            $oneAnnex = $this->fileModel->upload($file, $file_path, [], $filename, FALSE, FALSE);
            if ($oneAnnex['code']) {
                throw new Exception($oneAnnex['message']);
            }
            $OSSServer = \oss\OSSServer::getInstance();
            $ossConfig = $OSSServer->getConfig();
            $oneAnnex['data']['fileUrlPrefix'] = $ossConfig['fileUrlPrefix'];
            $file_path_img = $oneAnnex['data']['fileUrlPrefix'].$oneAnnex['data']['file_path'];
            //图片重复上传，删除之前上传记录
            $imgs = $db_mysql->name('enginery_data_img')->where(['file_name' => $filename, 'is_del' => 0])->select();
            if (!empty($imgs)) {
                if (count($imgs) == 1) {
                    $mod_where['id'] = $imgs[0]['id'];
                } else {
                    $mod_where['id'] = ['in', array_column($imgs, 'id')];
                }
                $db_mysql->name('enginery_data_img')->where($mod_where)->update(['file_path' => $file_path_img]);
            }
        }catch(Exception $e) {
            return  json(['code'=>1, 'message'=>$e->getMessage(), 'data'=>[]]);
        }
        return json(['code'=>0, 'message'=>'上传成功', 'data'=> $oneAnnex['data']]);
    }
}
