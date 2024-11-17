<?php

namespace Wncms\Providers;

use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (wncms_is_installed()) {
            $website = wncms()->website()->get();
            if($website){
                $smtp_config = [
                    // 'driver' => gss('mail_driver', 'smtp'),
                    'transport' => gss('mail_driver', 'smtp'),
                    'host' => gss('smtp_host'),
                    'port' => gss('smtp_port', 465),
                    'encryption' => gss('smtp_mode', 'ssl'),
                    'username' => gss('smtp_username'),
                    'password' => gss('smtp_password'),
                    'from' => [
                        'address' => gss('smtp_username'),
                        'name' => gss('smtp_from_name', $website->site_name)
                    ],
                ];
    
                $new_config = array_merge(config('mail.mailers.smtp'), $smtp_config);
                config()->set('mail.mailers.smtp', $new_config);
            }

            // dd(config('mail.mailers.smtp'));
        }
    }
}
