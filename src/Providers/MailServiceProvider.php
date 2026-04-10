<?php

namespace Wncms\Providers;

use Illuminate\Support\ServiceProvider;
use Throwable;

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
            $websiteSiteName = null;
            try {
                $website = wncms()->website()->get();
                if (is_object($website) && get_debug_type($website) !== '__PHP_Incomplete_Class') {
                    $websiteSiteName = data_get($website, 'site_name');
                }
            } catch (Throwable $e) {
                logger()->warning('MailServiceProvider failed to resolve website for SMTP from-name fallback', [
                    'message' => $e->getMessage(),
                ]);
            }

            $smtpConfig = [
                // 'driver' => gss('mail_driver', 'smtp'),
                'transport' => gss('mail_driver', 'smtp'),
                'host' => gss('smtp_host'),
                'port' => gss('smtp_port', 465),
                'encryption' => gss('smtp_mode', 'ssl'),
                'username' => gss('smtp_username'),
                'password' => gss('smtp_password'),
                'from' => [
                    'address' => gss('smtp_username'),
                    'name' => gss('smtp_from_name', $websiteSiteName ?: config('app.name', 'WNCMS')),
                ],
            ];

            $newConfig = array_merge(config('mail.mailers.smtp'), $smtpConfig);
            config()->set('mail.mailers.smtp', $newConfig);

            // dd(config('mail.mailers.smtp'));
        }
    }
}
