<?php

namespace app\api\logic;

use think\App;
use think\Config;
use think\Exception;

use app\model\Token as TokenModel;
class Polar {
    public $tokenModel;
    public $writeLogFlag = false;
    public $authBaseUrl = 'https://auth.polar.com';
    public $apiBaseUrl = 'https://teampro.api.polar.com';
    public $third = 1;
    public function __construct() {
        $this->tokenModel = new TokenModel();
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
    }
    
    /**
     * 获取商户配置
     * @param type $business
     * @return type
     * @throws Exception
     */
    public function getMerchantConfig($business) {
        if(empty($business)) {
            throw new Exception("商户名错误");
        }
        
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if(empty($business_config)) {
            throw new Exception("商户配置错误");
        }
        return $business_config;
    }
    
    /**
     * 授权并获取token
     * @param type $params
     * @return type
     * @throws Exception
     */
    public function code($params) {
        if($this->writeLogFlag) {
            $this->writeLog($params);
        }
        
        try {
            $business = $params['business'];
            $business_config = $this->getMerchantConfig($business);
            $client_id = empty($business_config['polar']['clinet_id']) ? '' : $business_config['polar']['clinet_id'];
            $redirect_uri = empty($business_config['polar']['redirect_uri']) ? '' : $business_config['polar']['redirect_uri'];
            if(empty($params['code'])) {//获取code
                // 进入授权页面
                header('location:'.$this->authBaseUrl.'/oauth/authorize?client_id='.$client_id.'&response_type=code&scope=team_read&redirect_uri='.$redirect_uri);
                exit;
            } else {//获取access_token
                $res = $this->getTokenFromPolar($business, $params['code']);
                if($res['code']) {
                    throw new Exception($res['message']);
                }
            }
            return ['code'=>0, 'message'=>'success', 'data'=>''];
        } catch (Exception $e) {
            return ['code'=>1, 'message'=>$e->getMessage(), 'data'=>''];
        }
    }
    
