<?php
/**
 * Created by PengJu
 * User: RenPengJu
 * Motto: 现在的努力是为了小时候吹过的牛逼
 * Time: 2024/8/5/18:37
 *visudo
 * www ALL=(ALL) NOPASSWD: ALL

 */

namespace app\worker\controller;

use app\base\ApiController;

class ReloadWorker extends ApiController
{
    public function reloadWorkerR()
    {
        exec("sudo -u www  chmod -R 755  /workspace/developSite/datacenter/application/worker/shell");
        exec("sudo -u www  chmod -R 755  /workspace/wwwroot/datacenter/project/application/worker/shell");
        if (TP_ENV == 'prod') {
            $scriptPath = "/workspace/wwwroot/datacenter/project/application/worker/shell/rowingmachine.sh 2>&1";
        }
        else
        {
            $scriptPath = "/workspace/developSite/datacenter/application/worker/shell/rowingmachine.sh 2>&1";
        }
        exec($scriptPath, $output, $return_var);
        return json(['code' => 0, 'message' => "同步成功", 'data' => '']);
    }

    //划船机心率
    public function rHeartRateR()
    {
        exec("sudo -u www  chmod -R 755  /workspace/developSite/datacenter/application/worker/shell");
        exec("sudo -u www  chmod -R 755  /workspace/wwwroot/datacenter/project/application/worker/shell");
        if (TP_ENV == 'prod') {
            $scriptPath = "/workspace/wwwroot/datacenter/project/application/worker/shell/rheartrate.sh 2>&1";
        }
        else
        {
            $scriptPath = "/workspace/developSite/datacenter/application/worker/shell/rheartrate.sh 2>&1";
        }
        exec($scriptPath, $output, $return_var);
        return json(['code' => 0, 'message' => "重启成功", 'data' => '']);
    }

    //wattbike心率
    public function wHeartRate()
    {
        exec("sudo -u www  chmod -R 755  /workspace/developSite/datacenter/application/worker/shell");
        exec("sudo -u www  chmod -R 755  /workspace/wwwroot/datacenter/project/application/worker/shell");
        if (TP_ENV == 'prod') {
            $scriptPath = "/workspace/wwwroot/datacenter/project/application/worker/shell/wheartrate.sh 2>&1";
        }
        else
        {
            $scriptPath = "/workspace/developSite/datacenter/application/worker/shell/wheartrate.sh 2>&1";
        }
        exec($scriptPath, $output, $return_var);
        return json(['code' => 0, 'message' => "重启成功", 'data' => '']);
    }

    //总览
    public function aHeartRate()
    {
        exec("sudo -u www  chmod -R 755  /workspace/developSite/datacenter/application/worker/shell");
        exec("sudo -u www  chmod -R 755  /workspace/wwwroot/datacenter/project/application/worker/shell");
        if (TP_ENV == 'prod') {
            $scriptPath = "/workspace/wwwroot/datacenter/project/application/worker/shell/aheartrate.sh 2>&1";
        }
        else
        {
            $scriptPath = "/workspace/developSite/datacenter/application/worker/shell/aheartrate.sh 2>&1";
        }

        exec($scriptPath, $output, $return_var);
        return json(['code' => 0, 'message' => "重启成功", 'data' => '']);
    }


    public function reloadWattbike()
    {
        exec("sudo -u www  chmod -R 755  /workspace/developSite/datacenter/application/worker/shell");
        exec("sudo -u www  chmod -R 755  /workspace/wwwroot/datacenter/project/application/worker/shell");
        if (TP_ENV == 'prod') {
            $scriptPath = "/workspace/wwwroot/datacenter/project/application/worker/shell/wattbike.sh 2>&1";
        }
        else
        {
            $scriptPath = "/workspace/developSite/datacenter/application/worker/shell/wattbike.sh 2>&1";
        }

        exec($scriptPath, $output, $return_var);
        return json(['code' => 0, 'message' => "重启成功", 'data' => '']);
    }
}