<?php

return [
    'title' => '文尼CMSインストーラー',
    'next' => '次へ',
    'back' => '戻る',
    'finish' => 'インストール',

    'forms' => [
        'errorTitle' => '以下のエラーが発生しました：',
    ],

    'welcome' => [
        'templateTitle' => '文尼CMSインストーラー',
        'title'   => '文尼CMSインストーラー',
        'message' => '簡単で迅速なインストール',
        'next'    => 'インストールを開始',
    ],

    'requirements' => [
        'templateTitle' => 'ステップ 1 | 環境要件',
        'title' => '環境要件',
        'next'    => '権限を確認',
    ],

    'permissions' => [
        'templateTitle' => 'ステップ 2 | 権限',
        'title' => '権限',
        'next' => 'システム基本設定',
    ],

    'environment' => [
        'menu' => [
            'templateTitle' => 'ステップ 3 | システム基本設定',
            'title' => 'システム基本設定',
            'desc' => '<code>.env</code> ファイルの設定方法を選択してください。',
            'wizard-button' => 'フォームウィザード設定',
            'classic-button' => 'クラシックテキストエディタ',
        ],
        'wizard' => [
            'templateTitle' => 'ステップ 3 | システム基本設定',
            'title' => 'システム基本設定',
            'tabs' => [
                'environment' => '基本設定',
                'database' => 'データベース',
                'application' => 'キャッシュ',
            ],
            'form' => [
                'name_required' => '環境名を提供する必要があります。',
                'app_name_label' => 'サイトID (英数字のみ、単一サーバーで複数のCMSを区別するため)',
                'app_name_placeholder' => '',
                'app_environment_label' => 'アプリケーション環境',
                'app_environment_label_local' => 'ローカル',
                'app_environment_label_developement' => '開発',
                'app_environment_label_qa' => 'テスト',
                'app_environment_label_production' => '本番',
                'app_environment_label_other' => 'その他',
                'app_environment_placeholder_other' => '環境を入力...',
                'app_debug_label' => 'アプリケーションデバッグ',
                'app_debug_label_true' => 'はい',
                'app_debug_label_false' => 'いいえ',
                'app_log_level_label' => 'アプリケーションログレベル',
                'app_log_level_label_debug' => 'デバッグ',
                'app_log_level_label_info' => '情報',
                'app_log_level_label_notice' => '通知',
                'app_log_level_label_warning' => '警告',
                'app_log_level_label_error' => 'エラー',
                'app_log_level_label_critical' => '重大',
                'app_log_level_label_alert' => '警告',
                'app_log_level_label_emergency' => '緊急',
                'app_url_label' => 'メインドメイン (https://付き)',
                'app_url_placeholder' => 'https://で始まる必要があります',
                'db_connection_failed' => 'データベースに接続できません。',
                'db_connection_label' => 'データベース接続',
                'db_connection_label_mysql' => 'MySQL',
                'db_connection_label_sqlite' => 'SQLite',
                'db_connection_label_pgsql' => 'PostgreSQL',
                'db_connection_label_sqlsrv' => 'SQL Server',
                'db_host_label' => 'データベースホスト',
                'db_host_placeholder' => 'デフォルトは127.0.0.1',
                'db_port_label' => 'データベースポート',
                'db_port_placeholder' => 'デフォルトは3306',
                'db_name_label' => 'データベース名',
                'db_name_placeholder' => '',
                'db_username_label' => 'データベースユーザー名',
                'db_username_placeholder' => '',
                'db_password_label' => 'データベースパスワード',
                'db_password_placeholder' => '',

                'app_tabs' => [
                    'more_info' => '詳細情報',
                    'broadcasting_title' => 'ブロードキャスト、キャッシュ、セッション、キュー',
                    'broadcasting_label' => 'ブロードキャストドライバ',
                    'broadcasting_placeholder' => 'ブロードキャストドライバ',
                    'cache_label' => 'キャッシュドライバ',
                    'cache_placeholder' => 'デフォルトはredis、Redisがない場合はfileを使用',
                    'session_label' => 'セッションドライバ',
                    'session_placeholder' => 'デフォルトはredis、Redisがない場合はfileを使用',
                    'queue_label' => 'キュードライバ',
                    'queue_placeholder' => 'キュードライバ',
                    'redis_label' => 'Redis ドライバ',
                    'redis_host' => 'Redis ホスト',
                    'redis_host_host' => 'デフォルトは127.0.0.1',
                    'redis_password' => 'Redis パスワード',
                    'redis_password_placeholder' => 'デフォルトは空',
                    'redis_port' => 'Redis ポート',
                    'redis_db' => 'Redis データベース番号 (0-15)、デフォルトは0',
                    'redis_port_placeholder' => 'デフォルト6379',

                    'mail_label' => 'メール',
                    'mail_driver_label' => 'メールドライバ',
                    'mail_driver_placeholder' => 'メールドライバ',
                    'mail_host_label' => 'メールホスト',
                    'mail_host_placeholder' => 'メールホスト',
                    'mail_port_label' => 'メールポート',
                    'mail_port_placeholder' => 'メールポート',
                    'mail_username_label' => 'メールユーザー名',
                    'mail_username_placeholder' => 'メールユーザー名',
                    'mail_password_label' => 'メールパスワード',
                    'mail_password_placeholder' => 'メールパスワード',
                    'mail_encryption_label' => 'メール暗号化',
                    'mail_encryption_placeholder' => 'メール暗号化',

                    'pusher_label' => 'Pusher',
                    'pusher_app_id_label' => 'Pusher アプリケーションID',
                    'pusher_app_id_palceholder' => 'Pusher アプリケーションID',
                    'pusher_app_key_label' => 'Pusher アプリケーションキー',
                    'pusher_app_key_palceholder' => 'Pusher アプリケーションキー',
                    'pusher_app_secret_label' => 'Pusher アプリケーションシークレット',
                    'pusher_app_secret_palceholder' => 'Pusher アプリケーションシークレット',
                ],

                'admin' => [
                    'username' => '管理者ユーザー名',
                    'email' => '管理者メール',
                    'password' => '管理者パスワード',
                ],
                'buttons' => [
                    'setup_database' => 'データベース設定',
                    'setup_application' => 'キャッシュ設定',
                    'install' => 'インストール',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'ステップ 3 | システム基本設定',
            'title' => 'クラシック環境エディタ',
            'save' => '.env を保存',
            'back' => 'フォームウィザードを使用',
            'install' => '保存してインストール',
        ],
        'success' => '.env ファイルの設定が保存されました。',
        'errors' => '.env ファイルを保存できませんでした。手動で作成してください。',
    ],

    'install' => 'インストール',

    'installed' => [
        'success_log_message' => '文尼CMSが成功裏にインストールされました ',
    ],

    'final' => [
        'title' => 'インストール完了',
        'templateTitle' => 'インストール完了',
        'finished' => '文尼CMSのインストールが成功しました',
        'migration' => '移行およびシードデータのコンソール出力：',
        'console' => 'アプリケーションコンソールの出力：',
        'log' => 'インストールログ項目：',
        'env' => '最終 .env ファイル：',
        'exit' => 'ここをクリックして退出',
    ],

    'updater' => [
        'title' => '文尼CMSアップデーター',

        'welcome' => [
            'title'   => 'アップデーターへようこそ',
            'message' => 'アップデートウィザードへようこそ。',
        ],

        'overview' => [
            'title'   => '概要',
            'message' => '1 件の更新があります。|:number 件の更新があります。',
            'install_updates' => '更新をインストール',
        ],

        'final' => [
            'title' => '完了',
            'finished' => 'アプリケーションのデータベースが正常に更新されました。',
            'exit' => 'ここをクリックして退出',
        ],

        'log' => [
            'success_message' => '文尼CMSは成功裏に更新されました ',
        ],
    ],

    'please_enable_these_php_functions' => '以下のPHP関数が有効になっていることを確認してください'
];