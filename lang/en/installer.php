<?php

return [
    'title' => 'WNCMS Installation Wizard',
    'next' => 'Next',
    'back' => 'Back',
    'finish' => 'Install',

    'forms' => [
        'errorTitle' => 'The following errors occurred:',
    ],

    'welcome' => [
        'templateTitle' => 'WNCMS Installation Wizard',
        'title'   => 'WNCMS Installation Wizard',
        'message' => 'Simple and Fast Installation',
        'next'    => 'Start Installation',
    ],

    'requirements' => [
        'templateTitle' => 'Step 1 | Environment Requirements',
        'title' => 'Environment Requirements',
        'next'    => 'Check Permissions',
    ],

    'permissions' => [
        'templateTitle' => 'Step 2 | Permissions',
        'title' => 'Permissions',
        'next' => 'Set System Configuration',
    ],

    'environment' => [
        'menu' => [
            'templateTitle' => 'Step 3 | System Configuration',
            'title' => 'System Configuration',
            'desc' => 'Please choose how to configure the application\'s <code>.env</code> file.',
            'wizard-button' => 'Form Wizard Configuration',
            'classic-button' => 'Classic Text Editor',
        ],
        'wizard' => [
            'templateTitle' => 'Step 3 | System Configuration',
            'title' => 'System Configuration',
            'tabs' => [
                'environment' => 'Basic Configuration',
                'database' => 'Database',
                'application' => 'Cache',
            ],
            'form' => [
                'name_required' => 'The environment name is required.',
                'app_name_label' => 'Site ID (English and numbers only, used to differentiate multiple CMSs on a single server)',
                'app_name_placeholder' => '',
                'app_environment_label' => 'Application Environment',
                'app_environment_label_local' => 'Local',
                'app_environment_label_developement' => 'Development',
                'app_environment_label_qa' => 'Testing',
                'app_environment_label_production' => 'Production',
                'app_environment_label_other' => 'Other',
                'app_environment_placeholder_other' => 'Enter your environment...',
                'app_debug_label' => 'Application Debug',
                'app_debug_label_true' => 'Yes',
                'app_debug_label_false' => 'No',
                'app_log_level_label' => 'Application Log Level',
                'app_log_level_label_debug' => 'Debug',
                'app_log_level_label_info' => 'Info',
                'app_log_level_label_notice' => 'Notice',
                'app_log_level_label_warning' => 'Warning',
                'app_log_level_label_error' => 'Error',
                'app_log_level_label_critical' => 'Critical',
                'app_log_level_label_alert' => 'Alert',
                'app_log_level_label_emergency' => 'Emergency',
                'app_url_label' => 'Main Domain (with https://)',
                'app_url_placeholder' => 'Must start with https://',
                'db_connection_failed' => 'Unable to connect to the database.',
                'db_connection_label' => 'Database Connection',
                'db_connection_label_mysql' => 'MySQL',
                'db_connection_label_sqlite' => 'SQLite',
                'db_connection_label_pgsql' => 'PostgreSQL',
                'db_connection_label_sqlsrv' => 'SQL Server',
                'db_host_label' => 'Database Host',
                'db_host_placeholder' => 'Default is 127.0.0.1',
                'db_port_label' => 'Database Port',
                'db_port_placeholder' => 'Default is 3306',
                'db_name_label' => 'Database Name',
                'db_name_placeholder' => '',
                'db_username_label' => 'Database Username',
                'db_username_placeholder' => '',
                'db_password_label' => 'Database Password',
                'db_password_placeholder' => '',

                'app_tabs' => [
                    'more_info' => 'More Info',
                    'broadcasting_title' => 'Broadcasting, Cache, Sessions, and Queues',
                    'broadcasting_label' => 'Broadcast Driver',
                    'broadcasting_placeholder' => 'Broadcast Driver',
                    'cache_label' => 'Cache Driver',
                    'cache_placeholder' => 'Default is redis, use file if Redis is not available',
                    'session_label' => 'Session Driver',
                    'session_placeholder' => 'Default is redis, use file if Redis is not available',
                    'queue_label' => 'Queue Driver',
                    'queue_placeholder' => 'Queue Driver',
                    'redis_label' => 'Redis Driver',
                    'redis_host' => 'Redis Host',
                    'redis_host_host' => 'Default is 127.0.0.1',
                    'redis_password' => 'Redis Password',
                    'redis_password_placeholder' => 'Default is empty',
                    'redis_port' => 'Redis Port',
                    'redis_db' => 'Redis Database Index (0-15), default is 0',
                    'redis_port_placeholder' => 'Default 6379',

                    'mail_label' => 'Mail',
                    'mail_driver_label' => 'Mail Driver',
                    'mail_driver_placeholder' => 'Mail Driver',
                    'mail_host_label' => 'Mail Host',
                    'mail_host_placeholder' => 'Mail Host',
                    'mail_port_label' => 'Mail Port',
                    'mail_port_placeholder' => 'Mail Port',
                    'mail_username_label' => 'Mail Username',
                    'mail_username_placeholder' => 'Mail Username',
                    'mail_password_label' => 'Mail Password',
                    'mail_password_placeholder' => 'Mail Password',
                    'mail_encryption_label' => 'Mail Encryption',
                    'mail_encryption_placeholder' => 'Mail Encryption',

                    'pusher_label' => 'Pusher',
                    'pusher_app_id_label' => 'Pusher App ID',
                    'pusher_app_id_palceholder' => 'Pusher App ID',
                    'pusher_app_key_label' => 'Pusher App Key',
                    'pusher_app_key_palceholder' => 'Pusher App Key',
                    'pusher_app_secret_label' => 'Pusher App Secret',
                    'pusher_app_secret_palceholder' => 'Pusher App Secret',
                ],

                'admin' => [
                    'username' => 'Admin Username',
                    'email' => 'Admin Email',
                    'password' => 'Admin Password',
                ],
                'buttons' => [
                    'setup_database' => 'Set Up Database',
                    'setup_application' => 'Set Up Cache',
                    'install' => 'Install',
                ],
            ],
        ],
        'classic' => [
            'templateTitle' => 'Step 3 | System Configuration',
            'title' => 'Classic Environment Editor',
            'save' => 'Save .env',
            'back' => 'Use Form Wizard',
            'install' => 'Save and Install',
        ],
        'success' => 'Your .env file settings have been saved.',
        'errors' => 'Unable to save .env file, please create it manually.',
    ],

    'install' => 'Install',

    'installed' => [
        'success_log_message' => 'WNCMS installation was successfully completed at ',
    ],

    'final' => [
        'title' => 'Installation Complete',
        'templateTitle' => 'Installation Complete',
        'finished' => 'WNCMS was successfully installed',
        'migration' => 'Migration and Seed Data Console Output:',
        'console' => 'Application Console Output:',
        'log' => 'Installation Log Items:',
        'env' => 'Final .env File:',
        'exit' => 'Click here to exit',
    ],

    'updater' => [
        'title' => 'WNCMS Updater',

        'welcome' => [
            'title'   => 'Welcome to the Updater',
            'message' => 'Welcome to the update wizard.',
        ],

        'overview' => [
            'title'   => 'Overview',
            'message' => 'There is 1 update. | There are :number updates.',
            'install_updates' => 'Install Updates',
        ],

        'final' => [
            'title' => 'Complete',
            'finished' => 'The application database has been successfully updated.',
            'exit' => 'Click here to exit',
        ],

        'log' => [
            'success_message' => 'WNCMS was successfully updated at ',
        ],
    ],

    'please_enable_these_php_functions' => 'Please ensure the following PHP functions are enabled'
];