<?php
/**
 * sql_server数据同步脚本
 * 按输入样本号与人员对应
 */
namespace app\cron\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SyncSqlDataV1 extends Command{
    protected function configure()
    {
        $this->setName('SyncSqlDataV1')->setDescription('this api is for SyncSqlDataV1')
            ->addArgument('business')//商户唯一标识
            ->addArgument('op');//操作项
    }

    /**
     * sql_server数据同步脚本
     * php think SyncSqlDataV1 tjkx 3 天津机能设备同步脚本-西门子尿十项分析仪Clinitek Status
     * php think SyncSqlDataV1 tjkx 4 天津机能设备同步脚本-西门子全自动化学发光免疫分析仪ADVIA® Centaur CP
     * php think SyncSqlDataV1 tjkx 5 天津机能设备同步脚本-迈瑞全自动生化分析仪BS-380
     * php think SyncSqlDataV1 tjkx 6 天津机能设备同步脚本-迈瑞全自动血液分析仪BC-5385CRP
     * php think SyncSqlDataV1 tjkx 7 天津机能设备同步脚本-富士全自动干式生化分析仪-NX500i
     * php think SyncSqlDataV1 tjkx 8 天津机能设备同步脚本-西门子血液分析仪ADVIA2021i
     *
     * 瑞美sql_server添加两个字段
     * ALTER TABLE [dbo].[lis_result] ADD [sysc] varchar(20);
     * ALTER TABLE [dbo].[lis_result] ADD [sysc_time] datetime;
     */
    protected function execute(Input $input, Output $output) {
        echo date('Y-m-d H:i:s'). ' 数据开始同步 env:' . TP_ENV . PHP_EOL;
        $business = $input->getArgument('business');
        $op = $input->getArgument('op');
        switch ($op) {
            case 1:
                break;
            case 2:
                break;
            case 3:
                $this->sync_data($business, 'CLNTKS');
                break;
            case 4:
                $this->sync_data($business, 'CNTR_C');
                break;
            case 5:
                $this->sync_data($business, 'BS420');
                break;
            case 6:
                $this->sync_data($business, 'BC5300');
                break;
            case 7:
                $this->sync_data($business, 'NX500i');
                break;
            case 8:
                $this->sync_data($business, 'ADV212');
                break;
            default:
                break;
        }
        echo date('Y-m-d H:i:s'). ' 数据同步结束 env:' . TP_ENV . PHP_EOL;
    }

