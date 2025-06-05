<?php
namespace app\model;

use app\model\BaseModel;

class Config extends BaseModel {

    /**
     * 获取配置
     * @param $key
     * @return string|array
     * @throws
     */
    public function getConfig($keys = null) {
        $field = 'config_key,config_value';
        $where['status'] = 1;
        if (!empty($keys)) {
            if (is_array($keys)) {
                $where['config_key'] = ['in', $keys];
            } else {
                $where['config_key'] = $keys;
            }
        }
        $configs = $this->inquiryAll($where, [], $field);

        $configMaps = [];
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $configMaps[$config['config_key']] = $config['config_value'];
            }
        }

        if (!empty($keys) && !is_array($keys)) {
            return isset($configMaps[$keys]) ?  $configMaps[$keys] : null;
        }

        return $configMaps;
    }

    /**
     * 获取配置多餐厅
     * @param $key
     * @return string|array
     * @throws
     */
    public function getConfigRestaurant($restaurant_id, $keys = null) {
        $field = 'config_key,config_value';
        $where['status'] = 1;
        $where['restaurant_id'] = $restaurant_id;
        if (!empty($keys)) {
            if (is_array($keys)) {
                $where['config_key'] = ['in', $keys];
            } else {
                $where['config_key'] = $keys;
            }
        }
        $configs = $this->inquiryAll($where, [], $field);

        $configMaps = [];
        if (!empty($configs)) {
            foreach ($configs as $config) {
                $configMaps[$config['config_key']] = $config['config_value'];
            }
        }

        if (!empty($keys) && !is_array($keys)) {
            return isset($configMaps[$keys]) ?  $configMaps[$keys] : null;
        }

        return $configMaps;
    }

    /**
     * 配置参数
     * @param $params
     * @param $create_by
     * @return bool
     */
    public function setConfig($config_data, $create_by) {
        $config_keys = array_keys($config_data);
        $where = [
            'config_key' => ['in', $config_keys],
            'status' => 1,
            'restaurant_id'=>!empty($config_data['restaurant_id']) ? $config_data['restaurant_id'] : 1
        ];
        $field = 'id,config_key,config_value,restaurant_id';
        $configs = $this->inquiryAll($where, [], $field);
        if (!empty($configs)) {
            $configs = array_column($configs, null, 'config_key');
        }
        foreach ($config_data as $key => $value) {
            $config = isset($configs[$key]) ? $configs[$key] : null;
            $tbData = [];
            $tbData['config_key'] = $key;
            $tbData['config_value'] = $value;
            if (empty($config)) {
                $tbData['status'] = 1;
                $tbData['create_time'] = date('Y-m-d H:i:s');
                $tbData['create_by'] = $create_by;
                $res = $this->add($tbData);
                if (!$res) {
                    return false;
                }
            } else if ($config['config_value'] != $value) {
                $res = $this->modifyById($config['id'], $tbData);
                if (!$res) {
                    return false;
                }
            }
        }
        return true;
    }
}