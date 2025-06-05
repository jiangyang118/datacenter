<?php

namespace app\base;

include_once __DIR__.DS."..".DS."..".DS."jwt".DS."vendor".DS."autoload.php";

use think\Exception;
use Firebase\JWT\JWT;

class FirstbeatBase {
    private $consumerId = "6617c51f-072c-4352-9e63-af985354564f";
    private $consumerSharedSecret = "e98d0865-96b3-4757-bba4-308001736701";
    private $apiKey = "jgRl4RCCC34PNco6RuFpl2WsWz8UT2za40J93Mlm";
    private $baseURI = "https://api.firstbeat.com/v1";
    private $log = false;
    
    public function __construct() {
        ;
    }
    
    public function curlPost($url, $param, $header=[], $json=false, $timeOut=30, $business='') {
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
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result=curl_exec($ch);
        //返回结果
        $error_no = curl_errno($ch);
        if($error_no) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        $this->dealApiException($business, $result, $url);
        return $result;
    }

    public function curlGet($url, $header=[], $gzip=false, $timeOut=30, $business='') {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        //url
        curl_setopt($ch, CURLOPT_URL, $url);
        if($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
//        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if($gzip) {
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        }

        $result=curl_exec($ch);
        //返回结果
        $error_no = curl_errno($ch);
        if($error_no) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        
        $this->dealApiException($business, $result, $url);
        return $result;
    }

    /**
     * 获取token
     * @return type
     * @throws Exception
     */
    public function getToken() {
        $now = time();
        $expires = $now + 300;
        $payload = array(
            'iss' => $this->consumerId,
            'iat' => $now,
            'exp' => $expires
        );
        return JWT::encode($payload, $this->consumerSharedSecret, 'HS256');
    }
    
    /**
     * Get accounts
     * Get Firstbeat Sports accounts linked to your API consumer
     * @param type $business
     * @return type
     */
    public function getAccounts($business) {
        $url = $this->baseURI . "/sports/accounts";
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_accounts");
        }
        