    //天津机能设备数据定时同步脚本
    public function sync_data($business, $ruimei_yq) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在：' . $business . '_' . $ruimei_yq . PHP_EOL;
            return;
        }
        $mysql_database = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : '';
        $mysql_prefix = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : '';
        $sqlsrv_database = !empty($business_config['sqlsrv_database']) ? $business_config['sqlsrv_database'] : '';
        $mysql_hostname = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : '';

        //数据中心数据库
        $db = get_db('','',$mysql_hostname);
        //天津mysql数据库
        $db_mysql = get_db($mysql_database, $mysql_prefix,$mysql_hostname);
        //天津机能设备sqlserver数据库
        $db_srv = get_db_srv($sqlsrv_database);

        $relation_type = config('relation_type');

        $where = [
            'sysc' => ['NULL',''],
            'yq' => $ruimei_yq,
        ];
        $field = [
            'jyrq',//检测日期
            'yq',//仪器
            'ybh',//样本号
            'xmdh',//检测项
            'csjg',//测试结果
            'bz2',//检测时间
        ];
        $test_data = $db_srv->name('lis_result')
            ->where($where)
            ->field($field)
            ->order('ybh','asc')
            ->select();
        if (empty($test_data)) {
            echo date('Y-m-d H:i:s'). ' 没有需要同步的数据' . PHP_EOL;
            return;
        }

        $xmdh_check = config('xmdh_check');
        $format_data = [];
        foreach ($test_data as $item) {
            $csjg = trim($item['csjg']);
            $csjg = str_replace('未','',$csjg);
            $csjg = str_replace('注','',$csjg);
            $csjg = str_replace('册','',$csjg);
            $csjg = trim($csjg);
            $jyrq_ori = $item['jyrq'];
            if (!empty($item['bz2'])) {
                $bz2 = date('Y-m-d H:i:s', strtotime($item['bz2']));
                $date = date('Y-m-d', strtotime($bz2));
            } else {
                $bz2 = date('Y-m-d', strtotime($item['jyrq'])) . ' ' .  date('H:i:s');
                $date = date('Y-m-d', strtotime($bz2));
            }
            $yq = trim($item['yq']);
            $ybh = trim($item['ybh']);
            $date_ybh = $date . '_' . $ybh;
            //检测指标矫正
            $xmdh_ori = $item['xmdh'];
            $xmdh = trim($item['xmdh']);
            $xmdh = trim($xmdh, '*');
            //西门子尿十项-数据过滤
            if ($relation_type[$ruimei_yq] == 3) {
                if (empty($xmdh)) {
                    if (strpos($csjg, 'LEU') !== false) {
                        $xmdh = 'LEU';
                    } else {
                        continue;
                    }
                } else if (preg_match('/^E/', $xmdh)) {
                    //过滤错误数据：E开头的错误码
                    if (!isset($format_data[$date_ybh])) {
                        $format_data[$date_ybh] = [
                            'specimen_no' => '',//用户系统对应样本号
                            'yq' => $yq,
                            'ybh' => $ybh,
                            'bz2' => $bz2,
                            'date' => $date,
                            'xmdh' => [$xmdh],
                            'maxjyrq' => $jyrq_ori,
                            'minjyrq' => $jyrq_ori,
                        ];
                    } else {
                        if (!in_array($xmdh, $format_data[$date_ybh]['xmdh'])) {
                            $format_data[$date_ybh]['xmdh'][] = $xmdh;
                        }
                        if ($format_data[$date_ybh]['maxjyrq'] < $jyrq_ori) {
                            $format_data[$date_ybh]['maxjyrq'] = $jyrq_ori;
                        }
                        if ($format_data[$date_ybh]['minjyrq'] > $jyrq_ori) {
                            $format_data[$date_ybh]['minjyrq'] = $jyrq_ori;
                        }
                    }
                    continue;
                } else if ($xmdh == 'Visibly bloody urine') {
                    continue;
                }
            } else if ($relation_type[$ruimei_yq] == 1 && $xmdh == 'PDW') {
                $xmdh = 'PDW(fl)';
            } else {
                if (empty($xmdh)) {
                    continue;
                }
            }
            $xmdh = !empty($xmdh_check[$xmdh]) ? $xmdh_check[$xmdh] : $xmdh;
            if (!isset($format_data[$date_ybh])) {
                $format_data[$date_ybh] = [
                    'specimen_no' => '',//用户系统对应样本号
                    'yq' => $yq,
                    'ybh' => $ybh,
                    'bz2' => $bz2,
                    'date' => $date,
                    $xmdh => $csjg,
                    'xmdh' => [$xmdh_ori],
                    'maxjyrq' => $jyrq_ori,
                    'minjyrq' => $jyrq_ori,
                ];
            } else {
                $format_data[$date_ybh][$xmdh] = $csjg;
                if (!in_array($xmdh_ori, $format_data[$date_ybh]['xmdh'])) {
                    $format_data[$date_ybh]['xmdh'][] = $xmdh_ori;
                }
                if ($format_data[$date_ybh]['maxjyrq'] < $jyrq_ori) {
                    $format_data[$date_ybh]['maxjyrq'] = $jyrq_ori;
                }
                if ($format_data[$date_ybh]['minjyrq'] > $jyrq_ori) {
                    $format_data[$date_ybh]['minjyrq'] = $jyrq_ori;
                }
            }
        }
        $where = [
            'is_del' => 0,
            'type' => $relation_type[$ruimei_yq],
        ];
        $relations = $db->name('equipment_relation')
            ->where($where)
            ->field('type,k,v')
            ->select();
        $v_indexs = [];
        $relation_list = [];
        foreach ($relations as $item) {
            $v_index = trim($item['v'],'k');
            $v_indexs[] = $v_index;
            $item['v_index'] = $v_index;
            $relation_list[$item['k']] = $item;
        }
        $result_list = [];
        $miss_params = [];
        foreach ($format_data as $date_ybh => $value) {
            $result_item = [
                'business' => $business,
                'result_extend' => [],
                'xmdh' => $value['xmdh'],
                'date' => $value['date'],
                'maxjyrq' => $value['maxjyrq'],
                'minjyrq' => $value['minjyrq'],
            ];
            foreach ($value as $k => $v) {
                if (in_array($k, ['xmdh', 'date', 'maxjyrq', 'minjyrq'])) {
                    continue;
                }
                $relation = !empty($relation_list[$k]) ? $relation_list[$k] : '';
                if (empty($relation)) {
                    //组装字段
                    $v_index_new = max($v_indexs) + 1;
                    $miss_v = 'k'.$v_index_new;
                    $miss_param = [
                        'type' => $relation_type[$ruimei_yq],
                        'k' => $k,
                        'v' => $miss_v
                    ];
                    //插入字段
                    $db->name('equipment_relation')->insertGetId($miss_param);
                    $db_mysql->name('equipment_relation')->insertGetId($miss_param);
                    //更新数据
                    $miss_param['v_index'] = $v_index_new;
                    $v_indexs[] = $v_index_new;
                    $relation_list[$k] = $miss_param;
                    $relation = $miss_param;

                    $miss_params[] = $miss_param;
                    echo sprintf('设备测试参数与扩展表对应关系缺少参数：type:%s;k:%s;v:%s',$relation_type[$ruimei_yq], $k, $miss_v) .PHP_EOL;
                }
                if ($relation['v_index'] > 20) {
                    $result_item['result_extend'][$relation['v']] = $v;
                } else {
                    $result_item[$relation['v']] = $v;
                }
            }
            $result_list[] = $result_item;
        }
        //设备测试参数对应关系缺少字段需要维护
        if (!empty($miss_params)) {
            $content = '设备测试参数对应关系缺少字段确认是否添加成功' . PHP_EOL;
            $content .= $business_config['name'] . PHP_EOL;
            $content .= 'time:' . date('Y-m-d H:i:s') . PHP_EOL;
            $content .= 'miss_params:' . json_encode($miss_params);
            $params = array(
                'msgtype' => 'text',
                'text' => array(
                    'content' => $content,
                ),
            );
            \app\common\QwRobot::pushMsg($params);
        }
        //用户仪器对应设备
        $device = $db_mysql->name('device_manage')->where('ruimei_yq', $ruimei_yq)->find();
        /*
         * 统一字段
        [k1] => 111-------------------用户系统对应样本号 ydy_specimen.specimen_no
        [k2] => CNTR_C----------------瑞美仪器代号 ydy_device_manage.ruimei_yq
        [k3] => 20231012104-----------仪器样本号
        [k4] => 2023-10-12 10:34:59---测试时间 ydy_equipment_result.record_time
        */
        foreach ($result_list as $item) {
            //检测项
            $xmdh = $item['xmdh'];
            unset($item['xmdh']);
            $maxjyrq = $item['maxjyrq'];
            unset($item['maxjyrq']);
            $minjyrq = $item['minjyrq'];
            unset($item['minjyrq']);
            //扩展数据
            $result_extend = !empty($item['result_extend']) ? $item['result_extend'] : [];
            unset($item['result_extend']);
            //设备
            $item['equipment_id'] = !empty($device['id']) ? $device['id'] : 0;
            //测试时间
            $item['record_time'] = $item['k4'];
            $item['create_by'] = 'cron';
            //样本号
            $item['k1'] = '';
            $item['staff_uuid'] = '';
            $item['department_uuid'] = '';
            //查询一下当前样本号
            $current_specimen_where = [
                'test_date' => $item['date'],
                'is_del' => 0,
                'specimen_no' => $item['k3'],
            ];
            $current_specimen = $db_mysql->name('specimen')->where($current_specimen_where)->find();
            if (!empty($current_specimen)) {
                $item['k1'] = $current_specimen['specimen_no'];
                $item['staff_uuid'] = $current_specimen['staff_uuid'];
                $item['department_uuid'] = $current_specimen['department_uuid'];
            }
            //当前设备-当前仪器样本号是否存在-存在即更新-不存在再新增
            $result_where = [
                'business' => $item['business'],
                'equipment_id' => $item['equipment_id'],
                'date' => $item['date'],
                'k3' => $item['k3'],
                'is_del' => 0
            ];
            $current_result = $db->name('equipment_result')->where($result_where)->find();
            if (empty($current_result)) {
                //插入数据中心数据库主表
                $mac_last_id = $db->name('equipment_result')->insertGetId($item);
                //插入用户数据库主表
                $item['reference']  = $mac_last_id;
                $last_id = $db_mysql->name('equipment_result')->insertGetId($item);
                if (!empty($result_extend)) {
                    //插入数据中心数据库扩展表
                    $result_extend['equipment_result_id'] = $mac_last_id;
                    $db->name('equipment_result_extend')->insertGetId($result_extend);
                    //插入用户数据库扩展表
                    $result_extend['equipment_result_id'] = $last_id;
                    $db_mysql->name('equipment_result_extend')->insertGetId($result_extend);
                }
            } else {
                $mac_last_id = $current_result['id'];
                //数据更新-不一样的数据
                //主数据需要更新
                $result_update = [];
                $extend_update = [];
                foreach ($item as $column => $value) {
                    if ($value != $current_result[$column]) {
                        $result_update[$column] = $value;
                    }
                }
                //扩展数据需要更新
                $current_extend = [];
                if (!empty($result_extend)) {
                    $current_extend = $db->name('equipment_result_extend')->where('equipment_result_id', $current_result['id'])->find();
                    foreach ($result_extend as $column => $value) {
                        if ($value != $current_extend[$column]) {
                            $extend_update[$column] = $value;
                        }
                    }
                }
                //查询用户数据
                $result_where_user = [
                    'business' => $item['business'],
                    'equipment_id' => $item['equipment_id'],
                    'reference' => $current_result['id']
                ];
                $current_result_user = $db_mysql->name('equipment_result')->where($result_where_user)->find();
                $current_extend_user = $db_mysql->name('equipment_result_extend')->where('equipment_result_id', $current_result_user['id'])->find();
                if (!empty($result_update)) {
                    //更新数据中心数据库主表
                    $db->name('equipment_result')->where('id', $current_result['id'])->update($result_update);
                    //更新用户数据库主表
                    $db_mysql->name('equipment_result')->where('id', $current_result_user['id'])->update($result_update);
                }
                if (!empty($extend_update)) {
                    //更新数据中心数据库扩展表
                    $db->name('equipment_result_extend')->where('id', $current_extend['id'])->update($extend_update);
                    //更新用户数据库扩展表
                    $db_mysql->name('equipment_result_extend')->where('id', $current_extend_user['id'])->update($extend_update);
                }
            }
            if (!empty($current_specimen)) {
                //更新样本号状态
                $update_specimen = [
                    'last_time' => $item['record_time'],
                ];
                if ($current_specimen['status'] == 0) {
                    $update_specimen['first_time'] = $item['record_time'];
                    $update_specimen['status'] = 1;
                }
                $db_mysql->name('specimen')->where('id', $current_specimen['id'])->update($update_specimen);
            }
            //更新原始数据状态
            $xmdh[] = '';
            $sysc_where = [
                'yq' => $item['k2'],
                'ybh' => $item['k3'],
                'xmdh' => ['in', $xmdh],
                'jyrq' => ['between', [$minjyrq, $maxjyrq]]
            ];
            $sysc_update = [
                'sysc' => $mac_last_id,
                'sysc_time' => date('Y-m-d H:i:s')
            ];
            $db_srv->name('lis_result')->where($sysc_where)->update($sysc_update);
            //同步成功消息
            $staff_info = null;
            if (!empty($item['staff_uuid'])) {
                $staff_info = $db_mysql->name('staff')->where('uuid', $item['staff_uuid'])->find();
            }
            $msg = [
                '设备' => $device['name'],
                '人员' => !empty($staff_info['name']) ? $staff_info['name'] : '',
                '人员uuid' => $item['staff_uuid'],
                '仪器样本号' => $item['k3'],
                '系统样本号' => $item['k1'],
                '测试时间' => $item['record_time'],
                'mac_last_id' => $mac_last_id,
            ];
            \app\common\QwRobot::pushMsgFormat($msg, $business_config['name']. '机能设备数据同步成功');
            echo date('Y-m-d H:i:s'). sprintf(' 数据同步成功：仪器样本号:%s；系统样本号:%s；mac_last_id:%s',$item['k3'], $item['k1'], $mac_last_id) .PHP_EOL;
        }
        echo date('Y-m-d H:i:s'). ' 数据同步完成 count：' . count($test_data) . PHP_EOL;
    }
}
