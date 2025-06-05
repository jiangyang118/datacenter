<?php

/**
 * model的基类
 */
namespace app\model;

use think\Model;
class BaseModel extends Model {
    protected $dateFormat = false;
    protected $resultSetType = 'collection';

    public function __construct($data = array()) {
        parent::__construct($data);
    }

    /**
     * 获取某一列的值
     * @param type $where
     * @param type $field   要去的列名
     * @param type $key     用作键的列名
     * @return type
     */
    public function inquiryColumn($where, $field, $key=null) {
        if($key) {
            return $this->where($where)->column($field, $key);
        }
        return $this->where($where)->column($field);
    }

    /**
     * 获取所有符合条件的数据
     * @param type $where
     * @param type $orderBy
     * @param type $field
     * @return type
     */
    public function inquiryAll($where=[], $orderBy=[], $field=['*']) {
        return $this->where($where)
            ->order($orderBy)
            ->field($field)
            ->select()
            ->toArray();
    }

    /**
     * 获取满足查询条件的总记录数
     * @param type $where
     * @return type
     */
    public function inquiryCount($where=[]) {
        return $this->where($where)->count();
    }

    /**
     * 获取符合条件的一条数据
     * @param array $where
     * @param array $orderBy
     * @param array $field
     * @return array | bool
     */
    public function inquiryOne($where=[], $orderBy=[], $field=['*']) {
        $info = $this->where($where)
            ->order($orderBy)
            ->field($field)
            ->limit(1)
            ->find();
        if (!empty($info)) {
            return $info->toArray();
        }
        return false;
    }

    /**
     * 查询满足条件的记录
     * @param type $where
     * @param type $page
     * @param type $pageSize
     * @param type $orderBy
     * @return type
     */
    public function inquiry($where=[], $page=1, $pageSize=15, $orderBy=[], $field=['*']) {
        return $this->where($where)
            ->order($orderBy)
            ->page($page, $pageSize)
            ->field($field)
            ->select()
            ->toArray();
    }

    /**
     * 添加记录
     * @param type $data
     * @param type $replace
     * @return type
     */
    public function add($data, $replace=false) {
        return $this->insertGetId($data, $replace);
    }

    /**
     * 修改记录,通过uuid
     * @param type $uuid
     * @param type $data
     * @return type
     */
    public function modify($uuid, $data) {
        return $this->where("uuid", $uuid)->update($data);
    }

    /**
     * 修改记录，通过id
     * @param type $id
     * @param type $data
     * @return type
     */
    public function modifyById($id, $data) {
        return $this->where("id", $id)->update($data);
    }

    /**
     * 删除记录，通过uuid
     */
    public function del($uuid) {
        return $this->where("uuid", $uuid)->delete();
    }

    /**
     * 删除记录，通过id
     * @param type $id
     * @return type
     */
    public function delById($id) {
        return $this->where("id", $id)->delete();
    }

    /**
     * 查看记录，通过uuid
     * @param type $uuid
     */
    public function read($uuid, $field=['*']) {
        $info = $this->where("uuid", $uuid)->field($field)->find();
        if($info) {
            $info = $info->toArray();
        }
        return $info;
    }

    /**
     * 查看记录，通过id
     * @param type $id
     * @return type
     */
    public function readById($id) {
        $info = $this->where("id", $id)->find();
        if($info) {
            $info = $info->toArray();
        }
        return $info;
    }

    /**
     * 批量添加记录
     * @param type $dataSet
     * @param type $replace
     * @return type
     */
    public function addAll($dataSet, $replace=false) {
        return $this->insertAll($dataSet, $replace);
    }

    /**
     * 太危险，所有定义为私有，尽量不用使用它
     * 按条件修改数据
     * @param type $where
     * @param type $data
     * @return type
     */
    public function modifyByWhere($where, $data) {
        return $this->where($where)->update($data);
    }

    /**
     * 太危险，所有定义为私有，尽量不用使用它
     * 按条件删除
     * @param type $where
     * @return type
     */
    public function delByWhere($where) {
        return $this->where($where)->delete();
    }
}
