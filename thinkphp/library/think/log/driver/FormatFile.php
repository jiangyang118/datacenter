<?php
namespace think\log\driver;

use think\App;
use think\Request;

/**
 * 格式化输出日志得到文件
 */
class FormatFile {
    public $format_log = [
        'time'              => '',//时间
        'log_type'          => '',//日志类型
        'trace_id'          => '',//上下文标识
        'remote_ip'         => '',//IP
        'referer'           => '',//来源连接
        'user_agent'        => '',//浏览器信息
        'uri'               => '',//请求路由
        'url'               => '',//请求接口
        'request_method'    => '',//请求方式
        'user_uuid'         => '',//登陆uuid
        'user_name'         => '',//登陆user_name
        'user_nickname'     => '',//登陆user_nickname
        'params'            => '',//请求参数
        'runtime'           => '',//接口用时
        'memory'            => '',//内存消耗
        'messages'          => '',//日志信息（messages ｜ response）
        'file'              => '',//日志信息文件
        'line'              => '',//日志信息行
        'error_sql'         => '',//错误sql
    ];

    protected $config = [
        'file_size'   => 20971520,
        'path'        => LOG_PATH,
        'json'        => false,
    ];

    public function __construct($config = []) {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        }

        // 初始化日志
        $this->iniLog();
    }

    /**
     * 日志写入接口
     * @access public
     * @param  array    $log 日志信息
     * @return bool
     */
    public function save(array $log = []) {
        $info_log = $error_log = $exception_log = '';
        $info_info = $error_info = $exception_info = [];
        foreach ($log as $type => $type_msg) {
            foreach ($type_msg as $val) {
                $this->appendMsg($type);
                $format_log = $this->format_log;
                $format_log['messages'] = $val;
                //解析其他字段
                if (strpos($val, 'other_log_info:')) {
                    $msg_more = explode('other_log_info:', $val);
                    $format_log['messages'] = isset($msg_more[0]) ? $msg_more[0] : '';
                    $other_log_info = isset($msg_more[1]) ? json_decode($msg_more[1]) : '';
                    if (!empty($other_log_info)) {
                        foreach ($other_log_info as $other_key => $other_msg) {
                            $format_log[$other_key] = $other_msg;
                        }
                    }
                }
                switch ($type) {
                    case 'error':
                        if (empty($error_log)) {
                            $error_log = $this->getLogFile($type);
                        }
                        $error_info[] = $format_log;
                        $this->pushMsg($format_log);
                        break;
                    case 'exception':
                        if (empty($exception_log)) {
                            $exception_log = $this->getLogFile($type);
                        }
                        $exception_info[] = $format_log;
                        $this->pushMsg($format_log);
                        break;
                    default:
                        if (empty($info_log)) {
                            $info_log = $this->getLogFile('info');
                        }
                        $info_info[] = $format_log;
                        break;
                }
            }
        }
        //info
        if ($info_info) {
            $this->write($info_info, $info_log);
        }
        //error
        if ($error_info) {
            $this->write($error_info, $error_log);
        }
        //exception
        if ($exception_info) {
            $this->write($exception_info, $exception_log);
        }
        return true;
    }

    /**
     * 获取日志文件名
     * @access public
     * @param  string $type 日志类型
     * @return string
     */
    protected function getLogFile($type = '') {
        $type = empty($type) ? '' : '_' . $type;

        $filename = date('Ymd') . $type . '.log';

        $destination = $this->config['path'] . $filename;

        return $destination;
    }

    /**
     * 日志写入
     * @access protected
     * @param  array     $message 日志信息
     * @param  string    $destination 日志文件
     * @return bool
     */
    protected function write($message, $destination) {

        $path = dirname($destination);
        !is_dir($path) && mkdir($path, 0755, true);

        // 检测日志文件大小，超过配置大小则备份日志文件重新生成
        $this->checkLogSize($destination);

        foreach ($message as $log) {
            $log_str = $this->parseLog($log);
            error_log($log_str, 3, $destination);
        }

        return true;
    }

    /**
     * 检查日志文件大小并自动生成备份文件
     * @access protected
     * @param  string    $destination 日志文件
     * @return void
     */
    protected function checkLogSize($destination) {
        if (is_file($destination) && floor($this->config['file_size']) <= filesize($destination)) {
            try {
                rename($destination, dirname($destination) . DIRECTORY_SEPARATOR . time() . '-' . basename($destination));
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * 解析日志
     * @access protected
     * @param  array  $message 日志信息
     * @return string
     */
    protected function parseLog($message) {
        //输出json格式
        return json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES). "\r\n";
    }

    /**
     * 初始化日志参数
     */
    public function iniLog() {

        $request = Request::instance();
        $params = $request->param();
        if (isset($params['pwd'])) {
            unset($params['pwd']);
        }
        if (isset($params['password'])) {
            unset($params['password']);
        }
        $params_str = '';
        $business = config('business_identifying');
        $business_name = config('business_name');
        if (!empty($params)) {
            $business = !empty($params['business']) ? $params['business'] : $business;
            $business_set = config('business');
            $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
            $business_name = !empty($business_config['name']) ? $business_config['name'] : $business_name;

//            $params_data = logSubstr($params);
            $params_data = $params;
            $params_str = json_encode($params_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $user_uuid = App::$user_uuid;
        $user_name = App::$user_name;
        $user_nickname = App::$user_nickname;

        $this->format_log = [
            'time'              => '',//时间
            'log_type'          => '',//日志类型
            'trace_id'          => APP::$trace_id,//上下文标识
            'remote_ip'         => $request->ip(),//IP
            'referer'           => isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : '',//来源连接
            'user_agent'        => isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : '',//浏览器信息
            'uri'               => $request->host() . $request->url(),//请求路由
            'url'               => $request->url(),//请求接口
            'request_method'    => $request->method(),//请求方式
            'user_uuid'         => $user_uuid,//登陆uuid
            'user_name'         => $user_name,//登陆user_name
            'user_nickname'     => $user_nickname,//登陆user_nickname
            'params'            => $params_str,//请求参数
            'runtime'           => 0,//接口用时
            'memory'            => 0,//内存消耗
            'messages'          => '',//日志信息（messages ｜ response）
            'file'              => '',//日志信息文件
            'line'              => '',//日志信息行
            'error_sql'         => '',//错误sql
            'business'          => $business,//商户唯一标识
            'business_name'     => $business_name,//商户名称
        ];
    }

    /**
     * 追加日志参数
     */
    private function appendMsg($log_type) {
        $runtime = round(microtime(true) - THINK_START_TIME, 10);
        $runtime = number_format($runtime, 6);

        $memory_use = number_format((memory_get_usage() - THINK_START_MEM) / 1024, 2);

        $this->format_log['runtime'] = $runtime;
        $this->format_log['memory'] = $memory_use;
        $this->format_log['log_type'] = $log_type;
        $this->format_log['time'] = date('Y-m-d H:i:s');
        $this->format_log['start_microtime'] = THINK_START_TIME;
        $this->format_log['microtime'] = microtime(true);
        $this->format_log['pdate'] = date('Y-m-d');
    }

    /**
     * 发送企业微信消息
     */
    private function pushMsg($format_log) {
        //格式化发消息的字段
        $format_msg = array(
            '客户'               => $format_log['business_name'],//客户名称
            'time'              => $format_log['time'],//时间
            'log_type'          => $format_log['log_type'],//日志类型
            'trace_id'          => $format_log['trace_id'],//上下文标识
            'remote_ip'         => $format_log['remote_ip'],//IP
            'referer'           => $format_log['referer'],//来源连接
            'user_agent'        => $format_log['user_agent'],//浏览器信息
            'uri'               => $format_log['uri'],//请求路由
            'request_method'    => $format_log['request_method'],//请求方式
            'business'          => $format_log['business'],//商户唯一标识
            'user_uuid'         => $format_log['user_uuid'],//登陆uuid
            'user_name'         => $format_log['user_name'],//登陆user_name
            'user_nickname'     => $format_log['user_nickname'],//登陆user_nickname
            'params'            => $format_log['params'],//请求参数
            'messages'          => $format_log['messages'],//日志信息（messages ｜ response）
            'file'              => str_replace("\\",'/', $format_log['file']),//日志信息文件
            'line'              => $format_log['line'],//日志信息行
            'error_sql'         => $format_log['error_sql'],//错误sql
        );
        $format_msg['params'] = logSubstr($format_msg['params']);
        $msg = "# 系统异常反馈 \n";
        foreach($format_msg as $k => $v) {
            if ($k == 'error_sql' && empty($v)) {
                continue;
            }
            $log_str = $v;
            if (is_array($v)) {
                $log_str = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            if ($k == "log_type" || $k == "messages") {
                $msg .= "**><font color='warning'>{$k}</font>:**<font color='warning'>{$log_str}</font>\n";
            } else {
                $msg .= "**><font color='comment'>{$k}</font>:**<font color='comment'>{$log_str}</font>\n";
            }
        }
        $params = array(
            'msgtype' => 'markdown',
            'markdown' => array(
                'content' => $msg
            ),
        );
        \app\common\QwRobot::pushMsg($params);
    }
}

