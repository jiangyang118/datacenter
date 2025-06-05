<?php
/**
 * sql_server数据同步脚本
 */
namespace app\cron\command;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SyncSqlData extends Command{
    protected function configure()
    {
        $this->setName('SyncSqlData')->setDescription('this api is for SyncSqlData')
            ->addArgument('business')//商户唯一标识
            ->addArgument('op');//操作项
    }

    /**
     * sql_server数据同步脚本
     * php think SyncSqlData tnxl 1 初始化体能希森美康KX21N数据
     * php think SyncSqlData tjkx 2 初始化天津机能设备数据
     * php think SyncSqlData tjkx 3 天津机能设备同步脚本-西门子尿十项分析仪Clinitek Status
     * php think SyncSqlData tjkx 4 天津机能设备同步脚本-西门子全自动化学发光免疫分析仪ADVIA® Centaur CP
     * php think SyncSqlData tjkx 5 天津机能设备同步脚本-迈瑞全自动生化分析仪BS-380
     * php think SyncSqlData tjkx 6 天津机能设备同步脚本-迈瑞全自动血液分析仪BC-5385CRP
     * php think SyncSqlData tjkx 7 天津机能设备同步脚本-富士全自动干式生化分析仪-NX500i
     * php think SyncSqlData tjkx 8 天津机能设备同步脚本-西门子血液分析仪ADVIA2021i
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
                $this->init_kx21n_data();
            case 2:
                $this->init_data($business);
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
//        $str = '[{"jyrq":"2023-11-16 00:00:00.000","yq":"BS420 ","ybh":"1","xmdh":"ALB Ⅱ","csjg":" 45.616935 未注册","bz2":"2023/11/16 09:59:38","ROW_NUMBER":"1"},{"jyrq":"2023-11-16 00:00:00.000","yq":"BS420 ","ybh":"1","xmdh":"ALP","csjg":" 76 未 注册","bz2":"2023/11/16 09:59:38","ROW_NUMBER":"2"},{"jyrq":"2023-11-16 00:00:00.000","yq":"BS420 ","ybh":"1","xmdh":"ALT","csjg":" 9 未注册","bz2":"2023/11/16 09:59:38","ROW_NUMBER":"3"},{"jyrq":"2023-11-16 00:00:00.000","yq":"BS420 ","ybh":"1","xmdh":"AST","csjg":" 33 未 注册","bz2":"2023/11/16 09:59:38","ROW_NUMBER":"4"},{"jyrq":"2023-11-16 00:00:00.000","yq":"BS420 ","ybh":"1","xmdh":"CA","csjg":" 2.51未 注册","bz2":"2023/11/16 09:59:38","ROW_NUMBER":"5"},{"jyrq":"2023-11-16 00:00:00.000","yq":"BS420 ","ybh":"1","xmdh":"CHE","csjg":"6801 未注册","bz2":"2023/11/16 09:59:38","ROW_NUMBER":"6"},{"jyrq":"2023-11-16 00:00:00.000","yq":"BS420 ","ybh":"1","xmdh":"CK","csjg":"434未注册","bz2":"2023/11/16 09:59:38","ROW_NUMBER":"7"}]';
//        $test_data = json_decode($str, true);
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
                } else if (preg_match('/E/', $xmdh)) {
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
                }
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
                $current_specimen = $this->get_current_specimen($db_mysql, $item);
                if (!empty($current_specimen)) {
                    $item['k1'] = $current_specimen['specimen_no'];
                    $item['staff_uuid'] = $current_specimen['staff_uuid'];
                    $item['department_uuid'] = $current_specimen['department_uuid'];
                }
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
                //查询一下当前样本号
                $current_specimen_where = [
                    'test_date' => $item['date'],
                    'is_del' => 0,
                    'specimen_no' => $current_result['k1'],
                ];
                $current_specimen = $db_mysql->name('specimen')->where($current_specimen_where)->find();
                if (!empty($current_specimen)) {
                    $item['k1'] = $current_specimen['specimen_no'];
                    $item['staff_uuid'] = $current_specimen['staff_uuid'];
                    $item['department_uuid'] = $current_specimen['department_uuid'];
                    $current_specimen['k1'] = $current_result['k1'];
                }
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
                    $last_specimen_key = sprintf('%s_%s_%s', $item['business'], $item['equipment_id'], $item['date']);
                    $this->last_specimen[$last_specimen_key]['status'] = 1;
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

    //初始化天津机能设备数据
    public function init_data($business) {
        $business_set = config('business');
        $business_config = !empty($business_set[$business]) ? $business_set[$business] : '';
        if (empty($business_config)) {
            echo date('Y-m-d H:i:s'). ' 用户不存在' . PHP_EOL;
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

        $relation_type = [
            'CLNTKS' => 3,
            'CNTR_C' => 4,
            'BS420' => 2,
            'BC5300' => 1
        ];

        $where = [
            'yq' => ['in', array_keys($relation_type)],
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
            ->order(['yq' => 'asc', 'ybh' => 'asc'])
            ->select();
        if (empty($test_data)) {
            echo date('Y-m-d H:i:s'). ' 没有需要同步的数据' . PHP_EOL;
            return;
        }
        $xmdh_check = config('xmdh_check');
        $format_data_list = [];
        foreach ($test_data as $item) {
            $csjg = trim($item['csjg']);
            $csjg = str_replace('未','',$csjg);
            $csjg = str_replace('注','',$csjg);
            $csjg = str_replace('册','',$csjg);
            $csjg = trim($csjg);
            //$bz2 = date('Y-m-d H:i:s', strtotime($item['bz2']));
            //todo test
            $bz2 = date('Y-m-d H:i:s');
            $yq = trim($item['yq']);
            $ybh = trim($item['ybh']);
            //检测指标矫正
            $xmdh = trim($item['xmdh']);
            $xmdh = trim($xmdh, '*');
            //过滤错误数据E61,E69
            if (in_array($xmdh, ['E61', 'E69'])) {
                continue;
            }
            if (empty($xmdh)) {
                if (strpos($csjg, 'LEU') !== false) {
                    $xmdh = 'LEU';
                } else {
                    continue;
                }
            }
            $xmdh = !empty($xmdh_check[$xmdh]) ? $xmdh_check[$xmdh] : $xmdh;
            $format_data = [
                'specimen_no' => '',//用户系统对应样本号
                'yq' => $yq,
                'ybh' => $ybh,
                'xmdh' => $xmdh,
                'csjg' => $csjg,
                'bz2' => $bz2,
            ];
            if (!isset($format_data_list[$format_data['yq']]) || !isset($format_data_list[$format_data['yq']][$format_data['ybh']])) {
                $format_data_list[$format_data['yq']][$format_data['ybh']] = [
                    'specimen_no' => $format_data['specimen_no'],
                    'yq' => $format_data['yq'],
                    'ybh' => $format_data['ybh'],
                    'bz2' => $format_data['bz2'],
                    $format_data['xmdh'] => $format_data['csjg'],
                ];
            } else {
                $format_data_list[$format_data['yq']][$format_data['ybh']][$format_data['xmdh']] = $format_data['csjg'];
            }
        }

        $where = [
            'is_del' => 0,
            'type' => ['in', array_values($relation_type)],
        ];
        $relation_data = $db->name('equipment_relation')
            ->where($where)
            ->field('type,k,v')
            ->select();
        $relation_list = [];
        foreach ($relation_data as $item) {
            $relation_list[$item['type']][$item['k']] = $item['v'];
        }
        $result_list = [];
        foreach ($format_data_list as $yq => $item) {
            $type = $relation_type[$yq];
            foreach ($item as $key => $value) {
                $result_item = [
                    'business' => $business,
                    'result_extend' => [],
                ];
                foreach ($value as $k => $v) {
                    if (isset($relation_list[$type][$k])) {
                        if (trim($relation_list[$type][$k],'k') > 20) {
                            $result_item['result_extend'][$relation_list[$type][$k]] = $v;
                        } else {
                            $result_item[$relation_list[$type][$k]] = $v;
                        }
                    } else {
                        echo sprintf('缺少参数：type:%s;k:%s;v:%s',$type, $k, $v) .PHP_EOL;//todo 缺少字段
                        $content = '设备测试参数对应关系缺少字段确实是否添加成功' . PHP_EOL;
                        $content .= $business_config['name'] . PHP_EOL;
                        $content .= 'time:' . date('Y-m-d H:i:s') . PHP_EOL;
                        $content .= 'type:' . $type . PHP_EOL;
                        $content .= 'k:' . $k;
                        $params = array(
                            'msgtype' => 'text',
                            'text' => array(
                                'content' => $content,
                                'mentioned_mobile_list' => array(
                                    '15811137696'
                                )
                            ),
                        );
                        \app\common\QwRobot::pushMsg($params);
                    }
                }
                $result_list[] = $result_item;
            }
        }

        //用户仪器对应设备
        $device_where = [
            'is_show' => 1,
            'ruimei_yq' => ['in', array_keys($relation_type)],
        ];
        $devices = $db_mysql->name('device_manage')
            ->where($device_where)
            ->field(['id','ruimei_yq'])
            ->select();
        $devices = array_column($devices, null, 'ruimei_yq');

        /*
         * 统一字段
        [k1] => 111-------------------用户系统对应样本号 ydy_specimen.specimen_no
        [k2] => CNTR_C----------------瑞美仪器代号 ydy_device_manage.ruimei_yq
        [k3] => 20231012104-----------仪器样本号
        [k4] => 2023-10-12 10:34:59---测试时间 ydy_equipment_result.record_time
        */
        foreach ($result_list as $item) {
            //设备
            $device  = !empty($devices[$item['k2']]) ? $devices[$item['k2']] : null;
            $item['equipment_id'] = !empty($device['id']) ? $device['id'] : 0;
            //测试时间
            $item['record_time'] = $item['k4'];
            $item['date'] = date('Y-m-d', strtotime($item['k4']));
            $item['create_by'] = 'cron';
            //样本号
            $item['k1'] = '';
            $item['staff_uuid'] = '';
            $item['department_uuid'] = '';
            $current_specimen = $this->get_current_specimen($db_mysql, $item);
            if (!empty($current_specimen)) {
                $item['k1'] = $current_specimen['specimen_no'];
                $item['staff_uuid'] = $current_specimen['staff_uuid'];
                $item['department_uuid'] = $current_specimen['department_uuid'];
            }
            $result_extend = $item['result_extend'];
            unset($item['result_extend']);

            //插入数据中心数据库主表
            $mac_last_id = $db->name('equipment_result')->insertGetId($item);
            //插入用户数据库主表
            $item['reference']  = $mac_last_id;
            $last_id = $db_mysql->name('equipment_result')->insertGetId($item);
            if (!empty($result_extend)) {
                //插入数据中心数据库扩展表
                $result_extend['equipment_result_id'] = $mac_last_id;
                $db->name('equipment_result_extend')->insert($result_extend);
                //插入用户数据库扩展表
                $result_extend['equipment_result_id'] = $last_id;
                $db_mysql->name('equipment_result_extend')->insert($result_extend);
            }
            if (!empty($current_specimen)) {
                //更新样本号状态
                $update_specimen = [
                    'last_time' => $item['record_time'],
                ];
                if ($current_specimen['status'] == 0) {
                    $update_specimen['first_time'] = $item['record_time'];
                    $update_specimen['status'] = 1;
                    $last_specimen_key = sprintf('%s_%s_%s', $item['business'], $item['equipment_id'], $item['date']);
                    $this->last_specimen[$last_specimen_key]['status'] = 1;
                }
                $db_mysql->name('specimen')->where('id', $current_specimen['id'])->update($update_specimen);
            }
            //同步成功消息
            $staff_info = null;
            if (!empty($item['staff_uuid'])) {
                $staff_info = $db_mysql->name('staff')->where('uuid', $item['staff_uuid'])->find();
            }
            $msg = [
                '设备' => !empty($device['name']) ? $device['name'] : '',
                '人员' => !empty($staff_info['name']) ? $staff_info['name'] : '',
                '人员uuid' => $item['staff_uuid'],
                '仪器样本号' => $item['k3'],
                '系统样本号' => $item['k1'],
                'mac_last_id' => $mac_last_id,
            ];
            \app\common\QwRobot::pushMsgFormat($msg, $business_config['name']. '机能设备数据同步成功');
            echo date('Y-m-d H:i:s'). sprintf(' 数据同步成功：仪器样本号:%s；系统样本号:%s；mac_last_id:%s',$item['k3'], $item['k1'], $mac_last_id) .PHP_EOL;
        }
        echo date('Y-m-d H:i:s'). ' 数据同步完成 count：' . count($test_data) . PHP_EOL;
    }

    //当天的全部样本号
    public $specimen = [];
    public $last_specimen = [];
    //获取对应的样本号
    public function get_current_specimen($db, $item) {
        if (!isset($this->specimen[$item['date']])) {
            $specimen_where = [
                'a.test_date' => $item['date'],
                'a.is_del' => 0,
                'b.equipment_id' => $item['equipment_id'],
            ];
            $specimens = $db->name('specimen')
                ->alias('a')
                ->join('specimen_equipment b','a.id = b.specimen_id', 'left')
                ->where($specimen_where)
                ->field('a.*')
                ->order(['a.test_time' => 'asc', 'a.specimen_no' => 'asc'])
                ->select();
            if (!empty($specimens)) {
                $this->specimen[$item['date']]['specimen_no'] = array_column($specimens, 'specimen_no');
                $this->specimen[$item['date']]['specimen_list'] = array_column($specimens, null, 'specimen_no');
            } else {
                $this->specimen[$item['date']]['specimen_no'] = [];
                $this->specimen[$item['date']]['specimen_list'] = [];
            }
        }
        if (empty($this->specimen[$item['date']]['specimen_no'])) {
            return null;
        }
        //当前仪器的上一个样本号信息
        $last_specimen_key = sprintf('%s_%s_%s', $item['business'], $item['equipment_id'], $item['date']);
        if (!empty($this->last_specimen[$last_specimen_key])) {
            $last_specimen = $this->last_specimen[$last_specimen_key];
        } else {
            $result_where = [
                'business' => $item['business'],
                'equipment_id' => $item['equipment_id'],
                'date' => $item['date'],
                'k1' => ['<>', ''],//非空
                'is_del' => 0
            ];
            $last_specimen = $db->name('equipment_result')
                ->where($result_where)
                ->field('k1')
                ->order('id', 'desc')
                ->limit(1)
                ->find();
        }
        if (empty($last_specimen)) {
            $current_specimen_no = $this->specimen[$item['date']]['specimen_no'][0];
        } else {
            $last_index = array_search($last_specimen['k1'], $this->specimen[$item['date']]['specimen_no']);
            $current_specimen_no = !empty($this->specimen[$item['date']]['specimen_no'][$last_index + 1])
                ? $this->specimen[$item['date']]['specimen_no'][$last_index + 1] : '';
        }
        $current_specimen = null;
        if (!empty($this->specimen[$item['date']]['specimen_list'][$current_specimen_no])) {
            $current_specimen = $this->specimen[$item['date']]['specimen_list'][$current_specimen_no];
            $current_specimen['k1'] = $current_specimen_no;
            $this->last_specimen[$last_specimen_key] = $current_specimen;
        }
        return $current_specimen;
    }

    //组装设备测试参数与扩展表对应关系
    public function equipment_relation($format_data_list) {
        $type = 1;
        $relation = [];
        $sql = "INSERT INTO `ydy_equipment_relation` (`type`, `k`, `v`, `create_by`) VALUES (%d, '%s', '%s', 'laiqingtao');";
        foreach ($format_data_list as $item) {
            $index = 1;
            $first = reset($item);
            echo '-- ' .$first['yq'] .PHP_EOL;
            foreach ($first as $k => $value) {
                $v = 'k' . $index;
                $relation_item = [
                    'type' => $type,
                    'k' => $k,
                    'v' => $v,
                ];
                $relation[] = $relation_item;
                echo sprintf($sql, $type, $k, $v) . PHP_EOL;
                $index ++;
            }
            $type ++;
        }
        print_r($relation);
    }

    //初始化安徽希森美康KX21N数据
    public function init_kx21n_data() {
        $db = get_db_srv('tn_anhui_lis');
        $db_mysql = get_db('tnxl_dev', 'tn_');

        $where = [
//            'sysc' => ['NULL','']
            'yq' => 'KX21'
        ];
        $field = [
            'jyrq',//检测时间
            'yq',//仪器
            'ybh',//样本号
            'xmdh',//检测项
            'csjg',//测试结果
            'bz2',//检测时间
        ];
        $test_data = $db->name('lis_result')
            ->where($where)
            ->field($field)
            ->order('ybh', 'asc')
            ->select();
        if (empty($test_data)) {
            echo date('Y-m-d H:i:s'). ' 没有需要同步的数据' . PHP_EOL;
            return;
        }

        $equipment = $db_mysql->name('equipment_hardware')->where('model_num', 'KX21N')->find();
        $test_date = date('Y-m-d');
        $test_time_no = time();

        $detection_item = $db_mysql->name('detection_item')->where('model_num', 'KX21N')->select();
        $detection_item = array_column($detection_item, null, 'test_item');

        //当前样本号
        $specimen_d = $db_mysql->name('specimen')
            ->where('test_date', $test_date)
            ->where('is_del', 0)
            ->order('id', 'desc')
            ->find();
        $specimen_no_index = !empty($specimen_d) ? $specimen_d['specimen_no'] + 1: 1;
        $specimen_no_list = [];
        $specimen_list = [];
        $detection_list = [];
        foreach ($test_data as $item) {
            if (empty($specimen_no_list[$item['ybh']])) {
                $specimen_no = $specimen_no_index;
                $specimen_no_index ++;
                $specimen_no_list[$item['ybh']] = $specimen_no;
                $test_time = date('Y-m-d H:i:s', $test_time_no + $specimen_no);
                //样本号
                $specimen_info = [
                    'test_date' => $test_date,
                    'specimen_no' => $specimen_no,
                    'staff_uuid' => '',
                    'staff_name' => '',
                    'status' => 1,
                    'equipment_name' => $equipment['name'],
                    'equipment_no' => $equipment['model_num'],
                    'equipment_uuid' => $equipment['uuid'],
                    'test_time' => $test_time,
                    'create_time' => date('Y-m-d H:i:s'),
                    'create_by' => 'cron',
                    'file_path' => '',
                    'specimen_type' => '',
                    'person_type' => '',
                    'office' => '',
                    'diagnosis' => '',
                    'cost_type' => '',
                    'hospital_num' => '',
                    'bed_num' => '',
                    'demo' => '',
                    'is_del' => 0,
                ];
                $specimen_list[] = $specimen_info;
            } else {
                $specimen_no = $specimen_no_list[$item['ybh']];
                $test_time = date('Y-m-d H:i:s', $test_time_no + $specimen_no);
            }

            $test_value = $item['csjg'];
            $test_value = str_replace('未','',$test_value);
            $test_value = str_replace('注','',$test_value);
            $test_value = str_replace('册','',$test_value);
            $test_value = trim($test_value);
            $detection_info = [
                'test_date' => $test_date,
                'specimen_no' => $specimen_no,
                'staff_uuid' => '',
                'test_item' => trim($item['xmdh']),
                'test_value' => $test_value,
                'test_result' => 2,
                'test_time' => $test_time,
                'test_xmmc' => '',
                'test_cksx' => '',
                'test_ckxx' => '',
                'test_dyckz' => '',
                'test_dw' => '',
                'equipment_name' => $equipment['name'],
                'equipment_no' => $equipment['model_num'],
                'equipment_uuid' => $equipment['uuid'],
                'create_time' => date('Y-m-d H:i:s'),
                'is_del' => 0,
            ];
            $detection_info['test_xmmc'] = $detection_item[$detection_info['test_item']]['test_xmmc'];
            $detection_info['test_cksx'] = $detection_item[$detection_info['test_item']]['test_cksx'];
            $detection_info['test_ckxx'] = $detection_item[$detection_info['test_item']]['test_ckxx'];
            $detection_info['test_dyckz'] = $detection_item[$detection_info['test_item']]['test_dyckz'];
            $detection_info['test_dw'] = $detection_item[$detection_info['test_item']]['test_dw'];
            $test_result = 2;
            if (!empty($detection_info['test_ckxx']) && !empty($detection_info['test_cksx'])) {
                if ($test_value < $detection_info['test_ckxx']) {
                    $test_result = 1;
                } else if($test_value > $detection_info['test_cksx']) {
                    $test_result = 3;
                } else {
                    $test_result = 2;
                }
            }
            $detection_info['test_result'] = $test_result;
            $detection_list[] = $detection_info;
        }
        $db_mysql->name('specimen')->insertAll($specimen_list);
        $db_mysql->name('detection_info')->insertAll($detection_list);
        echo date('Y-m-d H:i:s'). ' 数据同步完成 count：' . count($test_data) . PHP_EOL;
    }
}
