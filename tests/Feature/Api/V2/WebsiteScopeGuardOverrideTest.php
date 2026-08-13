<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\WebsiteScopeGuard;
use Wncms\Models\BaseModel;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class WebsiteScopeGuardOverrideTest extends TestCase
{
    use DatabaseTransactions;

    public function test_registry_website_override_resolves_without_core_concrete_type(): void
    {
        $alias = __NAMESPACE__.'\\WebsiteOverride\\Website';
        if (! class_exists($alias, false)) {
            class_alias(Task8WebsiteOverride::class, $alias);
        }
        wncms()->registerModel($alias);

        try {
            $actor = User::query()->firstOrFail();
            $website = $alias::query()->where('user_id', $actor->getKey())->firstOrFail();
            $context = new AuthenticationContext($actor, ApiCredential::TYPE_INTERACTIVE_ACCESS, 'website-override', 'website-session', ['*'], [$website->getKey()]);
            $request = Request::create('/api/v2/backend', 'GET', ['website_id' => $website->getKey()]);

            $resolution = app(WebsiteScopeGuard::class)->resolve($request, $context);

            $this->assertTrue($resolution->isAllowed());
            $this->assertInstanceOf($alias, $resolution->website());
            $this->assertSame('website:'.$website->getKey(), WebsiteScopeGuard::identity($resolution->website()));
        } finally {
            wncms()->registerModel(Website::class);
        }
    }
}

class Task8WebsiteOverride extends BaseModel
{
    public static $modelKey = 'website';

    protected $table = 'websites';

    protected $guarded = [];
}
