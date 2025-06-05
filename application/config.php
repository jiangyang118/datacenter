<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

$config = [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 应用调试模式 
    'app_debug'              => false,
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展函数文件
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => '',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'htm',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否开启路由解析缓存
    'route_check_cache'      => false,
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => false,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,
    // 全局请求缓存排除规则
    'request_cache_except'   => [],

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写
        'auto_rule'    => 1,
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str'       => [],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => true,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => function($e) {
        return json([
            'code' => '2',
            'message' => $e->getMessage(),
            'data' => [],
        ]);
    },

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => '',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],
    // 步态分析仪配置文件
    'force' => [
        'result_config' => 1000, // 结果
        'process_config' => 5000, // 过程
        'equipment_id' => 61,
        'business' => 'business_sx'
    ],
    'tablestore' => [
        'accessKeyId' => 'LTAI5t5nibDkQQzLAnWt4dHp',//阿里云账号AccessKeyId
        'accessKeySecret' => 'UhIFA5thOTDEIcYV7EZpDgjRyrRALf',//阿里云账号AccessKeySecret
        'instanceName' => 'wattbike-sx',//实例名称
        'tableName' => 'test_table',//table
    ],
    'relation_type' => [
        'CLNTKS' => 3,//西门子尿十项分析仪Clinitek Status
        'CNTR_C' => 4,//西门子全自动化学发光免疫分析仪ADVIA® Centaur CP
        'BS420' => 2,//迈瑞全自动生化分析仪BS-380
        'BC5300' => 1,//迈瑞全自动血液分析仪BC-5385CRP
        'NX500i' => 10,//富士全自动干式生化分析仪NX500i
        'ADV212' => 15,//西门子血液分析仪ADVIA2021i
        'access2' => 16,//贝克曼access2
        'CPT' => 49,//希森美康血细胞分析仪XS-1000i（起原名与设备本身Lis软件冲突）
        'DP8088' => 50,//东塘DoTopP8088
    ],
    'xmdh_check' => [//检测指标名称替换
        'EO#' => 'EOS',//BC5300
        'EO%' => 'EOS%',//BC5300
        'PLCC' => 'P-LCC',//BC5300
        'PLCR' => 'P-LCR',//BC5300
        'TSTII' => 'TESTO',//CNTR_C
        'CKMB-P' => 'CKMB',//NX500i
        'CPK-P' => 'CK',//NX500i
        'BUN-P' => 'BUN',//NX500i
        '#L-PLT' => 'LPLT',//ADVIA2021i
        '910' => 'RET%',//ADVIA2021i
        '911' => 'RET#',//ADVIA2021i
        '912' => 'CHr',//ADVIA2021i
        'BASO#' => 'BASO',//BC5300
        'LYM#' => 'LYM',//BC5300
        'MONO#' => 'MONO',//BC5300
        'NEUT#' => 'NEU',//BC5300
        'NEUT%' => 'NEU%',//BC5300
        'RDW-CV' => 'RDW',//BC5300
    ],
    'lingkang_check' => [//领康测试项--江西
        'E016' => '',//身高 体重 175 58.6 【中间有个空格，都不带单位】
        'E024' => 'response_time',//选择反应时
        'E022' => 'stand_one_leg',//闭眼单脚站
        'E009' => 'sit_reach',//坐卧体前屈
        'E008' => 'grip_strength',//握力
        'E023' => 'vertical_jump',//纵跳
        'E012' => 'pushup',//仰卧起坐
        'E007' => 'vital_capacity',//肺活量
        'E026' => 'pushup',//俯卧撑
        'E053' => '',//台阶 todo
        'E800' => '',//3000米跑
    ]
];
//根据环境变量加载配置文件
//商户标识
$business = '';
if (defined(BUSINESS) && BUSINESS != 'BUSINESS_VALUE') {
    $business = '/'. BUSINESS;
}
$conf_extend = require APP_PATH . 'config/'. TP_ENV . $business . '/config.php';
//加载其他扩展
//redis...
return array_merge($config, $conf_extend);