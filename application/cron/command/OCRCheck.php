<?php

namespace app\cron\command;
use app\api\logic\Action;
use app\model\EngineryDataImg;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class OCRCheck extends Command{

    protected function configure() {
        $this->setName('ocr_check')->setDescription('this api is for ocr_check');
    }

    /**
     * ocr识别脚本
     * php think ocr_check 每分钟执行一次
     */
    protected function execute(Input $input, Output $output) {
        echo date('Y-m-d H:i:s'). ' ocr start' . PHP_EOL;
        if (!config('ocr_disjunctor')) {//阿里云OCR开关
            echo date('Y-m-d H:i:s'). ' ocr_disjunctor stop' . PHP_EOL;
            return;
        }
        $imgModel = new EngineryDataImg();
        $ActionLogic = new Action();
        $img_where = [
            'file_path' => ['<>', ''],
            'ocr_status' => 0,
            'is_del' => 0,
        ];
        $imgs = $imgModel->field('*')
            ->where($img_where)
            ->order(['id' => 'asc'])
            ->limit(10)
            ->select()
            ->toArray();
        if (empty($imgs)) {
            echo date('Y-m-d H:i:s'). ' img empty' . PHP_EOL;
            return;
        }
        $enginery_band = 'eliteform';
        foreach ($imgs as $img) {
            $file_path_img = $img['file_path'];
            $data = json_decode($img['data'], true);
            $res = $ActionLogic->ocrDataDo($data, $file_path_img, $enginery_band);
            if ($res['code']) {
                echo date('Y-m-d H:i:s'). ' ocr fail' . json_encode($res) . PHP_EOL;
            } else {
                $imgModel->modifyById($img['id'], ['ocr_status' => 1]);
                echo date('Y-m-d H:i:s'). ' ocr success' . $img['id'] . json_encode($res) . PHP_EOL;
            }
        }
        echo date('Y-m-d H:i:s'). ' ocr end count:' . count($imgs) . PHP_EOL;
    }
}
