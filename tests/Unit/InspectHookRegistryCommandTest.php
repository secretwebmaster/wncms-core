<?php

namespace Wncms\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Wncms\Tests\TestCase;

class InspectHookRegistryCommandTest extends TestCase
{
    public function test_it_outputs_hook_registry_as_json(): void
    {
        Artisan::call('wncms:hook-list', ['--json' => true]);
        $output = trim(Artisan::output());

        $decoded = json_decode($output, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('hooks', $decoded);
        $this->assertArrayHasKey('extensions', $decoded);
        $this->assertArrayHasKey('macros', $decoded['extensions']);

        $hookNames = array_map(fn(array $hook) => (string) ($hook['name'] ?? ''), $decoded['hooks']);
        $this->assertContains('wncms.frontend.users.login.before', $hookNames);
    }
}
