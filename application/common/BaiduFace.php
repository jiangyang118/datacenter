<?php

namespace app\common;

use app\model\BaiduLog;
use app\model\Config;
use think\App;

class BaiduFace {

    private $api_type = array(2,3,4,5,6,7,8,9,10,11);

    private $api_name = array(
        '1' => '获取access_token',
        '2' => '人脸检测',
        '3' => '人脸搜索',
        '4' => '人脸注册',
        '5' => '人脸更新',
        '6' => '人脸删除',
        '7' => '用户删除',
        '8' => '在线活体检测',
        '9' => '人脸识别特征值',
        '10' => '创建用户组',
        '11' => '删除用户组',
    );

    private $api_url_ist = array(
        '1' => 'https://aip.baidubce.com/oauth/2.0/token',//获取access_token
        '2' => 'https://aip.baidubce.com/rest/2.0/face/v3/detect',//人脸检测
        '3' => 'https://aip.baidubce.com/rest/2.0/face/v3/search',//人脸搜索
        '4' => 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/user/add',//人脸注册
        '5' => 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/user/update',//人脸更新
        '6' => 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/face/delete',//人脸删除
        '7' => 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/user/delete',//用户删除
        '8' => 'https://aip.baidubce.com/rest/2.0/face/v3/faceverify',//在线活体检测
        '9' => 'https://aip.baidubce.com/rest/2.0/face/v1/feature',//人脸识别特征值
        '10' => 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/group/add',//创建用户组
        '11' => 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/group/delete',//删除用户组
    );

    private $access_token = '';
    private $baidu_group_id = '';
    private $baidu_apikey = '';
    private $baidu_secretkey = '';

    private $db = null;

    public function __construct() {

    }

    /**
     * 设置百度用户组
     * @return string | bool
     */
    public function setBaiduGroupID($baidu_group_id) {
        $this->baidu_group_id = $baidu_group_id;
    }

    public function setBaiduApikey($baidu_apikey) {
        $this->baidu_apikey = $baidu_apikey;
    }

    public function setBaiduSecretkey($baidu_secretkey) {
        $this->baidu_secretkey = $baidu_secretkey;
    }

    /**
     * 设置db
     * @return string | bool
     */
    public function setDb($db) {
        $this->db = $db;
    }

    /**
     * 读取数据库access_token
     * @return string | bool
     */
    private function readAccessToken() {
        $where = array(
            'type'          => 1,
            'is_success'    => 1,
            'response_time' => ['>', date('Y-m-d H:i:s', time() - 29 * 24 * 60 * 60)]
        );
        $token_log = $this->db->name('baidu_log')->where($where)->order(['response_time' => 'desc'])->find();
        if (empty($token_log) || empty($token_log['access_token'])) {
            return false;
        }
        return $token_log['access_token'];
    }

    /**
     * 获取access_token
     * @return string | bool
     */
    private function getAccessToken() {
        if (!empty($this->access_token)) {
            return $this->access_token;
        }
        $access_token = $this->readAccessToken();
        if (!empty($access_token)) {
            return $access_token;
        }
        $client_id     = $this->baidu_apikey;
        $client_secret = $this->baidu_secretkey;
        $grant_type    = 'client_credentials';
        $api_type      = '1';
        $api_url       = $this->api_url_ist[$api_type];
        $api_url       = sprintf('%s?client_id=%s&client_secret=%s&grant_type=%s', $api_url, $client_id, $client_secret, $grant_type);
        $create_time   = date('Y-m-d H:i:s');
        $response      = http_get($api_url);
        $response_time = date('Y-m-d H:i:s');
        $is_success    = 0;
        $access_token  = '';
        if (!empty($response)) {
            $res = json_decode($response, true);
            if (empty($res['error']) && !empty($res['access_token'])) {
                $is_success   = 1;
                $access_token = $res['access_token'];
                $this->access_token = $access_token;
            }
        }
        $log_data = array(
            'type'          => $api_type,
            'url'           => $api_url,
            'params'        => '',
            'access_token'  => $access_token,
            'trace_id'      => App::$trace_id,
            'response'      => $response,
            'response_time' => $response_time,
            'is_success'    => $is_success,
            'create_time'   => $create_time,
        );
        $api_name = !empty($this->api_name[$api_type]) ? $this->api_name[$api_type] : '';
        \think\Log::write($api_name . '百度api日志：'.json_encode($log_data));
        $this->db->name('baidu_log')->insertGetId($log_data);
        if ($is_success == 0) $this->pushMsg($api_type, $response);
        return !empty($access_token) ? $access_token : false;
    }

