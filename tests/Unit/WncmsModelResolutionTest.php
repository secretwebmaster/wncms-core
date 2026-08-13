<?php

namespace App\Models;

if (!class_exists(User::class, false)) {
    class User extends \Illuminate\Foundation\Auth\User
    {
    }
}

namespace Wncms\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Wncms\Models\Builders\AppendOnlySecurityEventBuilder;
use Wncms\Tests\TestCase;

class WncmsModelResolutionTest extends TestCase
{
    /**
     * Verify recursive model discovery ignores support classes with required constructors.
     *
     * @return void
     */
    public function test_model_discovery_skips_non_model_support_classes(): void
    {
        $models = wncms()->getModelNames();

        $this->assertNotEmpty($models);
        $this->assertFalse($models->contains(
            static fn (array $model): bool => $model['model_name_with_namespace'] === AppendOnlySecurityEventBuilder::class,
        ));
    }

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
