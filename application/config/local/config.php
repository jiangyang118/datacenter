<?php
$config['business_name'] = '数据中心生产环境';
$config['business_identifying'] = 'mdc';
$config['app_debug'] = false;
$config['app_trace'] = false;
$config['log'] = [
    'type' => 'FormatFile',
    'path' => '/workspace/wwwroot/log/datacenter/',
];
$config['domain'] = 'https://mdc.kxunpt.cn/';
$config['business'] = [
    'ahkx' => [
        'business' => 'ahkx',
        'name' => '安徽体科所生产环境',
        'mysql_hostname' => '39.106.45.95',
        'mysql_database' => 'tnxl_dps_ahtks',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'cpt2018@)!(',
        'mysql_prefix' => 'tn_',
        'tieren' => [
            'clientId' => 'NjYzMDEwNDE1NDYwNzgyMDgw',
            'clientSecret' => 'NjYzMDEwNDE1NDcxMDA1Njk2',
            'url' => 'https://iot.ironmanapi.com:8017',
        ],
        'domain' => 'https://ahtks.kxunpt.cn',
        'baidu_face_db_group_id' => 'tnxlantks',//百度在线人脸库用户组
        //百度人脸注册
        'baidu_apikey' => 'e2rVCIF7bBxgcYr7x7q8Arx2',
        'baidu_secretkey' => 'mWjH7YGLV4OtRrj2MLXpwxQwYshGa3x1',
        'equipment_system_numbers' => [//相当于写死每个设备的系统编号，设备的系统编号在上线前就固定下来了
            'Inbody770' => 124,
            'MONARK894e' => [126],
            'Cortex' => 125,
        ],
        //创建人员
        'createStaff' =>[
            'station_uuid' => '0764F652-E07B-EFDB-D8EC-CE6F152A9A3D',//其他
            'department_uuid' => '210C4BFB-D7B0-6337-D353-BE97895140EC',//其他部门
        ],
    ],
    'sxkx' => [
        'business' => 'sxkx',
        'name' => '山西体科所生产环境',
        'mysql_database' => 'sxkx',
        'mysql_prefix' => 'ydy_',
        'mysql_hostname' => 'rds3nh30726o5tko5b9g502.mysql.rds.aliyuncs.com',
        'mysql_username' => 'rooot',
        'mysql_password' => 'cpt2018@)!(',
        'admin_user_info' => [
            'uuid' => '4A544AFA-7911-9C6A-124D-EFBF1684AAD0',
            'name' => '超管',
        ],
        'versa_climber' => [//攀爬机
            'accessKeyId' => 'LTAI5t5nibDkQQzLAnWt4dHp',//阿里云账号AccessKeyId
            'accessKeySecret' => 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf',//阿里云账号AccessKeySecret
            'instanceName' => 'tech-skillrow',//实例名称
            'tableName' => 'versa_climber_table',//table
        ],
        'tech-skillrow' => [//泰诺健划船机
            'accessKeyId' => 'LTAI5t5nibDkQQzLAnWt4dHp',//阿里云账号AccessKeyId
            'accessKeySecret' => 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf',//阿里云账号AccessKeySecret
            'instanceName' => 'tech-skillrow',//实例名称
            'tableName' => 'tech_skillrow_table',//table
        ],
        'equipment_system_numbers' => [//相当于写死每个设备的系统编号，设备的系统编号在上线前就固定下来了
            'MONARK894e' => [27,28],
        ],
        //创建人员-默认开发环境
        'createStaff' =>[
            'station_uuid' => '4F3544DE-AD89-D302-AE3A-A226BD5456E0',//其他
            'department_uuid' => 'EDFF8B88-CC3D-8867-CE62-86327B55091C',//其他部门
        ],
        'wattbike' => [
            'accessKeyId' => 'LTAI5t5nibDkQQzLAnWt4dHp',//阿里云账号AccessKeyId
            'accessKeySecret' => 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf',//阿里云账号AccessKeySecret
            'instanceName' => 'new-wattbike-sx',//实例名称
            'tableName' => 'pro_table',//table
        ],
    ],
    'gdkx' => [
        'business' => 'gdkx',
        'name' => '广东体科所生产环境',
        'mysql_database' => 'gdkx',
        'admin_user_info' => [
            'uuid' => '4A544AFA-7911-9C6A-124D-EFBF1684AAD0',
            'name' => '超管',
        ],
        'createStaff' =>[
            'station_uuid' => '4F3544DE-AD89-D302-AE3A-A226BD5456E0',//其他
            'department_uuid' => 'EDFF8B88-CC3D-8867-CE62-86327B55091C',//其他部门
        ],
    ],
    'tjkx' => [
        'business' => 'tjkx',
        'name' => '天津体科所生产环境',
        'mysql_database' => 'tjkx',
        'sqlsrv_database' => 'lis_online_tjkx',
        //创建人员
        'createStaff' =>[
            'station_uuid' => '45451211-8A1E-C983-01AF-8348B59BECER',//运动员
            'department_uuid' => 'B4BBABDA-E076-3E68-5FD0-2AF5B17DF597',//其他部门
        ],
    ],
    'jxxxyy' => [
        'business' => 'jxxxyy',
        'name' => '江西信息应用职业技术学院生产环境',
        'sqlsrv_hostname' => '39.105.177.222',
        'sqlsrv_database' => 'tjzx_db_new_72_jxxxyy',
        'sqlsrv_username' => 'sa',
        'sqlsrv_password' => 'TjzxAdmin2023',
        'store_id' => 72,
        'device_id_list' => [
            '2ad679eef93003acf87f4e9127cf7e695',//身高体重测试仪
            '2e11b23192d903f33b1013a0f467dd9a5',//肺活量测试仪
            '2a63ef299d4333052a63dcf589c87d98d',//坐位体前屈测试仪
            '267613e3433393235ba74883edfcb9d2f',//仰卧起坐测试仪
            '263cd90b4faec3c159c83fbee878cadae',//俯卧撑测试仪
            '28f41e81530973ef4956bb4a011103828',//纵跳测试仪
            '263c57e33a074378c84db078ca7190a00',//反应时测试仪
            '238d9cd23307c31b6864da6f1e5c10cb5',//闭眼单脚独立测试仪
            '25807cd8afd4e3fae81fa6ed9582e1db4',//台阶测试仪
        ]
    ],
    'shjxkx' => [
        'business' => 'shjxkx',
        'name' => '上海体科所科训生产环境',
        'mysql_database' => 'shjxkxpro',
        'mysql_prefix' => '',
        'mysql_hostname' => 'rds3nh30726o5tko5b9g502.mysql.rds.aliyuncs.com',
        'mysql_username' => 'rooot',
        'mysql_password' => 'cpt2018@)!(',
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
                'wait'=>'/workspace/wwwroot/equipment_data_file/wait/shtkskx/k5', 
                'finish'=>'/workspace/wwwroot/equipment_data_file/finish'
            ],
        ],
        'polar' => [
//            179199280@qq.com
//            'clinet_id' => '2fea7b58-180e-4871-8e2e-514d6e42525f',
//            'client_secret' => '9a83cac7-8ed4-46ef-a45e-579f8c98bd36',

//            13764125719@163.com 
            'clinet_id' => '7b3d6e13-adcc-4587-825b-0d4e356a21bc',
            'client_secret' => '78427c2f-454e-4fca-b233-fccb71457a6a',
            'redirect_uri' => 'https://mdc.kxunpt.cn/shjxkx/polar/code',
        ],
        'wattbike' => [
            'accessKeyId' => 'LTAI5t5nibDkQQzLAnWt4dHp',//阿里云账号AccessKeyId
            'accessKeySecret' => 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf',//阿里云账号AccessKeySecret
            'instanceName' => 'cpt-table',//实例名称
//            'tableName' => 'shtks_wattbike_dev',//table，需要切换到线上table
            'tableName' => 'shtks_wattbike_online',//table，需要切换到线上table
        ],
        'concept2' => [
            'accessKeyId' => 'LTAI5t5nibDkQQzLAnWt4dHp',//阿里云账号AccessKeyId
            'accessKeySecret' => 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf',//阿里云账号AccessKeySecret
            'instanceName' => 'cpt-table',//实例名称
//            'tableName' => 'shtks_concept2_dev',//table，需要切换到线上table
            'tableName' => 'shtks_concept2_online',//table
        ],
    ],
    'qhdkx' => [
        'business' => 'qhdkx',
        'name' => '秦皇岛体科所生产环境',
        'mysql_database' => 'ydy_qhd',
        'mysql_prefix' => 'ydy_',
        'sqlsrv_database' => 'lis_test',//暂时用天津库测试后修改为：lis_online_qhdkx
        'mysql_hostname' => '39.106.45.95',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'cpt2018@)!(',
        'equipment_system_numbers' => [//相当于写死每个设备的系统编号，设备的系统编号在上线前就固定下来了
            'Inbody770' => 6, //待修改为系统设备id
        ],
        'tech-skillrow' => [//泰诺健划船机
            'accessKeyId' => 'LTAI5t5nibDkQQzLAnWt4dHp',//阿里云账号AccessKeyId
            'accessKeySecret' => 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf',//阿里云账号AccessKeySecret
            'instanceName' => 'tech-skillrow',//实例名称
            'tableName' => 'qhd_tech_skillrow_table',//table
        ],
        //创建人员-默认开发环境
        'createStaff' =>[
            'station_uuid' => '45451211-8A1E-C983-01AF-8348B59BECER',//其他
            'department_uuid' => '6B6F7E1F-D8E6-1502-78F9-65920638D255',//其他部门
        ]
    ],
    'tnxl' => [
        'business' => 'tnxl',
        'name' => '体能大比武生产环境',
        'mysql_hostname' => 'rm-2zewav6n1l9i08n6t.mysql.rds.aliyuncs.com',
        'mysql_database' => 'tnxl_tndbw_yanlian',//演练数据库
//        'mysql_database' => 'tnxl_tndbw',//正式数据库
        'mysql_prefix' => 'tn_',
        'sqlsrv_database' => '',
    ],
    'gdcs' => [
        'business' => 'gdcs',
        'name' => '广州城市职业技术学院生产环境',
        'sqlsrv_hostname' => '39.105.177.222',
        'sqlsrv_database' => 'tjzx_db_new_75_gdcsxy',
        'sqlsrv_username' => 'sa',
        'sqlsrv_password' => 'TjzxAdmin2023',
        'store_id' => 75,
    ],
    'gdtkskx' => [
        'business' => 'gdtkskx',
        'name' => '广东体科所',
        'mysql_database' => 'ydy3_gdtks',
        'mysql_prefix' => 'ydy_',
        'mysql_hostname' => '39.106.45.95',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'cpt2018@)!(',
        'equipment_system_numbers' => [//相当于写死每个设备的系统编号，设备的系统编号在上线前就固定下来了
            'Inbody770' => 1,
            'CosmedK5' => 2,
            'OmegaWave' => 10,
        ],
        'equipment_data_filepath' => [
            'CosmedK5' => [
                'wait'=>'/workspace/wwwroot/equipment_data_file/wait/gdtkskx/k5',
                'finish'=>'/workspace/wwwroot/equipment_data_file/finish'
            ],
            'OmegaWave' => [
                'wait'=>'/workspace/wwwroot/equipment_data_file/wait/gdtkskx/omegawave',
                'finish'=>'/workspace/wwwroot/equipment_data_file/finish'
            ],
        ],
        'personnelIdDigit' => 6,//inbody人员编号位数
    ],
    'gdtkskxdc' => [
        'business' => 'gdtkskxdc',
        'name' => '广东体科所数据中心',
        'mysql_database' => 'ydy3_gdtksdc',
        'mysql_prefix' => 'ydy_',
        'mysql_hostname' => '39.106.45.95',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'cpt2018@)!(',
        'equipment_system_numbers' => [//相当于写死每个设备的系统编号，设备的系统编号在上线前就固定下来了
            'Inbody770' => 1,
            'CosmedK5' => 2,
            'OmegaWave' => 10,
        ],
        'equipment_data_filepath' => [
            'CosmedK5' => [
                'wait'=>'/workspace/wwwroot/equipment_data_file/wait/gdtkskxdc/k5',
                'finish'=>'/workspace/wwwroot/equipment_data_file/finish'
            ],
            'OmegaWave' => [
                'wait'=>'/workspace/wwwroot/equipment_data_file/wait/gdtkskxdc/omegawave',
                'finish'=>'/workspace/wwwroot/equipment_data_file/finish'
            ],
        ],
        'personnelIdDigit' => 6,//inbody人员编号位数
    ],
    'gztks' => [
        'business' => 'gztks',
        'name' => '贵州体科所生产环境',
        'mysql_hostname' => '39.106.45.95',
        'mysql_database' => 'ydy3_gztyj',
        'mysql_prefix' => 'ydy_',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'cpt2018@)!(',
        'sqlsrv_database' => 'lis_online_gztks',
        'sqlsrv_username' => 'sa',
        'sqlsrv_password' => 'TjzxAdmin2023',
        //创建人员
        'createStaff' =>[
            'station_uuid' => '45451211-8A1E-C983-01AF-8348B59BECER',//运动员
            'department_uuid' => 'B4BBABDA-E076-3E68-5FD0-2AF5B17DF597',//其他部门
        ],
    ],
    'tatx' => [
        'business' => 'tatx',
        'name' => '山东泰安体校生产环境',
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
        'name' => '江苏体科所能量代谢系统生产环境',
        'domain' => 'https://jstks.kxunpt.cn',
        'mysql_hostname' => '39.106.45.95',
        'mysql_username' => 'wwwweb',
        'mysql_password' => 'cpt2018@)!(',
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