    /**
     * 请求百度api
     * @return string | bool
     */
    public function run($api_type, $params = null) {
        if (!in_array($api_type, $this->api_type)) {
            \think\Log::write('api_type_not_exist:' . $api_type);
            return false;
        }
        if (empty($this->access_token)) {
            $this->access_token = $this->getAccessToken();
        }
        if (empty($this->access_token)) {
            \think\Log::write('access_token_empty');
            return false;
        }
        $api_url       = $this->api_url_ist[$api_type];
        $api_url       = sprintf('%s?access_token=%s', $api_url, $this->access_token);
        $create_time   = date('Y-m-d H:i:s');
        $response      = http_post($params, $api_url, false);
        $response_time = date('Y-m-d H:i:s');
        $is_success    = 0;
        $result        = false;
        $error_code    = 0;
        if (!empty($response)) {
            $res = json_decode($response, true);
            if (isset($res['error_code']) && $res['error_code'] == 0) {
                $is_success = 1;
                $result     = $res;
            }
            $error_code = !empty($res['error_code']) ? $res['error_code'] : 0;
        }
        $params_str = '';
        if (!empty($params)) {
            $params_data = logSubstr($params);
            $params_str = json_encode($params_data);
        }
        $log_data = array(
            'type'          => $api_type,
            'url'           => $api_url,
            'params'        => $params_str,
            'access_token'  => $this->access_token,
            'response'      => $response,
            'trace_id'      => App::$trace_id,
            'response_time' => $response_time,
            'is_success'    => $is_success,
            'create_time'   => $create_time,
        );
        $api_name = !empty($this->api_name[$api_type]) ? $this->api_name[$api_type] : '';
        \think\Log::write($api_name . '百度api日志：'.json_encode($log_data));
        $this->db->name('baidu_log')->insertGetId($log_data);
        if ($is_success == 0 && $error_code != 222207) $this->pushMsg($api_type, $response);
        return $result;
    }

    public function pushMsg($api_type, $response) {
        $api_name = !empty($this->api_name[$api_type]) ? $this->api_name[$api_type] : '';
        $content = '百度api请求失败' . PHP_EOL;
        $content .= 'api:' . $api_name . PHP_EOL;
        $content .= 'trace_id:' . App::$trace_id . PHP_EOL;
        $content .= 'response:' . $response . PHP_EOL;
        $content .= 'file:' . __FILE__;
        $params = array(
            'msgtype' => 'text',
            'text' => array(
                'content' => $content
            ),
        );
        \app\common\QwRobot::pushMsg($params);
    }
    
    //人脸搜索
    public function search($image, $image_type) {
        $data = array(
            'face_token'   => '',
            'user_list'    => [],
        );
        //在线人脸库用户组
        $baidu_group_id = $this->baidu_group_id;
        $data['baidu_group_id'] = $baidu_group_id;
        //百度人脸搜索api
        $baidu_params = array(
            'image'            => $image,
            'image_type'       => $image_type,
            'group_id_list'    => $baidu_group_id,
            'match_threshold'  => 80
        );
        $api_type = 3;
        $api_name = $this->api_name[$api_type];
        $res = $this->run($api_type, $baidu_params);
        if (empty($res) || empty($res['result']) || empty($res['result']['face_token'])) {
            \think\Log::write($api_name . '百度api日志：'.json_encode($res));
            return $data;
        }
        $data['face_token'] = $res['result']['face_token'];
        $data['user_list'] = $res['result']['user_list'];
        return $data;
    }
}