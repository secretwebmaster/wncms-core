<?php

namespace App\Models;

if (!class_exists(User::class, false)) {
    class User extends \Illuminate\Foundation\Auth\User
    {
    }
}

namespace Wncms\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Wncms\Tests\TestCase;

class WncmsModelResolutionTest extends TestCase
{
    public function test_it_prefers_auth_user_model_over_app_user_model_fallback(): void
    {
        Config::set('auth.providers.users.model', \Wncms\Models\User::class);

        $this->clearCachedModelClass('user');

        $this->assertSame(\Wncms\Models\User::class, wncms()->getModelClass('user'));
    }

    protected function clearCachedModelClass(string $key): void
    {
        $wncms = wncms();
        $reflection = new \ReflectionObject($wncms);

        if (!$reflection->hasProperty('modelClassCache')) {
            return;
        }

        $property = $reflection->getProperty('modelClassCache');
        $property->setAccessible(true);

        $cache = (array) $property->getValue($wncms);
        unset($cache[$key]);
        $property->setValue($wncms, $cache);
    }

    public function test_it_overrides_cached_user_model_with_auth_user_model(): void
    {
        Config::set('auth.providers.users.model', \Wncms\Models\User::class);

        $wncms = wncms();
        $wncms->registerModel(\App\Models\User::class);

        $this->assertSame(\Wncms\Models\User::class, $wncms->getModelClass('user'));
    }
}