    /**
     * 获取access_token
     * @param type $business
     * @param type $code
     * @throws Exception
     */
    public function getTokenFromPolar($business, $code='') {
        try {
            $business_config = $this->getMerchantConfig($business);
            $client_id = empty($business_config['polar']['clinet_id']) ? '' : $business_config['polar']['clinet_id'];
            $client_secret = empty($business_config['polar']['client_secret']) ? '' : $business_config['polar']['client_secret'];
            $redirect_uri = empty($business_config['polar']['redirect_uri']) ? '' : $business_config['polar']['redirect_uri'];
            $url = $this->authBaseUrl.'/oauth/token';
            $auth_string = \base64_encode("$client_id:$client_secret");
            $header = [
                'Authorization: Basic ' . $auth_string,
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Apifox/1.0.0 (https://apifox.com)'
            ];
            $param = [];
            if(empty($code)) {//刷新
                $currentToken = $this->tokenModel->inquiryOne(['third'=>$this->third, 'merchant'=>$business]);
                if(empty($currentToken)) {
                    throw new Exception("当前token不存在");
                }
                $param = [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $currentToken['refresh_token'],
                ];
            } else {//获取
                $param = [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirect_uri,
                ];
            }
            
            $resJson = $this->curlPost($url, $param, $header);
            if($this->writeLogFlag) {
                $this->writeLog($resJson);
            }
            $res = json_decode($resJson, true);
            if(empty($res['access_token'])) {
                throw new Exception("获取token失败");
            }
            $datetime = date("Y-m-d H:i:s");
            $expires_time = $datetime;
            if(!empty($res['expires_in'])) {
                $expires_time = date("Y-m-d H:i:s", strtotime($datetime)+$res['expires_in']);
            }
            $tokens = [
                'merchant' => $business,
                'third' => $this->third,
                'access_token' => $res['access_token'],
                'refresh_token' => $res['refresh_token'],
                'token_type' => $res['token_type'],
                'jti' => $res['jti'],
                'scope' => $res['scope'],
                'expires_in' => $res['expires_in'],
                'obtain_time' => $datetime,
                'expires_time' => $expires_time
            ];
            $add_token = $this->tokenModel->add($tokens, true);
            if(false === $add_token) {
                throw new Exception("存储token失败");
            }
            
            return ['code'=>0, 'message'=>'ok', 'data'=>$tokens];
        } catch (Exception $e) {
            return ['code'=>1, 'message'=>$e->getMessage(), 'data'=>[]];
        }
    }
    
    /**
     * 获取有效的token
     * @param type $business
     * @return type
     */
    public function getToken($business) {
        $token = $this->tokenModel->inquiryOne(['third'=>$this->third, 'merchant'=>$business]);
        if(!empty($token) && (time() < strtotime($token['expires_time']) - 60)) {
            return ['code'=>0, 'message'=>'ok', 'data'=>$token];
        }
        return $this->getTokenFromPolar($business);
    }
    
    /**
     * 获取运动队
     * @param type $business
     * @param type $page 从0开始
     * @param type $per_page 页容量
     * @return type
     * @throws Exception
     */
    public function getTeams($business, $paginationQuery=['page'=>0, 'per_page'=>100]) {
        $url = $this->apiBaseUrl."/v1/teams";
        $queryStr = http_build_query($paginationQuery);
        if(!empty($queryStr)) {
            $url .= "?{$queryStr}";
        }
        try {
            $token = $this->getToken($business);
            if(empty($token)) {
                throw new Exception("token不存在");
            }
            if($token['code']) {
                throw new Exception($token['message']);
            }
            $token = $token['data'];
            $header = [
                'User-Agent: Apifox/1.0.0 (https://apifox.com)',
                'Authorization: Bearer '.$token['access_token']
            ];
            $resJson = $this->curlGet($url, $header);
            if($this->writeLogFlag) {
                $this->writeLog($resJson, "polar_get_teams");
            }
            return ['code'=>0, 'message'=>'ok', 'data'=>json_decode($resJson, 1)];
        } catch (Exception $e) {
            return ['code'=>1, 'message'=>$e->getMessage(), 'data'=>''];
        }
    }
    
    /**
     * 获取运动队明细--下边包含运动员
     * @param type $business
     * @param type $team_id
     * @return type
     * @throws Exception
     */
    public function getTeamDetails($business, $team_id) {
        $url = $this->apiBaseUrl."/v1/teams/{$team_id}";
        try {
            $token = $this->getToken($business);
            if(empty($token)) {
                throw new Exception("token不存在");
            }
            if($token['code']) {
                throw new Exception($token['message']);
            }
            $token = $token['data'];
            $header = [
                'User-Agent: Apifox/1.0.0 (https://apifox.com)',
                'Authorization: Bearer '.$token['access_token']
            ];
            $resJson = $this->curlGet($url, $header);
            if($this->writeLogFlag) {
                $this->writeLog($resJson, "polar_get_team_details");
            }
            return ['code'=>0, 'message'=>'ok', 'data'=>json_decode($resJson, 1)];
        } catch (Exception $e) {
            return ['code'=>1, 'message'=>$e->getMessage(), 'data'=>''];
        }
    }
    
    /**
     * 获取运动队训练sessions
     * @param type $business
     * @param type $team_id
     * @param type $since Return training sessions having record_start_time greater than .
     * @param type $until Return training sessions having record_start_time less than
     * @param type $paginationQuery
     * @return type
     * @throws Exception
     */
    /**
     * Response
        type	TRAINING
        type	DRILL
        type	TEST
        type	GAME
        type	MATCH
        type	STRENGTH AND CONDITION
        type	OTHER
     */
    public function getTeamTrainingSessions($business, $team_id, $since, $until, $paginationQuery=['page'=>0, 'per_page'=>100]) {
        if(!empty($since)) {
            $paginationQuery['since'] = $since;
        }
        if(!empty($until)) {
            $paginationQuery['until'] = $until;
        }
        $queryStr = http_build_query($paginationQuery);
        $url = $this->apiBaseUrl."/v1/teams/{$team_id}/training_sessions";
        if($queryStr) {
            $url .= "?{$queryStr}";
        }
        try {
            $token = $this->getToken($business);
            if(empty($token)) {
                throw new Exception("token不存在");
            }
            if($token['code']) {
                throw new Exception($token['message']);
            }
            $token = $token['data'];
            $header = [
                'User-Agent: Apifox/1.0.0 (https://apifox.com)',
                'Authorization: Bearer '.$token['access_token']
            ];
            $resJson = $this->curlGet($url, $header);
            if($this->writeLogFlag) {
                $this->writeLog($resJson, "polar_get_team_train_sessions");
            }
            return ['code'=>0, 'message'=>'ok', 'data'=>json_decode($resJson, 1)];
        } catch (Exception $e) {
            return ['code'=>1, 'message'=>$e->getMessage(), 'data'=>''];
        }
    }
    
    /**
     * 获取运动队训练session明细
     * @param type $business
     * @param type $training_session_id
     * @return type
     * @throws Exception
     */
    /**
     * Response
        marker_type	PHASE
        marker_type	NOTE
        marker_type	RECOVERY
     */
    public function getTeamTrainingSessionDetails($business, $training_session_id) {
        $url = $this->apiBaseUrl."/v1/teams/training_sessions/{$training_session_id}";
        try {
            $token = $this->getToken($business);
            if(empty($token)) {
                throw new Exception("token不存在");
            }
            if($token['code']) {
                throw new Exception($token['message']);
            }
            $token = $token['data'];
            $header = [
                'User-Agent: Apifox/1.0.0 (https://apifox.com)',
                'Authorization: Bearer '.$token['access_token']
            ];
            $resJson = $this->curlGet($url, $header);
            if($this->writeLogFlag) {
                $this->writeLog($resJson, "polar_get_team_train_session_details");
            }
            return ['code'=>0, 'message'=>'ok', 'data'=>json_decode($resJson, 1)];
        } catch (Exception $e) {
            return ['code'=>1, 'message'=>$e->getMessage(), 'data'=>''];
        }
    }
    
    /**
     * 获取运动员训练sessions
     * Only training sessions that are done during time the player has been linked to team roster will be visible here.
     * @param type $business
     * @param type $player_id
     * @param type $type Return all, individual or team training sessions.(ALL\TEAM\INDIVIDUAL)
     * @param type $since
     * @param type $until
     * @param type $paginationQuery
     * @return type
     * @throws Exception
     */
    /**
     * Response
        type	TEAM
        type	INDIVIDUAL
        feeling	BAD
        feeling	NOT_GOOD
        feeling	OKAY
        feeling	GREAT
        feeling	AWESOME
     */
    public function getPlayerTrainingSessions($business, $player_id, $since, $until, $type='TEAM', $paginationQuery=['page'=>0, 'per_page'=>100]) {
        if(!empty($since)) {
            $paginationQuery['since'] = $since;
        }
        if(!empty($until)) {
            $paginationQuery['until'] = $until;
        }
        if(!empty($type)) {
            $paginationQuery['type'] = $type;
        }
        $queryStr = http_build_query($paginationQuery);
        $url = $this->apiBaseUrl."/v1/players/{$player_id}/training_sessions";
        if($queryStr) {
            $url .= "?{$queryStr}";
        }
        try {
            $token = $this->getToken($business);
            if(empty($token)) {
                throw new Exception("token不存在");
            }
            if($token['code']) {
                throw new Exception($token['message']);
            }
            $token = $token['data'];
            $header = [
                'User-Agent: Apifox/1.0.0 (https://apifox.com)',
                'Authorization: Bearer '.$token['access_token']
            ];
            $resJson = $this->curlGet($url, $header);
            if($this->writeLogFlag) {
                $this->writeLog($resJson, "polar_get_player_train_sessions");
            }
            return ['code'=>0, 'message'=>'ok', 'data'=>json_decode($resJson, 1)];
        } catch (Exception $e) {
            return ['code'=>1, 'message'=>$e->getMessage(), 'data'=>''];
        }
    }
    
    /**
     * 获取运动员训练session详情
     * @param type $business
     * @param type $player_session_id
     * @param type $samples Include requested samples in response. Possible values are "all" or comma-separated list from "distance", "location", "hr", "speed", "cadence", "altitude", "forward_acceleration", "rr".
     * @return type
     * @throws Exception
     */
    /**
     * Response
        type	TEAM
        type	INDIVIDUAL
        feeling	BAD
        feeling	NOT_GOOD
        feeling	OKAY
        feeling	GREAT
        feeling	AWESOME
     */
    public function getPlayerTrainingSessionDetails($business, $player_session_id, $samples) {
        $url = $this->apiBaseUrl."/v1/training_sessions/{$player_session_id}";
        if(!empty($samples)) {
            $url .= "?samples={$samples}";
        }
        try {
            $token = $this->getToken($business);
            if(empty($token)) {
                throw new Exception("token不存在");
            }
            if($token['code']) {
                throw new Exception($token['message']);
            }
            $token = $token['data'];
            $header = [
                'User-Agent: Apifox/1.0.0 (https://apifox.com)',
                'Authorization: Bearer '.$token['access_token']
            ];
            $resJson = $this->curlGet($url, $header);
            if($this->writeLogFlag) {
                $this->writeLog($resJson, "polar_get_player_train_session_details");
            }
            return ['code'=>0, 'message'=>'ok', 'data'=>json_decode($resJson, 1)];
        } catch (Exception $e) {
            return ['code'=>1, 'message'=>$e->getMessage(), 'data'=>''];
        }
    }
    
    /**
     * Get player team training session trimmed values.
     * @param type $business
     * @param type $player_session_id
     * @return type
     * @throws Exception
     */
    public function getPlayerTrainingSessionSummary($business, $player_session_id) {
        $url = $this->apiBaseUrl."/v1/training_sessions/{$player_session_id}/session_summary";
        try {
            $token = $this->getToken($business);
            if(empty($token)) {
                throw new Exception("token不存在");
            }
            if($token['code']) {
                throw new Exception($token['message']);
            }
            $token = $token['data'];
            $header = [
                'User-Agent: Apifox/1.0.0 (https://apifox.com)',
                'Authorization: Bearer '.$token['access_token']
            ];
            $resJson = $this->curlGet($url, $header);
            if($this->writeLogFlag) {
                $this->writeLog($resJson, "polar_get_player_train_session_summary");
            }
            return ['code'=>0, 'message'=>'ok', 'data'=>json_decode($resJson, 1)];
        } catch (Exception $e) {
            return ['code'=>1, 'message'=>$e->getMessage(), 'data'=>''];
        }
    }
    
    /**
     * 发送机器人消息
     * @param type $business
     * @param type $msg
     * @param type $title
     * @throws Exception
     */
    public function sendMsg($business, $msg, $title) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            throw new Exception('商户配置错误');
        }
        \app\common\QwRobot::pushMsgFormat($msg, $business_config['name'].$title);
    }
    
    /**
     * post请求
     * @param type $url
     * @param type $param
     * @param type $header
     * @param type $json
     * @param type $timeOut
     * @return boolean
     */
    public function curlPost($url, $param, $header=[], $json=false, $timeOut=30) {
        $data = null;
        if($json) {
            $data = json_encode($param);
        } else {
            $data = http_build_query($param);
        }

        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        //url
        curl_setopt($ch, CURLOPT_URL, $url);
        if($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $result=curl_exec($ch);
        //返回结果
        $error_no = curl_errno($ch);
        if($error_no) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $result;
    }
    
    /**
     * get请求
     * @param type $url
     * @param type $header
     * @param type $timeOut
     * @return boolean
     */
    public function curlGet($url, $header=[], $timeOut=30) {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        //url
        curl_setopt($ch, CURLOPT_URL, $url);
        if($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result=curl_exec($ch);
        //返回结果
        $error_no = curl_errno($ch);
        if($error_no) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $result;
    }
    
    /**
     * 日志
     * @param type $text
     * @param type $filename
     */
    public function writeLog($text, $filename='polar') {
        if(is_array($text)) {
            $text = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $dir = $filePath = ROOT_PATH . 'public' . DS . 'static' . DS . 'upload' . DS .'api' . DS . date("Y-m-d");
        !is_dir($dir) AND mkdir($dir, 0777, true);
        $s = date("Y-m-d H:i:s") . "\t". $text . "\r\n";
        file_put_contents($dir . "/{$filename}.txt", $s, FILE_APPEND );
   }
   
    /**
    * 压缩数值
    * @param type $data
    * @param type $type
    * @param type $bits
    * @return type
    */
    public function compress($data, $type, $bits) {
        // 定义写入字节的函数表
        $writeValueToByteArray = [
            'Float' => [
                64 => function($value) {
                    return unpack('C*', pack('d', $value));
                },
                32 => function($value) {
                    return unpack('C*', pack('f', $value));
                }
            ],
            'Unsigned' => [
                16 => function($value) {
                    return unpack('C*', pack('n', $value));
                },
                8 => function($value) {
                    return [$value];
                }
            ],
            'Signed' => [
                16 => function($value) {
                    return unpack('C*', pack('s', $value));
                }
            ]
        ];

        // 初始化字节数组
        $bytes = [];

        // 遍历数据，编码到字节数组
        foreach ($data as $value) {
            $encodedBytes = $writeValueToByteArray[$type][$bits]($value);
            $bytes = array_merge($bytes, $encodedBytes);
        }

        // 将字节数组打包为二进制字符串
        $binaryData = pack('C*', ...$bytes);

        // 压缩数据
        $compressedData = zlib_encode($binaryData, ZLIB_ENCODING_DEFLATE);

        // 转换为 Base64 编码
        $base64EncodedData = base64_encode($compressedData);

        // 返回压缩后的数据
        return [
            'data' => $base64EncodedData,
            'type' => $type,
            'bits' => $bits
        ];
    }

    /**
     * 解压数值
     * @param type $series
     * @return type
     */
    public function decompress($series) {
        // 解码Base64
        $decodedData = base64_decode($series['data']);

        // 解压缩数据
        $uncompressedData = zlib_decode($decodedData);

        // 创建一个可以读字节的数组
        $bytes = unpack('C*', $uncompressedData);

        $readValueFromByteArray = [
            'Float' => [
                64 => function($bytes, $index) {
                    $data = pack('C*', ...array_slice($bytes, $index * 8 + 1, 8));
                    return unpack('d', $data)[1];
                },
                32 => function($bytes, $index) {
                    $data = pack('C*', ...array_slice($bytes, $index * 4 + 1, 4));
                    return unpack('f', $data)[1];
                }
            ],
            'Unsigned' => [
                16 => function($bytes, $index) {
                    $data = pack('C*', $bytes[$index * 2 + 1], $bytes[$index * 2 + 2]);
                    return unpack('n', $data)[1];
                },
                8 => function($bytes, $index) {
                    return $bytes[$index + 1];
                }
            ],
            'Signed' => [
                16 => function($bytes, $index) {
                    $data = pack('C*', $bytes[$index * 2 + 1], $bytes[$index * 2 + 2]);
                    return unpack('s', $data)[1];
                }
            ]
        ];

        $result = [];
        $totalLength = count($bytes);
        $elementSize = $series['bits'] / 8;

        for ($i = 0; $i < $totalLength / $elementSize; $i++) {
            $result[$i] = $readValueFromByteArray[$series['type']][$series['bits']]($bytes, $i);
        }

        return $result;
    }
    
    /**
     * 压缩混合类型
     * @param type $data
     * @return type
     */
    function compressNested($data) {
        // 使用 JSON 将数据序列化为字符串
        $jsonString = json_encode($data);

        // 使用 zlib 压缩
        $compressedData = zlib_encode($jsonString, ZLIB_ENCODING_DEFLATE);

        // 转为 Base64 编码
        $base64EncodedData = base64_encode($compressedData);

        return $base64EncodedData;
    }
    
    /**
     * 解压混合类型
     * @param type $compressedData
     * @return type
     */
    function decompressNested($compressedData) {
        // Base64 解码
        $decodedData = base64_decode($compressedData);

        // 解压缩数据
        $jsonString = zlib_decode($decodedData);

        // 使用 JSON 反序列化还原为数组
        $data = json_decode($jsonString, true);

        return $data;
    }
}
