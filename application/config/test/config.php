<?php
$config['business_name'] = '数据中心测试环境';
$config['business_identifying'] = 'mdc';
$config['app_debug'] = false;
$config['app_trace'] = false;
$config['log'] = [
    'type' => 'FormatFile',
    'path' => '/work_cpt/log/datacenter/',
];
$config['domain'] = 'https://mdctest.kxunpt.cn/';
$config['business'] = [
    'ahkx' => [
        'business' => 'ahkx',
        'name' => '安徽体科所测试环境',
        'mysql_database' => 'tnxl_dev',
        'mysql_prefix' => 'tn_',
        'tieren' => [
            'clientId' => 'NjYzMDEwNDE1NDYwNzgyMDgw',
            'clientSecret' => 'NjYzMDEwNDE1NDcxMDA1Njk2',
            'url' => 'https://iot.ironmanapi.com:8017',
        ],
        'domain' => 'https://tnxltest.kxunpt.cn',
        'baidu_face_db_group_id' => 'tnxl_test',//百度在线人脸库用户组
        //百度人脸注册
        'baidu_apikey' => 'e2rVCIF7bBxgcYr7x7q8Arx2',
        'baidu_secretkey' => 'mWjH7YGLV4OtRrj2MLXpwxQwYshGa3x1',
        'mysql_hostname' => '59.110.32.205',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'Cpt2018@)!(',
        'equipment_system_numbers' => [//相当于写死每个设备的系统编号，设备的系统编号在上线前就固定下来了
            'Inbody770' => 124,
            'MONARK894e' => [126],
            'Cortex' => 125,
        ],
        //创建人员-默认开发环境
        'createStaff' =>[
            'station_uuid' => '0764F652-E07B-EFDB-D8EC-CE6F152A9A3D',//其他
            'department_uuid' => 'A6063B6B-DA55-0CB4-9D10-1ED2C4FC21DC',//其他部门
        ],
    ],
    'tnxl' => [
        'business' => 'tnxl',
        'name' => '体能系统测试环境',
        'mysql_database' => 'tnxl_test',
        'mysql_prefix' => 'tn_',
        'sqlsrv_database' => 'tn_anhui_lis',
    ],
    'sxkx' => [
        'business' => 'sxkx',
        'name' => '山西体科所测试环境',
        'mysql_database' => 'ydy3_sxtks',
    ],
    'tjkx' => [
        'business' => 'tjkx',
        'name' => '天津体科所测试环境',
        'mysql_database' => 'ydy3_tj',
        'sqlsrv_database' => 'lis_test',
        //创建人员
        'createStaff' =>[
            'station_uuid' => '45451211-8A1E-C983-01AF-8348B59BECER',//运动员
            'department_uuid' => 'EA29A027-C086-81E2-4217-8E6F8BB1C348',//其他部门
        ],
    ],
    'jxxxyy' => [
        'business' => 'jxxxyy',
        'name' => '江西信息应用职业技术学院测试环境',
        'sqlsrv_hostname' => '39.105.177.222',
        'sqlsrv_database' => 'tjzx_db_new_72_jxxxyy',
        'sqlsrv_username' => 'sa',
        'sqlsrv_password' => 'TjzxAdmin2023',
        'store_id' => 72,
        'device_id_list' => [
            '233b7ea7e84703c209a95a76e86a3ecf3'
        ]
    ],
    'shjxkx' => [
        'business' => 'shjxkx',
        'name' => '上海体科所科训测试环境',
        'mysql_database' => 'shjxkx',
        'mysql_prefix' => '',
        'mysql_hostname' => '59.110.32.205',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'Cpt2018@)!(',
        'sqlsrv_database' => 'lis_shjxkx',
        'admin_user_info' => [
            'uuid' => '4A544AFA-7911-9C6A-124D-EFBF1684AAD0',
            'name' => '超管',
        ],
        //创建人员-默认开发环境
        'createStaff' =>[
            'station_uuid' => '4F3544DE-AD89-D302-AE3A-A226BD5456E0',//其他
            'department_uuid' => 'EDFF8B88-CC3D-8867-CE62-86327B55091C',//其他部门
            'polar_top_level_department_uuid' => '4D9B9B38-E35C-3A28-D42D-E5FE42F0A133',//polar顶级部门
            'athlete_station_uuid' => '45451211-8A1E-C983-01AF-8348B59BECER',//运动员uuid
        ],
        'equipment_system_numbers' => [//相当于写死每个设备的系统编号，设备的系统编号在上线前就固定下来了
            'CosmedK5' => 3,
        ],
        'equipment_data_filepath' => [
            'CosmedK5' => [
                'wait'=>'/workspace/shtksequipment/wait/k5', 
                'finish'=>'/workspace/shtksequipment/finish'
            ],
        ],
        'polar' => [
//            179199280@qq.com
//            'clinet_id' => '2fea7b58-180e-4871-8e2e-514d6e42525f',
//            'client_secret' => '9a83cac7-8ed4-46ef-a45e-579f8c98bd36',

//            13764125719@163.com 
            'clinet_id' => '7b3d6e13-adcc-4587-825b-0d4e356a21bc',
            'client_secret' => '78427c2f-454e-4fca-b233-fccb71457a6a',
            'redirect_uri' => 'https://mdctest.kxunpt.cn/shjxkx/polar/code',
        ],
        'wattbike' => [
            'accessKeyId' => 'LTAI5t5nibDkQQzLAnWt4dHp',//阿里云账号AccessKeyId
            'accessKeySecret' => 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf',//阿里云账号AccessKeySecret
            'instanceName' => 'cpt-table',//实例名称
            'tableName' => 'shtks_wattbike_dev',//table
        ],
        'concept2' => [
            'accessKeyId' => 'LTAI5t5nibDkQQzLAnWt4dHp',//阿里云账号AccessKeyId
            'accessKeySecret' => 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf',//阿里云账号AccessKeySecret
            'instanceName' => 'cpt-table',//实例名称
            'tableName' => 'shtks_concept2_dev',//table
//            'tableName' => 'concept2_online',//table
        ],
    ],
    'gdcs' => [
        'business' => 'gdcs',
        'name' => '广州城市职业技术学院测试环境',
        'sqlsrv_hostname' => '39.105.177.222',
        'sqlsrv_database' => 'tjzx_db_new_75_gdcsxy',
        'sqlsrv_username' => 'sa',
        'sqlsrv_password' => 'TjzxAdmin2023',
        'store_id' => 75,
    ],
    'gdtkskx' => [
        'business' => 'gdtkskx',
        'name' => '广东体科所测试环境',
        'mysql_database' => 'ydy3_gdtkstest',
        'mysql_prefix' => 'ydy_',
        'mysql_hostname' => '59.110.32.205',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'Cpt2018@)!(',
        'equipment_system_numbers' => [//相当于写死每个设备的系统编号，设备的系统编号在上线前就固定下来了
            'Inbody770' => 1,
            'CosmedK5' => 2,
            'OmegaWave' => 10,
        ],
        'equipment_data_filepath' => [
            'CosmedK5' => [
                'wait'=>'/workspace/gdtksequipment/wait/k5', 
                'finish'=>'/workspace/gdtksequipment/finish'
            ],
            'OmegaWave' => [
                'wait'=>'/workspace/gdtksequipment/wait/omegawave', 
                'finish'=>'/workspace/gdtksequipment/finish'
            ],
        ],
        'personnelIdDigit' => 6,//inbody人员编号位数
    ],
    'tatx' => [
        'business' => 'tatx',
        'name' => '山东泰安体校测试环境',
        'sqlsrv_hostname' => '39.105.177.222',
        'sqlsrv_database' => 'tjzx_db_new_80_tatx_student',
        'sqlsrv_username' => 'sa',
        'sqlsrv_password' => 'TjzxAdmin2023',
        'store_id' => 80,
        'device_id_list' => [
            '233b7ea7e84703c209a95a76e86a3ecf3'
        ]
    ],
    'tnxljstks' => [
        'business' => 'tnxljstks',
        'name' => '江苏体科所能量代谢系统测试环境',
        'domain' => 'https://jstktest.kxunpt.cn',
        'mysql_hostname' => '59.110.32.205',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'Cpt2018@)!(',
        'mysql_database' => 'tnxl_jstks',
        'mysql_prefix' => 'tn_',
        'equipment_system_numbers' => [//相当于写死每个设备的系统编号，设备的系统编号在上线前就固定下来了
            'Inbody770' => 1,
            'BreezingPro' => 2,
        ],
        //创建人员-默认开发环境
        'createStaff' =>[
            'station_uuid' => '0764F652-E07B-EFDB-D8EC-CE6F152A9A3D',//其他
            'department_uuid' => '210C4BFB-D7B0-6337-D353-BE97895140EC',//其他部门
        ],
    ],
];
return $config;