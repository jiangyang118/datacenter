<?php

namespace app\api\logic;
use think\App;
use think\Config;
use think\Exception;
use app\model\EquipmentResult as EquipmentResultModel;
use app\model\EquipmentResultExtend as EquipmentResultExtendModel;
use app\model\File as fileModel;

class Emgmeter {
    private $equipmentResultModel;
    private $equipmentResultExtendModel;
    private $fileModel;

    public function __construct() {
        $this->equipmentResultModel = new EquipmentResultModel();
        $this->equipmentResultExtendModel = new EquipmentResultExtendModel();
        $this->fileModel = new fileModel();
    }

    private function set_db_config($business_config) {
        $db = Config::get('database');
        $db['database'] = !empty($business_config['mysql_database']) ? $business_config['mysql_database'] : $db['database'];
        $db['prefix'] = !empty($business_config['mysql_prefix']) ? $business_config['mysql_prefix'] : $db['prefix'];
        $db['hostname'] = !empty($business_config['mysql_hostname']) ? $business_config['mysql_hostname'] : $db['hostname'];
        $db['sqlsrv']['hostname'] = !empty($business_config['sqlsrv_hostname']) ? $business_config['sqlsrv_hostname'] : $db['sqlsrv']['hostname'];
        $db['sqlsrv']['database'] = !empty($business_config['sqlsrv_database']) ? $business_config['sqlsrv_database'] : $db['sqlsrv']['database'];
        $db['sqlsrv']['username'] = !empty($business_config['sqlsrv_username']) ? $business_config['sqlsrv_username'] : $db['sqlsrv']['username'];
        $db['sqlsrv']['password'] = !empty($business_config['sqlsrv_password']) ? $business_config['sqlsrv_password'] : $db['sqlsrv']['password'];
        Config::set('database', $db);
    }

    /**
     * 查询设备
     */
    public function get_device($business_config, $deviceId) {
        $this->set_db_config($business_config);
        $db_srv = get_db_srv();
        return $db_srv->name('tbl_device')->where('deviceid', $deviceId)->find();
    }

    /**
     * 查询人员
     * usp_SelectUserInfoByMemberId @memberid=1000001,@storeid=75
     */
    public function getStaff($business_config, $memberid) {
        $this->set_db_config($business_config);
        $db_srv = get_db_srv();
        $sql_spl = "exec usp_SelectUserInfoByMemberId @memberid=%s,@storeid=%s";
        $sql = sprintf($sql_spl, $memberid, $business_config['store_id']);
        return $db_srv->query($sql);
    }

    /**
     * 查询人员
     */
    public function syncStaff($business_config, $pageNo = 1, $pageSize = 15) {
        $this->set_db_config($business_config);
        $db_srv = get_db_srv();
        $sql_spl = "exec usp_SelectUserInfoNew @sort=0,@value=N'',@create_time=N'',@create_time1=N'',@PageIndex=%s,
        @PageSize=%s,@firmID=-1,@birthdayyear=N'',@GradeID=N'',@ClassID=0";
        $sql = sprintf($sql_spl, $pageNo, $pageSize);
        return $db_srv->query($sql);
    }

