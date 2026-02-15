<?php

namespace Wncms\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Wncms\Models\Plugin;
use Wncms\Plugins\PluginLifecycleManager;
use Wncms\Tests\TestCase;

class PluginLifecycleUpgradeMapTest extends TestCase
{
    use RefreshDatabase;

    protected string $pluginsRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginsRoot = storage_path('framework/testing/plugins-upgrade-map-' . uniqid('', true));
        File::ensureDirectoryExists($this->pluginsRoot);
        config(['filesystems.disks.plugins.root' => $this->pluginsRoot]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->pluginsRoot);
        parent::tearDown();
    }

    public function test_it_runs_upgrade_files_in_order_and_updates_installed_version(): void
    {
        $plugin = $this->createPlugin('upgrade-map-plugin', '1.0.0');
        $this->writeManifest('upgrade-map-plugin', '1.3.0');
        $this->writeEntryFile(
            'upgrade-map-plugin',
            "<?php\n\n"
            . "return new class extends \\Wncms\\Plugins\\AbstractPlugin\n"
            . "{\n"
            . "    public array \$upgrades = [\n"
            . "        '1.2.0' => 'upgrade_1_2_0.php',\n"
            . "        '1.3.0' => 'upgrade_1_3_0.php',\n"
            . "    ];\n"
            . "};\n"
        );
        $this->writeUpgradeFile('upgrade-map-plugin', 'upgrade_1_2_0.php');
        $this->writeUpgradeFile('upgrade-map-plugin', 'upgrade_1_3_0.php');

        $result = app(PluginLifecycleManager::class)->upgradePlugin($plugin);

        $this->assertTrue($result['passed']);
        $this->assertTrue($result['changed']);
        $this->assertSame('1.3.0', $plugin->fresh()->version);

        $logPath = $this->pluginsRoot . DIRECTORY_SEPARATOR . 'upgrade-map-plugin' . DIRECTORY_SEPARATOR . 'upgrade.log';
        $this->assertFileExists($logPath);
        $log = (string) file_get_contents($logPath);
        $this->assertStringContainsString("1.0.0=>1.2.0\n", $log);
        $this->assertStringContainsString("1.2.0=>1.3.0\n", $log);
    }

    public function test_it_fails_when_target_version_has_no_upgrade_steps(): void
    {
        $plugin = $this->createPlugin('upgrade-missing-step-plugin', '1.0.0');
        $this->writeManifest('upgrade-missing-step-plugin', '1.3.0');
        $this->writeEntryFile(
            'upgrade-missing-step-plugin',
            "<?php\n\n"
            . "return new class extends \\Wncms\\Plugins\\AbstractPlugin\n"
            . "{\n"
            . "    public array \$upgrades = [\n"
            . "        '1.2.0' => 'upgrade_1_2_0.php',\n"
            . "    ];\n"
            . "};\n"
        );
        $this->writeUpgradeFile('upgrade-missing-step-plugin', 'upgrade_1_2_0.php');

        $result = app(PluginLifecycleManager::class)->upgradePlugin($plugin);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['changed']);
        $this->assertStringContainsString('do not reach available version', (string) $result['message']);
        $this->assertSame('1.0.0', $plugin->fresh()->version);
    }

    public function test_it_fails_when_upgrade_file_is_missing(): void
    {
        $plugin = $this->createPlugin('upgrade-missing-file-plugin', '1.0.0');
        $this->writeManifest('upgrade-missing-file-plugin', '1.2.0');
        $this->writeEntryFile(
            'upgrade-missing-file-plugin',
            "<?php\n\n"
            . "return new class extends \\Wncms\\Plugins\\AbstractPlugin\n"
            . "{\n"
            . "    public array \$upgrades = [\n"
            . "        '1.2.0' => 'upgrade_1_2_0.php',\n"
            . "    ];\n"
            . "};\n"
        );

        $result = app(PluginLifecycleManager::class)->upgradePlugin($plugin);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['changed']);
        $this->assertStringContainsString('upgrade file not found', (string) $result['message']);
        $this->assertSame('1.0.0', $plugin->fresh()->version);
    }

    protected function createPlugin(string $pluginId, string $version): Plugin
    {
        return Plugin::create([
            'plugin_id' => $pluginId,
            'name' => $pluginId,
            'description' => '',
            'author' => '',
            'version' => $version,
            'url' => '',
            'status' => 'inactive',
            'path' => $pluginId,
            'remark' => null,
        ]);
    }

    protected function writeManifest(string $pluginId, string $version): void
    {
        $pluginDirectory = $this->pluginsRoot . DIRECTORY_SEPARATOR . $pluginId;
        File::ensureDirectoryExists($pluginDirectory);
        File::put(
            $pluginDirectory . DIRECTORY_SEPARATOR . 'plugin.json',
            json_encode([
                'id' => $pluginId,
                'name' => $pluginId,
                'version' => $version,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    protected function writeEntryFile(string $pluginId, string $content): void
    {
        $pluginDirectory = $this->pluginsRoot . DIRECTORY_SEPARATOR . $pluginId;
        File::ensureDirectoryExists($pluginDirectory);
        File::put($pluginDirectory . DIRECTORY_SEPARATOR . 'Plugin.php', $content);
    }

    protected function writeUpgradeFile(string $pluginId, string $fileName): void
    {
        $pluginDirectory = $this->pluginsRoot . DIRECTORY_SEPARATOR . $pluginId;
        $upgradesDirectory = $pluginDirectory . DIRECTORY_SEPARATOR . 'upgrades';
        File::ensureDirectoryExists($upgradesDirectory);
        File::put(
            $upgradesDirectory . DIRECTORY_SEPARATOR . $fileName,
            "<?php\n\n"
            . "return function (array \$context) {\n"
            . "    file_put_contents(__DIR__ . '/upgrade.log', \$context['from_version'] . '=>' . \$context['to_version'] . \"\\n\", FILE_APPEND);\n"
            . "};\n"
        );
    }
}
