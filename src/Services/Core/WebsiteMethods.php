<?php

namespace Wncms\Services\Core;

use Wncms\Models\Website;
use Illuminate\Support\Facades\Http;

trait WebsiteMethods
{
    public function isSelectedWebsite(Website $_website): bool
    {
        return (
            $_website->id == request()->website ||
            (!request()->has('website') && $_website->id == session('selected_website_id')) ||
            (!request()->has('website') && empty(session('selected_website_id')) && wncms()->website()->get()?->id == $_website->id)
        );
    }

    public function checkLicense(Website $website)
    {
        $url = "https://api.wncms.cc/api/v1/license/check";
        $cacheKey = "website_check";
        $cacheTag = ["websites"];

        $result = wncms()->cache()->tags($cacheTag)->remember($cacheKey, 86400, function () use ($url, $website) {
            $response = Http::post($url, [
                'domain' => $website->domain,
                'license' => $website->license,
            ]);
            return json_decode($response->body(), true);
        });

        if (!empty($result['result'])) {
            return $website;
        }

        return redirect()->route('websites.create')->send();
    }
}
