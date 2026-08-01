<?php

namespace Wncms\Tests\Feature;

use Illuminate\Support\Facades\View;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class InstalledWebsiteViewShareTest extends TestCase
{
    /**
     * Configure the installed state before package providers boot.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('wncms.testing_is_installed', true);
    }

    /**
     * Share the resolved website with the installed auth layout.
     *
     * @return void
     */
    public function test_installed_application_shares_website_with_auth_layout(): void
    {
        $website = Website::query()->firstOrFail();
        $sharedWebsite = View::shared('website');

        $this->assertInstanceOf(Website::class, $sharedWebsite);
        $this->assertTrue($website->is($sharedWebsite));

        $html = view('wncms::auth.login')->render();

        $this->assertStringContainsString('<title>' . e($website->site_name) . '</title>', $html);
    }
}
