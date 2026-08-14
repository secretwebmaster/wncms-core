<?php

namespace Wncms\Tests\Unit\Api\V2;

use PHPUnit\Framework\TestCase;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Providers\CoreAuthSecurityContractProvider;

class CoreAuthSecurityContractProviderTest extends TestCase
{
    public function test_provider_registers_public_refresh_and_interactive_security_contracts(): void
    {
        $registry = new ApiContractRegistry;
        (new CoreAuthSecurityContractProvider)->register($registry);

        $login = $registry->operation('backend.authentication.login');
        $this->assertSame([], $login->acceptedCredentialTypes);
        $this->assertSame(['json', 'cookie'], $login->refreshTransports);
        $this->assertTrue($login->request->toArray()['properties']['password']['writeOnly']);

        $sessions = $registry->operation('backend.authentication.sessions.destroy');
        $this->assertSame(['interactive_access'], $sessions->acceptedCredentialTypes);
        $this->assertTrue($sessions->idempotencyRequired);
        $this->assertSame('sensitive', $sessions->securityRisk);
    }
}
