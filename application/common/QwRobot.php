<?php

namespace app\common;

use think\App;

class QwRobot{
    //告警消息提醒
    private static $webhook_prod = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=701458bb-9ba6-44d0-b853-cdbd0014ef1c";
    private static $webhook_test = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=c4dfae52-3add-4fc6-a3e6-cda3446e2d4d";
    //正常消息通知
    private static $msg_prod = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=f7e98d75-61f3-4b65-82b3-e959ca3d2331";
    private static $msg_test = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=3a74beb0-e108-40c3-bee8-300f1f127f73";
    //膳食监控消息
    private static $monitor_prod = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=4883c37c-1242-4772-b42e-051186fabc0c";
    private static $monitor_test = "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=4883c37c-1242-4772-b42e-051186fabc0c";
    /**
     * 发送机器人消息
     * @param $params
     * @param $robot_type string 空=告警消息通知,msg=正常消息通知
     *
     * 示例
     * $params = array(
     *    'msgtype' => 'text',
     *    'text' => array(
     *        'content' => '系统异常，测试@到人',
     *        'mentioned_mobile_list' => array(
     *            '15811137696'
     *        )
     *   ),
     * );
     * 参考文档：https://developer.work.weixin.qq.com/document/path/91770
     * @return bool
     */
    public static function pushMsg($params, $robot_type = '') {
        if (TP_ENV == 'local') {
            return true;
        }
        $webhook = TP_ENV == 'prod' ? self::$webhook_prod : self::$webhook_test;
        if ($robot_type == 'msg') {
            $webhook = TP_ENV == 'prod' ? self::$msg_prod : self::$msg_test;
        } else if ($robot_type == 'monitor') {
            $webhook = TP_ENV == 'prod' ? self::$monitor_prod : self::$monitor_test;
        }
        http_post($params, $webhook, true);
        return true;
    }

    //$msg 支持数组
    public static function pushMsgFormat($msg, $title = '消息提醒', $robot_type = 'msg') {
        $content = $title . '：' . PHP_EOL;
        if (is_array($msg)) {
            foreach ($msg as $key => $value) {
                $content .= $key . ':' . $value .PHP_EOL;
            }
        } else {
            $content .= 'message:' . $msg .PHP_EOL;
        }
        $content .= 'time:' . date('Y-m-d H:i:s') . PHP_EOL;
        $content .= 'trace_id:' . App::$trace_id . PHP_EOL;
        $content .= 'env:' . TP_ENV;
        $params = array(
            'msgtype' => 'text',
            'text' => array(
                'content' => $content
            ),
        );
        return self::pushMsg($params, $robot_type);
    }
}