    /**
     * 同步数据
     * @throws Exception
     */
    public function uploadUserData($business_config, $data) {
        try {
            $this->set_db_config($business_config);
            $db_srv = get_db_srv();
            
            $srv_array = [
                'storeid' => '',
                'testsiteid' => '',
        //                'devicetype' => '',
                'devicecode' => '',
                'memberid' => '',
                'intelId' => '',
        //                'name' => '',
        //                'phone' => '',
        //                'cardNo' => '',
        //                'age' => '',
                'user' => '',
        //                'sex' => '',
                'height' => 0,
                'weight' => 0,
                'bmi' => '',
        //                'birthday' => '',
                'docname' => '',
                'hosname' => '',
        //                'resultJson' => '',
                'Lbrttib' => 0,
                'LbrttibResult' => '',
                'Lsrttib' => 0,
                'LsrttibResult' => '',
                'Lbrttilm' => 0,
                'LbrttilmResult' => '',
                'Lsrttilm' => 0,
                'LsrttilmResult' => '',
                'Rbrttib' => 0,
                'RbrttibResult' => '',
                'Rsrttib' => 0,
                'RsrttibResult' => '',
                'Rbrttilm' => 0,
                'RbrttilmResult' => '',
                'Rsrttilm' => 0,
                'RsrttilmResult' => '',
                'Lbtaitmota' => 0,
                'LbtaitmotaResult' => '',
                'Lstaitmota' => 0,
                'LstaitmotaResult' => '',
                'Lbtaitmolm' => 0,
                'LbtaitmolmResult' => '',
                'Lstaitmolm' => 0,
                'LstaitmolmResult' => '',
                'Rbtaitmota' => 0,
                'RbtaitmotaResult' => '',
                'Rstaitmota' => 0,
                'RstaitmotaResult' => '',
                'Rbtaitmolm' => 0,
                'RbtaitmolmResult' => '',
                'Rstaitmolm' => 0,
                'RstaitmolmResult' => '',
                'Lbulnn' => 0,
                'LbulnnResult' => '',
                'Lsulnn' => 0,
                'LsulnnResult' => '',
                'Lbulnlm' => 0,
                'LbulnlmResult' => '',
                'Lsulnlm' => 0,
                'LsulnlmResult' => '',
                'Rbulnn' => 0,
                'RbulnnResult' => '',
                'Rsulnn' => 0,
                'RsulnnResult' => '',
                'Rbulnlm' => 0,
                'RbulnlmResult' => '',
                'Rsulnlm' => 0,
                'RsulnlmResult' => '',
                'Lbmusn' => 0,
                'LbmusnResult' => '',
                'Lsmusn' => 0,
                'LsmusnResult' => '',
                'Lbmusnlm' => 0,
                'LbmusnlmResult' => '',
                'Lsmusnlm' => 0,
                'LsmusnlmResult' => '',
                'Rbmusn' => 0,
                'RbmusnResult' => '',
                'Rsmusn' => 0,
                'RsmusnResult' => '',
                'Rbmusnlm' => 0,
                'RbmusnlmResult' => '',
                'Rsmusnlm' => 0,
                'RsmusnlmResult' => '',
                'Lbspn' => 0,
                'LbspnResult' => '',
                'Lsspn' => 0,
                'LsspnResult' => '',
                'Lbspnlm' => 0,
                'LbspnlmResult' => '',
                'Lsspnlm' => 0,
                'LsspnlmResult' => '',
                'Rbspn' => 0,
                'RbspnResult' => '',
                'Rsspn' => 0,
                'RsspnResult' => '',
                'Rbspnlm' => 0,
                'RbspnlmResult' => '',
                'Rsspnlm' => 0,
                'RsspnlmResult' => '',
                'Lbcpn' => 0,
                'LbcpnResult' => '',
                'Lscpn' => 0,
                'LscpnResult' => '',
                'Lbcpnlm' => 0,
                'LbcpnlmResult' => '',
                'Lscpnlm' => 0,
                'LscpnlmResult' => '',
                'Rbcpn' => 0,
                'RbcpnResult' => '',
                'Rscpn' => 0,
                'RscpnResult' => '',
                'Rbcpnlm' => 0,
                'RbcpnlmResult' => '',
                'Rscpnlm' => 0,
                'RscpnlmResult' => '',
                'Lbptn' => 0,
                'LbptnResult' => '',
                'Lsptn' => 0,
                'LsptnResult' => '',
                'Lbptnlm' => 0,
                'LbptnlmResult' => '',
                'Lsptnlm' => 0,
                'LsptnlmResult' => '',
                'Rbptn' => 0,
                'RbptnResult' => '',
                'Rsptn' => 0,
                'RsptnResult' => '',
                'Rbptnlm' => 0,
                'RbptnlmResult' => '',
                'Rsptnlm' => 0,
                'RsptnlmResult' => '',
                'Lbbolfam' => 0,
                'LbbolfamResult' => '',
                'Lradmla' => 0,
                'LradmlaResult' => '',
                'Lsepam' => 0,
                'LsepamResult' => '',
                'Lradmlb' => 0,
                'LradmlbResult' => '',
                'Lradmf' => 0,
                'LradmfResult' => '',
                'Lradmd' => 0,
                'LradmdResult' => '',
                'Rbbolfam' => 0,
                'RbbolfamResult' => '',
                'Rradmla' => 0,
                'RradmlaResult' => '',
                'Rsepam' => 0,
                'RsepamResult' => '',
                'Rradmlb' => 0,
                'RradmlbResult' => '',
                'Rradmf' => 0,
                'RradmfResult' => '',
                'Rradmd' => 0,
                'RradmdResult' => '',
                'Lbbotsam' => 0,
                'LbbotsamResult' => '',
                'Lbbotsala' => 0,
                'LbbotsalaResult' => '',
                'Lsetsam' => 0,
                'LsetsamResult' => '',
                'Lsetsala' => 0,
                'LsetsalaResult' => '',
                'Lsetsamf' => 0,
                'LsetsamfResult' => '',
                'Lsetsamd' => 0,
                'LsetsamdResult' => '',
                'Rbbotsam' => 0,
                'RbbotsamResult' => '',
                'Rbbotsala' => 0,
                'RbbotsalaResult' => '',
                'Rsetsam' => 0,
                'RsetsamResult' => '',
                'Rsetsamla' => 0,
                'RsetsamlaResult' => '',
                'Rsetsamf' => 0,
                'RsetsamfResult' => '',
                'Rsetsamd' => 0,
                'RsetsamdResult' => '',
                'Lbafse' => 0,
                'LbafseResult' => '',
                'Lbafsela' => 0,
                'LbafselaResult' => '',
                'Lststott' => 0,
                'LststottResult' => '',
                'Lststottla' => 0,
                'LststottlaResult' => '',
                'Lststottm' => 0,
                'LststottmResult' => '',
                'Lststottd' => 0,
                'LststottdResult' => '',
                'Rbafse' => 0,
                'RbafseResult' => '',
                'Rbafsela' => 0,
                'RbafselaResult' => '',
                'Rststott' => 0,
                'RststottResult' => '',
                'Rststottla' => 0,
                'RststottlaResult' => '',
                'Rststottm' => 0,
                'RststottmResult' => '',
                'Rststottd' => 0,
                'RststottdResult' => '',
                'Lbwapb' => 0,
                'LbwapbResult' => '',
                'Lbwapbla' => 0,
                'LbwapblaResult' => '',
                'Lsafapb' => 0,
                'LsafapbResult' => '',
                'Lsafapbla' => 0,
                'LsafapblaResult' => '',
                'Lsafapbm' => 0,
                'LsafapbmResult' => '',
                'Lsafapbd' => 0,
                'LsafapbdResult' => '',
                'Rbwapb' => 0,
                'RbwapbResult' => '',
                'Rbwapbla' => 0,
                'RbwapblaResult' => '',
                'Rsafapb' => 0,
                'RsafapbResult' => '',
                'Rsafapbla' => 0,
                'RsafapblaResult' => '',
                'Rsafapbm' => 0,
                'RsafapbmResult' => '',
                'Rsafapbd' => 0,
                'RsafapbdResult' => '',
                'Lbaah' => 0,
                'LbaahResult' => '',
                'Lbaahla' => 0,
                'LbaahlaResult' => '',
                'Lspfah' => 0,
                'LspfahResult' => '',
                'Lspfahla' => 0,
                'LspfahlaResult' => '',
                'Lspfahf' => 0,
                'LspfahfResult' => '',
                'Lspfahd' => 0,
                'LspfahdResult' => '',
                'Rbaah' => 0,
                'RbaahResult' => '',
                'Rbaahla' => 0,
                'RbaahlaResult' => '',
                'Rspfah' => 0,
                'RspfahResult' => '',
                'Rspfahla' => 0,
                'RspfahlaResult' => '',
                'Rspfahf' => 0,
                'RspfahfResult' => '',
                'Rspfahd' => 0,
                'RspfahdResult' => '',
                'test' => '',
                'report' => '',
                'conclusion' => '',
                'dateTime' => '',
            ];
            
            $resultArr = [];
            if(!empty($data['resultJson'])) {
                $data['resultJson'] = preg_replace('/(?<!\\\\)(\\\\(?!r|n|$))/', '', $data['resultJson']);
                $resultArr = json_decode($data['resultJson'], 1);
                if(!is_array($resultArr)) {
                    throw new Exception("参数【resultJson】格式错误");
                }
            }
            if($resultArr) {
                $resultArr = $resultArr[0];
            }
            
            //插入数据库
            $date = date('Y-m-d');
            $sql = "exec usp_EmgMeter ";
            foreach ($srv_array as $key => $value) {
                $key = trim($key);
                if(array_key_exists($key, $data)) {
                    $value = $data[$key];
                }
                if(array_key_exists($key, $resultArr)) {
                    $value = $resultArr[$key];
                }
                $sql .= "@{$key}=".$db_srv->quote($value).",";
//                if('height' == $key || 'weight' == $key) {
//                    $sql .= "@{$key}={$value},";
//                } else {
//                    $sql .= "@{$key}='{$value}',";
//                }
//                $sql .= "@{$key}='{$value}',";
            }
            $sql = trim($sql, ',');
            $exam_res = $db_srv->query($sql);
            $examid = 0;
            if(!empty($exam_res[0][0]['id'])) {
                $examid = $exam_res[0][0]['id'];
            }
            if(!$examid) {
                throw new Exception("测试结果保存失败");
            }
//            $examid = !empty($exam_res[0][0]['id']) ? $exam_res[0][0]['id'] : 1;
            
            //查找数据中心是否存在此记录
            $equipment_result_exist = $this->equipmentResultModel->inquiryOne(['k19' => $examid, 'is_del' => 0], ['id' => 'desc']);
            if($equipment_result_exist) {
                $this->equipmentResultModel->modifyByWhere(['k19'=>$examid], ['is_del'=>1]);
                $this->equipmentResultExtendModel->modifyByWhere(['equipment_result_id'=>$equipment_result_exist['id']], ['is_del'=>1]);
            }
            //插入数据中心数据库
            $equipment_result = [
                'business' => $data['business'],
                'relation_type' => 22,
                'date' => $date,
                'record_time' => !empty($resultArr['dateTime']) ? $resultArr['dateTime'] : date("Y-m-d H:i:s"),//todo
                'reference' => App::$trace_id,
                'k1' => $data['storeid'],
                'k2' => $data['testsiteid'],
                'k3' => $data['devicetype'],
                'k4' => $data['devicecode'],
                'k5' => $data['memberid'],
                'k6' => $data['intelId'],
                'k7' => $data['name'],
                'k8' => $data['phone'],
                'k9' => $data['cardNo'],
                'k10' => $data['age'],
                'k11' => $data['user'],
                'k12' => $data['sex'],
                'k13' => $data['height'],
                'k14' => $data['weight'],
                'k15' => $data['bmi'],
                'k16' => $data['birthday'],
                'k17' => $data['docname'],
                'k18' => $data['hosname'],
                'k19' => $examid,
                'create_by' => 'api',
            ];
            $equipment_result_id = $this->equipmentResultModel->add($equipment_result);
            $equipment_result_extend = [
                'equipment_result_id' => $equipment_result_id,
                'other_info' => $data['resultJson'],
            ];
            $this->equipmentResultExtendModel->add($equipment_result_extend);
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'examid' => 0];
        }
        return ['code' => 0,'message' => '同步成功', 'examid'=> $examid];
    }

    /**
     * 仅上传图片
     * @throws Exception
     */
    public function addFile($file, $data, $business_config) {
        try {
            //上传文件
            $file_path = $data['business'].DS.'emgmeter'.DS.$data['examid'];
            $file_info = $this->fileModel->uploadFile($file, $file_path, true);
            if ($file_info['code']) {
                throw new Exception($file_info['message']);
            }
            $equipment_result = $this->equipmentResultModel->inquiryOne(['k19' => $data['examid'], 'is_del' => 0], ['id' => 'desc']);
            if (!empty($equipment_result)) {
                $this->equipmentResultModel->modifyById($equipment_result['id'], ['k20' => $file_info['data']]);
            }
            //更新体健之星数据库报告路径
            $this->set_db_config($business_config);
            $db_srv = get_db_srv();
            $sysc_where = [
                'examid' => $data['examid']
            ];
            $sysc_update = [
                'reportFilePath' => config('domain') . $file_info['data']
            ];
            $db_srv->name('tbl_emgmeter')->where($sysc_where)->update($sysc_update);
        } catch (Exception $ex) {
            return ['code' => 1,'message' => $ex->getMessage(), 'data'=>[]];
        }
        return ['code' => 0,'message' => '上传成功', 'data'=> []];
    }

}
