<?php
/*
 * 膳食系统数据监控消息
 */
namespace app\cron\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Monitor extends Command{

    protected function configure() {
        $this->setName('Monitor')->setDescription('this api is for Monitor');
    }

    //默认数据库配置
    private $db_config = [
        'type'            => 'mysql',
        'hostname'        => 'rds3nh30726o5tko5b9gro.mysql.rds.aliyuncs.com',
        'database'        => 'ydy_zlss',
        'username'        => 'rooot',
        'password'        => 'cpt2018@)!(',
        'hostport'        => '3306',
        'dsn'             => '',
        'params'          => [],
        'charset'         => 'utf8',
        'prefix'          => 'ydy_',
        'debug'           => false,
        'deploy'          => 0,
        'rw_separate'     => false,
        'master_num'      => 1,
        'slave_no'        => '',
        'read_master'     => false,
        'fields_strict'   => true,
        'resultset_type'  => 'array',
        'auto_timestamp'  => false,
        'datetime_format' => 'Y-m-d H:i:s',
        'sql_explain'     => false,
    ];
    //监控的数据库
    private $db_monitor = [
        [
            'name' => '产业园演示系统',
            'database' => 'ydy_zlss',
        ],
        [
            'name' => '北京体育大学系统',
            'database' => 'bjtydxydysskz',
        ],
        [
            'name' => '上海竞技训练中心',
            'database' => 'shjxydyss',
        ],
        [
            'name' => '秦皇岛训练基地',
            'database' => 'qhdydysskz',
        ],
        [
            'name' => '广东重竞技膳食系统',
            'database' => 'gdzjjydyss',
        ],
        [
            'name' => '国家女子自行车',
            'database' => 'gjnzzxcyydyss',
        ],
        [
            'name' => '贵州体科所',
            'database' => 'gztksydyss',
        ],
        [
            'name' => '江苏体科所',
            'database' => 'jstksydysskz',
        ],
        [
            'name' => '吕梁膳食系统',
            'database' => 'llsfyydyss',
        ],
        [
            'name' => '青岛职业技术学院',
            'database' => 'qdzyxyydyss',
        ],
        [
            'name' => '上海体育大学',
            'database' => 'shtydxyydyss',
        ],
        [
            'name' => '商丘职业技术学院',
            'database' => 'sqzyxyydyss',
        ],
        [
            'name' => '扬州基地',
            'database' => 'yzjdydysskz',
        ],
        [
            'name' => '广东体职院',
            'database' => 'gdtzyydyss',
        ],
        [
            'name' => '广东城市职业技术学院',
            'database' => 'gzcsyydyss',
        ],
        [
            'name' => '四川体质院',
            'database' => 'sctzydysskz',
        ],
        [
            'name' => '什刹海体校',
            'database' => 'schyyydyss',
        ],
        [
            'name' => '安徽体职院',
            'database' => 'ahtzyyydyss',
        ],
        [
            'name' => '甘肃体科所',
            'database' => 'gstksyydyss',
        ],
        [
            'name' => '河北省体育局运动技术学校',
            'database' => 'hbtxyydyss',
        ],
        [
            'name' => '湖南师范大学',
            'database' => 'hnsfyydyss',
        ],
        [
            'name' => '资政项目上海复旦大学附属华山医院',
            'database' => 'hsyyyydyss',
        ],
        [
            'name' => '山东食品药品职业学院',
            'database' => 'sdyszyydyss',
        ]
    ];

    /**
     * php think Monitor 每天下午6点执行
     */
    protected function execute(Input $input, Output $output) {
        echo date('Y-m-d H:i:s'). ' Monitor start' . PHP_EOL;
        $date = date('Y-m-d');
        foreach ($this->db_monitor as $key => $monitor) {
            $config = $this->db_config;
            $config['database'] = $monitor['database'];
            $db = \think\Db::connect($config);
            $sql = "select 
sum(t2.morning) morning,
sum(t2.noon) noon,
sum(t2.night) night,
sum(t2.whole) whole
from (
select 
t1.staff_uuid,
sum(if(t1.meal_times = 1, 1, 0)) morning,
sum(if(t1.meal_times = 2, 1, 0)) noon,
sum(if(t1.meal_times = 3, 1, 0)) night,
if(sum(t1.meal_times) = 6, 1, 0) whole
from (
	select 
	staff_uuid,
	meal_times
	from ydy_date_menu 
	where meal_type = 3
	and menu_date = '{$date}'
	group by staff_uuid,meal_times
) t1
group by t1.staff_uuid
) t2";
            $this->db_monitor[$key]['result'] = $db->query($sql);
        }
        $msg = "# 膳食系统就餐数据统计 \n";
        $msg .= "就餐日期：{$date} \n";
        $msg .= "| 客户 | 早餐人数 | 午餐人数 | 晚餐人数 | 三餐人数 | \n";
        foreach ($this->db_monitor as $monitor) {
            $msg_item = "| %s | %s | %s | %s | %s | \n";
            $result = !empty($monitor['result'][0]) ? $monitor['result'][0] : null;
            $morning = !empty($result['morning']) ? $result['morning'] : 0;
            $noon = !empty($result['noon']) ? $result['noon'] : 0;
            $night = !empty($result['night']) ? $result['night'] : 0;
            $whole = !empty($result['whole']) ? $result['whole'] : 0;
            $msg .= sprintf($msg_item, $monitor['name'], $morning, $noon, $night, $whole);
        }
        $params = array(
            'msgtype' => 'markdown',
            'markdown' => array(
                'content' => $msg
            ),
        );
        \app\common\QwRobot::pushMsg($params, 'monitor');
        echo date('Y-m-d H:i:s'). ' Monitor end' . PHP_EOL;
    }
}
