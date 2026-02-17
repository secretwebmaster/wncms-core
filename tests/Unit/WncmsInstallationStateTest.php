<?php

namespace Wncms\Tests\Unit;

use Orchestra\Testbench\TestCase;

class WncmsInstallationStateTest extends TestCase
{
    protected string $installedFilePath;

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../helpers/wncms.php';

        $this->installedFilePath = storage_path('installed');
        @unlink($this->installedFilePath);
        config(['wncms.testing_is_installed' => null]);
    }

    protected function tearDown(): void
    {
        @unlink($this->installedFilePath);
        config(['wncms.testing_is_installed' => null]);

        parent::tearDown();
    }

    public function test_it_can_force_installed_state_in_testing_via_config_override(): void
    {
        config(['wncms.testing_is_installed' => true]);
        $this->assertTrue(wncms_is_installed());

        config(['wncms.testing_is_installed' => false]);
        $this->assertFalse(wncms_is_installed());
    }

    public function test_it_falls_back_to_installed_file_check_when_override_is_null(): void
    {
        config(['wncms.testing_is_installed' => null]);

        $this->assertFalse(wncms_is_installed());

        file_put_contents($this->installedFilePath, 'installed');

        $this->assertTrue(wncms_is_installed());
    }
}
