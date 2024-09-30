<?php

namespace Wncms\Providers;

use Wncms\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Request $request)
    {
        if(!wncms_is_installed()) return;
        
        try {

            $cacheKey = "gsss";
            $cacheTags = ['system'];
            //wncms_clear_cache($cacheKey, $cacheTags);

            $settings = wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 86400), function(){
                return Setting::all()->pluck('value', 'key');
            });
            // dd($settings);

            // Store all the database settings in a config array
            foreach ($settings as $key => $value) {
                config(['settings.' . $key => $value]);
            }

            //google
            config(['services.google.client_id' => gss('google_client_id', config('services.google.client_id'))]);
            config(['services.google.client_secret' => gss('google_client_secret', config('services.google.client_secret'))]);
            config(['services.google.redirect' => gss('google_redirect', config('services.google.redirect'))]);

            //paypal
            config(['paypal' =>[
                'mode' => gss('paypal_mode'),
                'client_id' => gss('paypal_mode') == 'sandbox' ? gss('paypal_sandbox_client_id') : gss('paypal_client_id'),
                'client_secret' => gss('paypal_mode') == 'sandbox' ? gss('paypal_sandbox_client_secret') : gss('paypal_client_secret'),
                'webhook_id' => gss('paypal_mode') == 'sandbox' ? gss('paypal_sandbox_webhook_id') : gss('paypal_webhook_id'),
            ]]);

        } catch (\Exception $e) {
            
        }
    }
}
