<?php

namespace Wncms\Tests\Feature;

use Wncms\Http\Controllers\Frontend\UserController;
use Wncms\Tests\TestCase;

class FrontendRegistrationSettingTest extends TestCase
{
    protected mixed $originalDisableRegistration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDisableRegistration = gss('disable_registration', true);
    }

    protected function tearDown(): void
    {
        uss('disable_registration', $this->originalDisableRegistration ? 1 : 0);

        parent::tearDown();
    }

    public function test_frontend_registration_follows_disable_registration_setting(): void
    {
        $controller = app(UserController::class);
        $enabledRegistration = new \ReflectionMethod($controller, 'enabledRegistration');
        $enabledRegistration->setAccessible(true);

        uss('disable_registration', 1);
        $this->assertFalse($enabledRegistration->invoke($controller));

        uss('disable_registration', 0);
        $this->assertTrue($enabledRegistration->invoke($controller));
    }
}
