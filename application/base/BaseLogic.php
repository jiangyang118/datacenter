<?php

namespace app\base;

use think\Config;

class BaseLogic {

    /**
     * 初始化数据库配置
     */
    public function set_db_config($business_config) {
        $db = Config::get('database');
        $db['database'] = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : $db['database'];
        $db['prefix'] = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : $db['prefix'];
        $db['hostname'] = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : $db['hostname'];
        $db['username'] = !empty($business_config['mysql_username']) ? $business_config['mysql_username'] : $db['username'];
        $db['password'] = !empty($business_config['mysql_password']) ? $business_config['mysql_password'] : $db['password'];
        Config::set('database', $db);
    }

    /**
     * 获取符合条件的一条数据
     * @param array $business_config
     * @param string $table
     * @param array $where
     * @param array $orderBy
     * @param array $field
     * @return array | bool
     */
    public function inquiryOne($business_config, $table, $where=[], $orderBy=[], $field=['*']) {
        $this->set_db_config($business_config);
        $db_mysql = get_db();
        $info = $db_mysql->name($table)
            ->where($where)
            ->order($orderBy)
            ->field($field)
            ->limit(1)
            ->find();
        return $info;
    }
}
