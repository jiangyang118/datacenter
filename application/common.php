<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
if(!function_exists("get_age")) {
    function get_age($birthday) {
        if(!$birthday) {
            return 0;
        }
        list($y1, $m1, $d1) = explode("-", date("Y-m-d", time()));
        list($y2, $m2, $d2) = explode("-", $birthday);
        $age = $y1 - $y2;
        if($m1.$d1 < $m2.$d2) {
            $age--;
        }
        return $age;
    }
}

if(!function_exists("get_birthday")) {
    function get_birthday($age) {
        $current_day = date("Y-m-d");
        if(!$age) {
            return $current_day;
        }
        list($y, $m, $d) = explode("-", $current_day);
        $y1 = $y - $age;
        return $y1."-".$m."-".$d;
    }
}

/**
 * 获取创建人
 */
if(!function_exists("get_create_by")) {
    function get_create_by($user) {
        if(!empty($user['name'])) {
            return $user['name'];
        }
        return "";
//        if(!empty($user['name']) && !empty($user['id'])) {
//            return "{$user['name']}<{$user['id']}>";
//        } elseif(!empty ($user['name'])) {
//            return "{$user['name']}";
//        } elseif(!empty ($user['id'])) {
//            return "{$user['id']}";
//        } 
//        return "";
    }
}

if(!function_exists("get_salt")) {
    function get_salt() {
        $str = '0123456789abcdefghijklmnopqrstuvwxyz';
        $len = strlen($str);
        $hash = "";
        for($i=0; $i<6; $i++) {
            $hash .= $str[mt_rand(0, $len-1)];
        }
        return $hash;
    }
}

if(!function_exists("get_password")) {
    function get_password($password, $salt) {
        return md5(md5($password).$salt);
    }
}

if(!function_exists("array_column")) {
    function array_column($input, $column_key=null, $index_key=null) {
        $arr = array();
        foreach($input as $v) {
            if($index_key && $column_key) {
                $arr[$v[$index_key]] = $v[$column_key];
            } elseif($column_key) {
                $arr[] = $v[$column_key];
            } elseif($index_key) {
                $arr[$v[$index_key]] = $v;
            } else {
                $arr[] = $v;
            }
            unset($v);
        }
        unset($input);
        return $arr;
    }
}

if(!function_exists("guid")) {
    function guid(){
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
                // "}".chr(125);
        return $uuid;
    }
}

//无限级分类
if(!function_exists("tree")) {
    //$arr 分级的数组  $uuid 从第几级划分  $pid  父id  $id  子id
    function tree($arr, $uuid='0', $pid='puuid', $id='uuid') {
        $tree = array();
        if(empty($arr)) {
            return $tree;
        }
        foreach($arr as $menu) {
            if($menu[$pid] == $uuid) {
                $menu['children'] = tree($arr, $menu[$id], $pid, $id);
                if (!$menu['children']) {
                    unset($menu['children']);
                }
                //为前端使用
                $menu['chhildShow'] = false;    
                
                $tree[] = $menu;
            }
        }
        return $tree;
    }
}

if(!function_exists('int2Excel')) {
    function int2Excel($num) {
        $az = 26;
        $m = (int)($num % $az);
        $q = (int)($num / $az);
        $letter = chr(ord('A') + $m);
        if ($q > 0) {
            return int2Excel($q - 1) . $letter;
        }
        return $letter;
    }
}

if(!function_exists("excel2Int")) {
    function excel2Int($str) {
        $num = 0;
        $strArr = str_split($str, 1);
        $lenght = count($strArr);
        foreach ($strArr as $k => $v) {
            $num += ((ord($v) - ord('A') + 1) * pow(26, $lenght - $k - 1));
        }
        return $num - 1;
    }
}

if(!function_exists("writeLog")) {
    function writeLog($text, $filename='log') {
        \think\Log::write($text);
   }
}

