<?php

namespace Wncms\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Wncms\Models\Plugin;
use Wncms\Plugins\PluginActivationCompatibilityValidator;
use Wncms\Tests\TestCase;

class PluginActivationCompatibilityValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected string $pluginsRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginsRoot = storage_path('framework/testing/plugins-' . uniqid('', true));
        File::ensureDirectoryExists($this->pluginsRoot);
        config(['filesystems.disks.plugins.root' => $this->pluginsRoot]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pluginsRoot);
        parent::tearDown();
    }

    public function test_it_blocks_activation_when_dependency_plugin_is_missing(): void
    {
        $plugin = $this->createPlugin('target-plugin');
        $this->writePluginManifest('target-plugin', [
            'id' => 'target-plugin',
            'name' => 'Target Plugin',
            'version' => '1.0.0',
            'dependencies' => ['required-plugin'],
        ]);

        $result = app(PluginActivationCompatibilityValidator::class)->validate($plugin);

        $this->assertFalse($result['passed']);
        $this->assertSame('wncms::word.plugin_activation_blocked_dependency_missing', $result['message_key']);
        $this->assertStringContainsString('required-plugin', (string) $result['message']);
    }

    public function test_it_blocks_activation_when_dependency_plugin_is_inactive(): void
    {
        $plugin = $this->createPlugin('target-plugin');
        $this->createPlugin('required-plugin', status: 'inactive');

        $this->writePluginManifest('target-plugin', [
            'id' => 'target-plugin',
            'name' => 'Target Plugin',
            'version' => '1.0.0',
            'dependencies' => ['required-plugin'],
        ]);

        $result = app(PluginActivationCompatibilityValidator::class)->validate($plugin);

        $this->assertFalse($result['passed']);
        $this->assertSame('wncms::word.plugin_activation_blocked_dependency_inactive', $result['message_key']);
        $this->assertStringContainsString('required-plugin', (string) $result['message']);
    }

    public function test_it_blocks_activation_when_dependency_version_does_not_match_constraint(): void
    {
        $plugin = $this->createPlugin('target-plugin');
        $this->createPlugin('required-plugin', status: 'active', version: '1.4.0');

        $this->writePluginManifest('target-plugin', [
            'id' => 'target-plugin',
            'name' => 'Target Plugin',
            'version' => '1.0.0',
            'dependencies' => [
                'required-plugin' => '^2.0',
            ],
        ]);

        $result = app(PluginActivationCompatibilityValidator::class)->validate($plugin);

        $this->assertFalse($result['passed']);
        $this->assertSame('wncms::word.plugin_activation_blocked_dependency_version_mismatch', $result['message_key']);
        $this->assertStringContainsString('^2.0', (string) $result['message']);
    }

    public function test_it_allows_activation_when_dependency_is_active_and_version_matches(): void
    {
        $plugin = $this->createPlugin('target-plugin');
        $this->createPlugin('required-plugin', status: 'active', version: '1.4.2');

        $this->writePluginManifest('target-plugin', [
            'id' => 'target-plugin',
            'name' => 'Target Plugin',
            'version' => '1.0.0',
            'dependencies' => [
                [
                    'id' => 'required-plugin',
                    'version' => '^1.4',
                ],
            ],
        ]);

        $result = app(PluginActivationCompatibilityValidator::class)->validate($plugin);

        $this->assertTrue($result['passed']);
        $this->assertSame('', $result['message']);
    }

    protected function createPlugin(string $pluginId, string $status = 'inactive', string $version = '1.0.0'): Plugin
    {
        return Plugin::create([
            'plugin_id' => $pluginId,
            'name' => $pluginId,
            'description' => '',
            'author' => '',
            'version' => $version,
            'url' => '',
            'status' => $status,
            'path' => $pluginId,
            'remark' => null,
        ]);
    }

    protected function writePluginManifest(string $pluginId, array $manifest): void
    {
        $pluginDirectory = $this->pluginsRoot . DIRECTORY_SEPARATOR . $pluginId;
        File::ensureDirectoryExists($pluginDirectory);
        File::put(
            $pluginDirectory . DIRECTORY_SEPARATOR . 'plugin.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
