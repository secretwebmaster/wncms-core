<?php

namespace Wncms\Tests\Unit\Api\V2;

use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Tests\TestCase;

class ApiContractServiceProviderTest extends TestCase
{
    /**
     * Resolve the lazily built contract registry from the application container.
     *
     * @return void
     */
    public function test_it_registers_a_singleton_contract_registry_with_core_and_legacy_operations(): void
    {
        $registry = app(ApiContractRegistry::class);

        $this->assertSame($registry, app(ApiContractRegistry::class));
        $this->assertNotNull($registry->operation('frontend.health'));
        $this->assertNotNull($registry->operation('system.translations'));
        $this->assertNotNull($registry->operation('backend.links.index'));
        $this->assertNotNull($registry->operation('backend.links.bulk_update'));
        $this->assertSame('legacy_bridge', $registry->operation('backend.links.bulk_update')?->implementation);
        $this->assertSame('domain', $registry->operation('backend.links.index')?->implementation);
        $this->assertSame('legacy_controller', $registry->operation('backend.posts.index')?->implementation);
    }
}
