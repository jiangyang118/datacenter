<?php


namespace app\api\logic;

class Yueqi {
    public function __construct() {
        ;
    }
    
    public function postDataSTM32($params) {
        return ['Status'=>'SUCCESS','Msg'=>'上传结果成功'];
    }
    
    /**
     * 记录日志
     * @param type $text
     * @param type $filename
     */
    public function writeLog($text, $filename='yueqi') {
        if(is_array($text)) {
            $text = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $dir = $filePath = ROOT_PATH . 'public' . DS . 'static' . DS . 'upload' . DS .'api' . DS . date("Y-m-d");
        !is_dir($dir) AND mkdir($dir, 0777, true);
        $s = date("Y-m-d H:i:s") . "\t". $text . "\r\n";
        file_put_contents($dir . "/{$filename}.txt", $s, FILE_APPEND );
   }
}
