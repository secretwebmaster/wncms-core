<?php

return [
    'title' => '文尼CMS安裝精靈',
    'next' => '下一步',
    'back' => '上一步',
    'finish' => '安裝',

    'forms' => [
        'errorTitle' => '以下錯誤發生：',
    ],

    'welcome' => [
        'templateTitle' => '文尼CMS安裝向導',
        'title'   => '文尼CMS安裝向導',
        'message' => '簡單且快速安裝',
        'next'    => '開始安裝',
    ],

    'requirements' => [
        'templateTitle' => '步驟 1 | 環境要求',
        'title' => '環境要求',
        'next'    => '檢查權限',
    ],

    'permissions' => [
        'templateTitle' => '步驟 2 | 權限',
        'title' => '權限',
        'next' => '設定系統基本配置',
    ],

    'environment' => [
        'menu' => [
            'templateTitle' => '步驟 3 | 系統基本配置',
            'title' => '系統基本配置',
            'desc' => '請選擇如何設定應用程式的 <code>.env</code> 文件。',
            'wizard-button' => '表單向導設定',
            'classic-button' => '經典文本編輯器',
        ],
        'wizard' => [
            'templateTitle' => '步驟 3 | 系統基本配置',
            'title' => '系統基本配置',
            'tabs' => [
                'environment' => '基本設定',
                'database' => '資料庫',
                'application' => '快取',
            ],
            'form' => [
                'name_required' => '需要提供環境名稱。',
                'app_name_label' => '網站ID (只可使用英文和數字，用於單一服務器區分多個CMS)',
                'app_name_placeholder' => '',
                'app_environment_label' => '應用程式環境',
                'app_environment_label_local' => '本地',
                'app_environment_label_developement' => '開發',
                'app_environment_label_qa' => '測試',
                'app_environment_label_production' => '正式',
                'app_environment_label_other' => '其他',
                'app_environment_placeholder_other' => '輸入您的環境...',
                'app_debug_label' => '應用程式除錯',
                'app_debug_label_true' => '是',
                'app_debug_label_false' => '否',
                'app_log_level_label' => '應用程式記錄層級',
                'app_log_level_label_debug' => '除錯',
                'app_log_level_label_info' => '資訊',
                'app_log_level_label_notice' => '注意',
                'app_log_level_label_warning' => '警告',
                'app_log_level_label_error' => '錯誤',
                'app_log_level_label_critical' => '嚴重',
                'app_log_level_label_alert' => '警示',
                'app_log_level_label_emergency' => '緊急',
                'app_url_label' => '主域名 (帶 https://)',
                'app_url_placeholder' => '需以https://開頭',
                'db_connection_failed' => '無法連接到資料庫。',
                'db_connection_label' => '資料庫連接',
                'db_connection_label_mysql' => 'MySQL',
                'db_connection_label_sqlite' => 'SQLite',
                'db_connection_label_pgsql' => 'PostgreSQL',
                'db_connection_label_sqlsrv' => 'SQL Server',
                'db_host_label' => '資料庫主機',
                'db_host_placeholder' => '預設為127.0.0.1',
                'db_port_label' => '資料庫端口',
                'db_port_placeholder' => '預設為3306',
                'db_name_label' => '資料庫名稱',
                'db_name_placeholder' => '',
                'db_username_label' => '資料庫使用者名稱',
                'db_username_placeholder' => '',
                'db_password_label' => '資料庫密碼',
                'db_password_placeholder' => '',

                'app_tabs' => [
                    'more_info' => '更多資訊',
                    'broadcasting_title' => '廣播、快取、會話和佇列',
                    'broadcasting_label' => '廣播驅動程式',
                    'broadcasting_placeholder' => '廣播驅動程式',
                    'cache_label' => '快取驅動程式',
                    'cache_placeholder' => '預設為redis，沒有Redis可以使用file',
                    'session_label' => '會話驅動程式',
                    'session_placeholder' => '預設為redis，沒有Redis可以使用file',
                    'queue_label' => '佇列驅動程式',
                    'queue_placeholder' => '佇列驅動程式',
                    'redis_label' => 'Redis 驅動程式',
                    'redis_host' => 'Redis 主機',
                    'redis_host_host' => '預設為127.0.0.1',
                    'redis_password' => 'Redis 密碼',
                    'redis_password_placeholder' => '預設為空',
                    'redis_port' => 'Redis 端口',
                    'redis_db' => 'Redis 數據庫編輯 (0-15) ，預設為0',
                    'redis_port_placeholder' => '預設6379',

                    'mail_label' => '郵件',
                    'mail_driver_label' => '郵件驅動程式',
                    'mail_driver_placeholder' => '郵件驅動程式',
                    'mail_host_label' => '郵件主機',
                    'mail_host_placeholder' => '郵件主機',
                    'mail_port_label' => '郵件端口',
                    'mail_port_placeholder' => '郵件端口',
                    'mail_username_label' => '郵件使用者名稱',
                    'mail_username_placeholder' => '郵件使用者名稱',
                    'mail_password_label' => '郵件密碼',
                    'mail_password_placeholder' => '郵件密碼',
                    'mail_encryption_label' => '郵件加密',
                    'mail_encryption_placeholder' => '郵件加密',

                    'pusher_label' => 'Pusher',
                    'pusher_app_id_label' => 'Pusher 應用程式 ID',
                    'pusher_app_id_palceholder' => 'Pusher 應用程式 ID',
                    'pusher_app_key_label' => 'Pusher 應用程式金鑰',
                    'pusher_app_key_palceholder' => 'Pusher 應用程式金鑰',
                    'pusher_app_secret_label' => 'Pusher 應用程式密鑰',
                    'pusher_app_secret_palceholder' => 'Pusher 應用程式密鑰',
                ],

                'admin' => [
                    'username' => '管理員使用者名稱',
                    'email' => '管理員電子郵件',
                    'password' => '管理員密碼',
                ],
                'buttons' => [
                    'setup_database' => '設定資料庫',
                    'setup_application' => '設定快取',
                    'install' => '安裝',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => '步驟 3 | 系統基本配置',
            'title' => '經典環境編輯器',
            'save' => '儲存 .env',
            'back' => '使用表單向導',
            'install' => '儲存並安裝',
        ],
        'success' => '您的 .env 文件設定已保存。',
        'errors' => '無法保存 .env 文件，請手動創建。',
    ],

    'install' => '安裝',

    'installed' => [
        'success_log_message' => '文尼CMS安裝程式成功安裝於 ',
    ],

    'final' => [
        'title' => '安裝完成',
        'templateTitle' => '安裝完成',
        'finished' => '成功安裝文尼CMS',
        'migration' => '遷移和種子資料控制台輸出：',
        'console' => '應用程式控制台輸出：',
        'log' => '安裝紀錄項目：',
        'env' => '最終 .env 檔案：',
        'exit' => '點擊這裡退出',
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

    'please_enable_these_php_functions' => '請確定以下PHP函數為啟用'
];