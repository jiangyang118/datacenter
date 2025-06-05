<?php
//环境变量 local/dev/test/prod：本地/开发/测试/生产
define('TP_ENV', isset($_SERVER['TP_ENV']) ? $_SERVER['TP_ENV'] : 'local');
//商户唯一标识 test
define('BUSINESS', 'BUSINESS_VALUE');
