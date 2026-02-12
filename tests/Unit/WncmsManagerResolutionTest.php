<?php

namespace App\Services\Managers;

use Wncms\Services\Managers\ModelManager;
use Wncms\Tests\Unit\Fixtures\DummyDependency;

class CustomInjectedManager extends ModelManager
{
    public function __construct(public DummyDependency $dependency)
    {
    }

    public function getModelClass(): string
    {
        return \Wncms\Models\Post::class;
    }

    protected function buildListQuery(array $options): mixed
    {
        return collect();
    }
}

class CatalogItemManager extends ModelManager
{
    public function getModelClass(): string
    {
        return \Wncms\Models\Post::class;
    }

    protected function buildListQuery(array $options): mixed
    {
        return collect();
    }
}

namespace Wncms\Tests\Unit;

use Wncms\Tests\TestCase;
use Wncms\Tests\Unit\Fixtures\DummyDependency;

class WncmsManagerResolutionTest extends TestCase
{
    public function test_it_resolves_custom_app_manager_through_container_dependencies(): void
    {
        $dependency = new DummyDependency('from-container');
        app()->instance(DummyDependency::class, $dependency);

        $manager = wncms()->custom_injected();

        $this->assertInstanceOf(\App\Services\Managers\CustomInjectedManager::class, $manager);
        $this->assertSame($dependency, $manager->dependency);
    }

    public function test_it_resolves_plural_alias_to_singular_app_manager_class(): void
    {
        $manager = wncms()->catalog_items();

        $this->assertInstanceOf(\App\Services\Managers\CatalogItemManager::class, $manager);
    }
}