if(!function_exists("syncFile")) {
    function syncFile($localFile, $remoteFile=null) {
        if(empty($remoteFile)) {
            $remoteFile = $localFile;
        }
        $doman_dir = "/home/wwwroot/sxtyj/public/";
        $localFile = $doman_dir.$localFile;
        $remoteFile = $doman_dir.$remoteFile;
        $ips = config('serverIPs');
        if(empty($ips)) {
            return;
        }
        foreach($ips as $ip) {
            if(empty($ip)) {
                continue;
            }
            $cmd = "scp -r {$localFile}" . " " . "{$ip}:{$remoteFile}";
            exec($cmd, $output, $retval);
            if($retval) {
                writeLog("{$localFile} {$ip}:{$remoteFile}|returnCode:{$retval}", "scpSyncFile");
            }
        }
    }
}

//日志字段长度截取
function logSubstr($log, $len = 1000) {
    if (is_array($log)) {
        if (mb_strlen(json_encode($log)) < $len) {
            return $log;
        }
        foreach ($log as $key => $value) {
            $log[$key] = logSubstr($value);
        }
        return $log;
    }
    return substr($log, 0, $len);
}

if(!function_exists("searchMaxOrMinValue")) {
    // 搜索某个字段的最大值或者最小值
    function searchMaxOrMinValue($array, $field, $searchType)
    {
        $result = null;
        foreach ($array as $item) {
            if (isset($item[$field]) && is_numeric($item[$field])) {
                $value = $item[$field];

                if ($searchType === 'max') {
                    if ($result === null || $value > $result) {
                        $result = $value;
                    }
                } elseif ($searchType === 'min') {
                    if ($result === null || $value < $result) {
                        $result = $value;
                    }
                }
            }
        }
        return $result;
    }
}


if(!function_exists("http_post")) {
    function http_post($param, $url, $json=false, $overtime=30) {
        $data = null;
        if($json) {
            $data = json_encode($param);
        } else {
            $data = http_build_query($param);
        }

        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $overtime);
        //url
        curl_setopt($ch, CURLOPT_URL, $url);
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
        return $result;
    }
}

/**
 * 获取数据库-mysql
 * @params $database string 数据库
 */
function get_db($database = '', $prefix = '', $hostname = '') {
    $db = config('database');
    $db['database'] = !empty($database) ? $database : $db['database'];
    $db['prefix'] = !empty($prefix) ? $prefix : $db['prefix'];
    $db['hostname'] = !empty($hostname) ? $hostname : $db['hostname'];
    return \think\Db::connect($db);
}
/**
 * 获取数据库-mysql
 * @params $database string 数据库
 */
function get_db_mysql($database = '', $prefix = '', $hostname = '', $username= '', $password = '') {
    $db = config('database');
    $db['database'] = !empty($database) ? $database : $db['database'];
    $db['prefix'] = !empty($prefix) ? $prefix : $db['prefix'];
    $db['hostname'] = !empty($hostname) ? $hostname : $db['hostname'];
    $db['username'] = !empty($username) ? $username : $db['username'];
    $db['password'] = !empty($password) ? $password : $db['password'];
    return \think\Db::connect($db);
}
/**
 * 获取数据库-sqlserver
 * @params $database string 数据库
 */
function get_db_srv($database = '') {
    $db = config('database.sqlsrv');
    $db['database'] = !empty($database) ? $database : $db['database'];
    return \think\Db::connect($db);
}

if(!function_exists("http_get")) {
    function http_get($url,$overtime=30) {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $overtime);
        //url
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

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
}

//H:i:s.0 转 秒
if(!function_exists("his_to_seconds")) {
    function his_to_seconds($his) {
        if (empty($his)) {
            return 0;
        }
        $str_arr = explode('.', $his);
        $time_str = !empty($str_arr[0]) ? $str_arr[0] : '';
        $time_d = !empty($str_arr[1]) ? $str_arr[1] : '';

        $time_arr = explode(':', $time_str);
        $h = !empty($time_arr[0]) ? $time_arr[0] : 0;
        $i = !empty($time_arr[1]) ? $time_arr[1] : 0;
        $s = !empty($time_arr[2]) ? $time_arr[2] : 0;

        $time = $h * 60 * 60 + $i * 60 + $s;
        if (!empty($time_d)) {
            $time = $time.'.'.$time_d;
        }
        return $time;
    }
}

//获取字符串数字
if(!function_exists("str_preg_num")) {
    function str_preg_num($str) {
        if (empty($str)) {
            return 0;
        }
        $num = 0;
        if (preg_match('/\d+/', $str, $arr)) {
            $num = $arr[0];
        }
        return $num;
    }
}