        return $res;
    }
    
    /**
     * Get list of athletes
     * Get athletes that belong to the specified account
     * @param type $business
     * @param type $accountId
     * @param type $offset
     * @return type
     */
    public function getAthletesByAccountId($business, $accountId, $offset=0) {
        $url = $this->baseURI . "/sports/accounts/{$accountId}/athletes";
        if(!empty($offset)) {
            $params['offset'] = $offset;
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
            $url .= "?{$queryStr}";
        }
        
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_athleteslist_accounts");
        }
        
        return $res;
    }
    
    /**
     * Get athlete
     * 和列表返回的字段一样，因此不用调用它
     * @param type $business
     * @param type $accountId
     * @param type $athleteId
     * @return type
     */
    public function getAthleteById($business, $accountId, $athleteId) {
        $url = $this->baseURI . "/sports/accounts/{$accountId}/athletes/{$athleteId}";
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_athlete");
        }
        
        return $res;
    }
    
    /**
     * Get athlete measurements
     * List all measurements of an athlete
     * @param type $business
     * @param type $accountId
     * @param type $athleteId
     * @param type $includeLaps
     * @param type $offset
     * @return type
     */
    public function getAthleteMeasurements($business, $accountId, $athleteId, $includeLaps='false', $offset=0) {
        $params = [];
        if(!empty($offset)) {
            $params['offset'] = $offset;
        }
        if($includeLaps) {
            $params['includeLaps'] = $includeLaps;
        }
        $queryStr = '';
        if($params) {
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        }
        $url = $this->baseURI . "/sports/accounts/{$accountId}/athletes/{$athleteId}/measurements";
        if($queryStr) {
            $url .= "?{$queryStr}";
        }
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_athlete_measurements");
        }
        
        return $res;
    }
    
    /**
     * Get athlete measurement results
     * @param type $business
     * @param type $accountId
     * @param type $athleteId
     * @param type $measurementId
     * @param type $format Enum: "binary" "list"
     * @param type $var Specify names of requested variables in a comma-separated string
     * @return type
     */
    public function getAthleteMeasurementResults($business, $accountId, $athleteId, $measurementId, $format="list", $var="") {
        $params = [];
        if(!empty($var)) {
            $params['var'] = $var;
        }
        if(!empty($format)) {
            $params['format'] = $format;
        }
        $queryStr = '';
        if($params) {
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        }
        $url = $this->baseURI . "/sports/accounts/{$accountId}/athletes/{$athleteId}/measurements/{$measurementId}/results";
        if($queryStr) {
            $url .= "?{$queryStr}";
        }
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_athlete_measurement_results");
        }
        
        return $res;
    }
    
    /**
     * Get athlete measurement lap results
     * Get analysis results for lap in athlete's measurement
     * @param type $business
     * @param type $accountId
     * @param type $athleteId
     * @param type $measurementId
     * @param type $format Enum: "binary" "list"
     * @param type $var Specify names of requested variables in a comma-separated string
     * @return type
     */
    public function getAthleteMeasurementLapResults($business, $accountId, $athleteId, $measurementId, $lapId, $format="", $var="") {
        $params = [];
        if(!empty($var)) {
            $params['var'] = $var;
        }
        if(!empty($format)) {
            $params['format'] = $format;
        }
        $queryStr = '';
        if($params) {
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        }
        $url = $this->baseURI . "/sports/accounts/{$accountId}/athletes/{$athleteId}/measurements/{$measurementId}/laps/{$lapId}/results";
        if($queryStr) {
            $url .= "?{$queryStr}";
        }
        
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_athlete_measurement_lap_results");
        }
        
        return $res;
    }
    
    /**
     * Get coaches
     * Get all coaches in an account
     * @param type $business
     * @param type $accountId
     * @param type $offset
     * @return type
     */
    public function getCoachesByAccountId($business, $accountId, $offset=0) {
        $url = $this->baseURI . "/sports/accounts/{$accountId}/coaches";
        if(!empty($offset)) {
            $params['offset'] = $offset;
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
            $url .= "?{$queryStr}";
        }
        
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_all_coaches_inaccount");
        }
        
        return $res;
    }
    
    /**
     * Get coach
     * Get coach info
     * 和列表返回字段一样，因此不用调用它
     * @param type $business
     * @param type $accountId
     * @param type $coachId
     * @return type
     */
    public function getCoachById($business, $accountId, $coachId) {
        $url = $this->baseURI . "/sports/accounts/{$accountId}/coaches/{$coachId}";
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_coach");
        }
        
        return $res;
    }
    
    /**
     * Get teams
     * Get all teams and groups in an account
     * @param type $business
     * @param type $accountId
     * @param type $offset
     * @return type
     */
    public function getTeamsByAccountId($business, $accountId, $offset=0) {
        $url = $this->baseURI . "/sports/accounts/{$accountId}/teams";
        if(!empty($offset)) {
            $params['offset'] = $offset;
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
            $url .= "?{$queryStr}";
        }
        
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_all_teamsorgroups_inaccount");
        }
        
        return $res;
    }
    
    /**
     * Get team
     * Get team and its groups
     * 和列表返回字段一样，因此不用调用它
     * @param type $business
     * @param type $accountId
     * @param type $teamId
     * @return type
     */
    public function getTeamById($business, $accountId, $teamId) {
        $url = $this->baseURI . "/sports/accounts/{$accountId}/teams/{$teamId}";
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_team");
        }
        
        return $res;
    }
    
    /**
     * Get team athletes
     * Get all athletes in a team or group
     * @param type $business
     * @param type $accountId
     * @param type $teamId
     * @param type $offset
     * @return type
     */
    public function getAthletesInteam($business, $accountId, $teamId, $offset=0) {
        $url = $this->baseURI . "/sports/accounts/{$accountId}/teams/{$teamId}/athletes";
        if(!empty($offset)) {
            $params['offset'] = $offset;
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
            $url .= "?{$queryStr}";
        }
        
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_athletes_in_team");
        }
        
        return $res;
    }
    
    /**
     * Get team sessions
     * Get all sessions of a team or group
     * @param type $business
     * @param type $accountId
     * @param type $teamId
     * @param type $offset
     * @param type $fromTime
     * @param type $toTime
     * @param type $type
     * @param type $includeLaps
     * @return type
     */
    public function getTeamSessions($business, $accountId, $teamId, $offset=0, $fromTime=null, $toTime=null, $type=null, $includeLaps="false") {
        $params = [];
        if(!empty($offset)) {
            $params['offset'] = $offset;
        }
        if(!empty($fromTime)) {
            $params['fromTime'] = $fromTime;
        }
        if(!empty($toTime)) {
            $params['toTime'] = $toTime;
        }
        if(!empty($type)) {
            $params['type'] = $type;
        }
        if(!empty($includeLaps)) {
            $params['includeLaps'] = $includeLaps;
        }
        $queryStr = '';
        if($params) {
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        }
        $url = $this->baseURI . "/sports/accounts/{$accountId}/teams/{$teamId}/sessions";
        if($queryStr) {
            $url .= "?{$queryStr}";
        }
        
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_team_sessions");
        }
        
        return $res;
    }
    
    /**
     * Get session results
     * @param type $business
     * @param type $accountId
     * @param type $teamId
     * @param type $sessionId
     * @param type $format
     * @param type $var
     * @return type
     */
    public function getSessionResults($business, $accountId, $teamId, $sessionId, $format="", $var="") {
        $params = [];
        if(!empty($var)) {
            $params['var'] = $var;
        }
        if(!empty($format)) {
            $params['format'] = $format;
        }
        $queryStr = '';
        if($params) {
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        }
        $url = $this->baseURI . "/sports/accounts/{$accountId}/teams/{$teamId}/sessions/{$sessionId}/results";
        if($queryStr) {
            $url .= "?{$queryStr}";
        }
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_session_results");
        }
        
        return $res;
    }
    
    public function getSessionLapResults($business, $accountId, $teamId, $sessionId, $lapId, $format="", $var="") {
        $params = [];
        if(!empty($var)) {
            $params['var'] = $var;
        }
        if(!empty($format)) {
            $params['format'] = $format;
        }
        $queryStr = '';
        if($params) {
            $queryStr = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        }
        $url = $this->baseURI . "/sports/accounts/{$accountId}/teams/{$teamId}/sessions/{$sessionId}/laps/{$lapId}/results";
        if($queryStr) {
            $url .= "?{$queryStr}";
        }
        
        $token = $this->getToken();
        $res = $this->curlGet($url, ["Authorization: Bearer ".$token, "x-api-key: ".$this->apiKey, "accept: application/json"],false, 30, $business);
        if($this->log) {
            $this->writeLog($res, "get_session_lap_results");
        }
        
        return $res;
    }
    
    /**
     * 处理接口反馈异常
     * @param type $business
     */
    public function dealApiException($business, $res, $apiUrl) {
        $res = json_decode($res, 1);
        if(!empty($res['message'])) {
//            if(in_array(strtolower($res['message']), ['forbidden','unauthorized','badrequest'])) {
                $sendmsg = [
                    'device' => "firstbeat",
                    'business' => $business,
                    'apiUrl' => $apiUrl,
                    'message' => $res['message']
                ];
                $this->sendMsg($sendmsg, "firstbeat接口返回异常");
//            }
        }
        
    }
    
     /**
     * 发送机器人消息
     * @param type $business
     * @param type $msg
     * @param type $title
     * @throws Exception
     */
    public function sendMsg($msg, $title) {
        \app\common\QwRobot::pushMsgFormat($msg, $title);
    }
    
    public function writeLog($text, $filename='log') {
        $dir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "./apiLog/" . date("Y-m-d");
        !is_dir($dir) AND mkdir($dir, 0777, true);
        $s = date("Y-m-d H:i:s") . "\t". $text . "\r\n";
        file_put_contents($dir . "/{$filename}.txt", $s, FILE_APPEND );
   }
}
