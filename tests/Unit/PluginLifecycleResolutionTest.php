<?php

namespace Wncms\Tests\Unit;

use Illuminate\Support\Facades\File;
use Wncms\Models\Plugin;
use Wncms\Plugins\PluginLifecycleManager;
use Wncms\Tests\TestCase;

class PluginLifecycleResolutionTest extends TestCase
{
    protected string $pluginsRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginsRoot = storage_path('framework/testing/plugins-lifecycle-resolution-' . uniqid('', true));
        File::ensureDirectoryExists($this->pluginsRoot);
        config(['filesystems.disks.plugins.root' => $this->pluginsRoot]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pluginsRoot);
        parent::tearDown();
    }

    public function test_container_resolved_lifecycle_manager_can_run_multiple_hooks_for_entry_files_that_return_instances(): void
    {
        $plugin = $this->createPlugin('instance-entry-plugin');
        $this->writeManifest('instance-entry-plugin');
        $this->writeEntryFile(
            'instance-entry-plugin',
            "<?php\n\n"
            . "return new class extends \\Wncms\\Plugins\\AbstractPlugin\n"
            . "{\n"
            . "    public function init(): void\n"
            . "    {\n"
            . "        file_put_contents(__DIR__ . '/lifecycle.log', \"init\\n\", FILE_APPEND);\n"
            . "    }\n\n"
            . "    public function deactivate(): void\n"
            . "    {\n"
            . "        file_put_contents(__DIR__ . '/lifecycle.log', \"deactivate\\n\", FILE_APPEND);\n"
            . "    }\n"
            . "};\n"
        );

        $init = app(PluginLifecycleManager::class)->run($plugin, 'init');
        $deactivate = app(PluginLifecycleManager::class)->run($plugin, 'deactivate');

        $this->assertTrue($init['passed']);
        $this->assertTrue($deactivate['passed']);

        $logPath = $this->pluginsRoot . DIRECTORY_SEPARATOR . 'instance-entry-plugin' . DIRECTORY_SEPARATOR . 'lifecycle.log';
        $this->assertFileExists($logPath);
        $this->assertSame("init\ndeactivate\n", (string) file_get_contents($logPath));
    }

    public function test_it_returns_a_localized_error_when_entry_does_not_return_an_instance_and_manifest_class_is_missing(): void
    {
        app()->setLocale('zh_TW');

        $plugin = $this->createPlugin('missing-class-plugin');
        $this->writeManifest('missing-class-plugin');
        $this->writeEntryFile('missing-class-plugin', "<?php\n\nreturn true;\n");

        $result = app(PluginLifecycleManager::class)->run($plugin, 'deactivate');

        $this->assertFalse($result['passed']);
        $this->assertSame(
            __('wncms::word.plugin_manifest_class_required_when_entry_returns_no_instance', ['entry' => 'Plugin.php']),
            $result['message']
        );
    }

    protected function createPlugin(string $pluginId): Plugin
    {
        $plugin = new Plugin();
        $plugin->forceFill([
            'plugin_id' => $pluginId,
            'name' => $pluginId,
            'description' => '',
            'author' => '',
            'version' => '1.0.0',
            'url' => '',
            'status' => 'active',
            'path' => $pluginId,
            'remark' => null,
        ]);

        return $plugin;
    }

    protected function writeManifest(string $pluginId): void
    {
        $pluginDirectory = $this->pluginsRoot . DIRECTORY_SEPARATOR . $pluginId;
        File::ensureDirectoryExists($pluginDirectory);
        File::put(
            $pluginDirectory . DIRECTORY_SEPARATOR . 'plugin.json',
            json_encode([
                'id' => $pluginId,
                'name' => $pluginId,
                'version' => '1.0.0',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    protected function writeEntryFile(string $pluginId, string $content): void
    {
        $pluginDirectory = $this->pluginsRoot . DIRECTORY_SEPARATOR . $pluginId;
        File::ensureDirectoryExists($pluginDirectory);
        File::put($pluginDirectory . DIRECTORY_SEPARATOR . 'Plugin.php', $content);
    }
}
