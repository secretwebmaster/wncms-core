<?php

return [
    'title' => '文尼CMS安装向导',
    'next' => '下一步',
    'back' => '上一步',
    'finish' => '安装',

    'forms' => [
        'errorTitle' => '以下错误发生：',
    ],

    'welcome' => [
        'templateTitle' => '文尼CMS安装向导',
        'title'   => '文尼CMS安装向导',
        'message' => '简单且快速安装',
        'next'    => '开始安装',
    ],

    'requirements' => [
        'templateTitle' => '步骤 1 | 环境要求',
        'title' => '环境要求',
        'next'    => '检查权限',
    ],

    'permissions' => [
        'templateTitle' => '步骤 2 | 权限',
        'title' => '权限',
        'next' => '设定系统基本配置',
    ],

    'environment' => [
        'menu' => [
            'templateTitle' => '步骤 3 | 系统基本配置',
            'title' => '系统基本配置',
            'desc' => '请选择如何设定应用程式的 <code>.env</code> 文件。',
            'wizard-button' => '表单向导设定',
            'classic-button' => '经典文本编辑器',
        ],
        'wizard' => [
            'templateTitle' => '步骤 3 | 系统基本配置',
            'title' => '系统基本配置',
            'tabs' => [
                'environment' => '基本设定',
                'database' => '资料库',
                'application' => '快取',
            ],
            'form' => [
                'name_required' => '需要提供环境名称。',
                'app_name_label' => '主网站名称',
                'app_name_placeholder' => '',
                'app_environment_label' => '应用程式环境',
                'app_environment_label_local' => '本地',
                'app_environment_label_developement' => '开发',
                'app_environment_label_qa' => '测试',
                'app_environment_label_production' => '正式',
                'app_environment_label_other' => '其他',
                'app_environment_placeholder_other' => '输入您的环境...',
                'app_debug_label' => '应用程式除错',
                'app_debug_label_true' => '是',
                'app_debug_label_false' => '否',
                'app_log_level_label' => '应用程式记录层级',
                'app_log_level_label_debug' => '除错',
                'app_log_level_label_info' => '资讯',
                'app_log_level_label_notice' => '注意',
                'app_log_level_label_warning' => '警告',
                'app_log_level_label_error' => '错误',
                'app_log_level_label_critical' => '严重',
                'app_log_level_label_alert' => '警示',
                'app_log_level_label_emergency' => '紧急',
                'app_url_label' => '主域名',
                'app_url_placeholder' => '需以https://开头',
                'db_connection_failed' => '无法连接到资料库。',
                'db_connection_label' => '资料库连接',
                'db_connection_label_mysql' => 'MySQL',
                'db_connection_label_sqlite' => 'SQLite',
                'db_connection_label_pgsql' => 'PostgreSQL',
                'db_connection_label_sqlsrv' => 'SQL Server',
                'db_host_label' => '资料库主机',
                'db_host_placeholder' => '预设为127.0.0.1',
                'db_port_label' => '资料库端口',
                'db_port_placeholder' => '预设为3306',
                'db_name_label' => '资料库名称',
                'db_name_placeholder' => '',
                'db_username_label' => '资料库使用者名称',
                'db_username_placeholder' => '',
                'db_password_label' => '资料库密码',
                'db_password_placeholder' => '',

                'app_tabs' => [
                    'more_info' => '更多资讯',
                    'broadcasting_title' => '广播、快取、会话和伫列',
                    'broadcasting_label' => '广播驱动程式',
                    'broadcasting_placeholder' => '广播驱动程式',
                    'cache_label' => '快取驱动程式',
                    'cache_placeholder' => '预设为redis，没有Redis可以使用file',
                    'session_label' => '会话驱动程式',
                    'session_placeholder' => '预设为redis，没有Redis可以使用file',
                    'queue_label' => '伫列驱动程式',
                    'queue_placeholder' => '伫列驱动程式',
                    'redis_label' => 'Redis 驱动程式',
                    'redis_host' => 'Redis 主机',
                    'redis_host_host' => '预设为127.0.0.1',
                    'redis_password' => 'Redis 密码',
                    'redis_password_placeholder' => '预设为空',
                    'redis_port' => 'Redis 端口',
                    'redis_db' => 'Redis 数据库编辑 (0-15) ，预设为0',
                    'redis_port_placeholder' => '预设6379',

                    'mail_label' => '邮件',
                    'mail_driver_label' => '邮件驱动程式',
                    'mail_driver_placeholder' => '邮件驱动程式',
                    'mail_host_label' => '邮件主机',
                    'mail_host_placeholder' => '邮件主机',
                    'mail_port_label' => '邮件端口',
                    'mail_port_placeholder' => '邮件端口',
                    'mail_username_label' => '邮件使用者名称',
                    'mail_username_placeholder' => '邮件使用者名称',
                    'mail_password_label' => '邮件密码',
                    'mail_password_placeholder' => '邮件密码',
                    'mail_encryption_label' => '邮件加密',
                    'mail_encryption_placeholder' => '邮件加密',

                    'pusher_label' => 'Pusher',
                    'pusher_app_id_label' => 'Pusher 应用程式 ID',
                    'pusher_app_id_palceholder' => 'Pusher 应用程式 ID',
                    'pusher_app_key_label' => 'Pusher 应用程式金钥',
                    'pusher_app_key_palceholder' => 'Pusher 应用程式金钥',
                    'pusher_app_secret_label' => 'Pusher 应用程式密钥',
                    'pusher_app_secret_palceholder' => 'Pusher 应用程式密钥',
                ],

                'admin' => [
                    'username' => '管理员使用者名称',
                    'email' => '管理员电子邮件',
                    'password' => '管理员密码',
                ],
                'buttons' => [
                    'setup_database' => '设定资料库',
                    'setup_application' => '设定快取',
                    'install' => '安装',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => '步骤 3 | 系统基本配置',
            'title' => '经典环境编辑器',
            'save' => '储存 .env',
            'back' => '使用表单向导',
            'install' => '储存并安装',
        ],
        'success' => '您的 .env 文件设定已保存。',
        'errors' => '无法保存 .env 文件，请手动创建。',
    ],

    'install' => '安装',

    'installed' => [
        'success_log_message' => '文尼CMS安装程式成功安装于 ',
    ],

    'final' => [
        'title' => '安装完成',
        'templateTitle' => '安装完成',
        'finished' => '成功安装文尼CMS',
        'migration' => '迁移和种子资料控制台输出：',
        'console' => '应用程式控制台输出：',
        'log' => '安装纪录项目：',
        'env' => '最终 .env 档案：',
        'exit' => '点击这裡退出',
    ],

    'updater' => [
        'title' => '文尼CMS更新程序',

        'welcome' => [
            'title'   => '欢迎使用更新程序',
            'message' => '欢迎使用更新向导。',
        ],

        'overview' => [
            'title'   => '概览',
            'message' => '有 1 个更新。|有 :number 个更新。',
            'install_updates' => '安装更新',
        ],

        'final' => [
            'title' => '完成',
            'finished' => '应用程序的数据库已成功更新。',
            'exit' => '点击这里退出',
        ],

        'log' => [
            'success_message' => '文尼CMS已成功更新于 ',
        ],
    ],

    'please_enable_these_php_functions' => '请确定一下PHP函数为启用'
];