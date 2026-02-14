<?php

namespace Wncms\Tests\Unit;

use Wncms\Models\Plugin;
use Wncms\Tests\TestCase;

class PluginLoadErrorDiagnosticsTest extends TestCase
{
    public function test_it_formats_load_error_remark_with_source_file(): void
    {
        $remark = Plugin::formatLoadErrorRemark('init failed', '/var/www/plugins/demo/Plugin.php');

        $this->assertStringStartsWith(Plugin::LOAD_ERROR_PREFIX, $remark);
        $this->assertStringContainsString('init failed', $remark);
        $this->assertStringContainsString(Plugin::LOAD_ERROR_SOURCE_FILE_MARKER . '/var/www/plugins/demo/Plugin.php', $remark);
    }

    public function test_it_extracts_load_error_diagnostics_from_structured_remark(): void
    {
        $remark = '[LOAD_ERROR] 2026-02-14 10:30:00 plugin class not found | source_file=/var/www/plugins/demo/Plugin.php';

        $diagnostics = Plugin::extractLoadErrorDiagnostics($remark);

        $this->assertTrue($diagnostics['is_load_error']);
        $this->assertSame('plugin class not found', $diagnostics['last_load_error']);
        $this->assertSame('/var/www/plugins/demo/Plugin.php', $diagnostics['source_file']);
    }

    public function test_it_extracts_load_error_diagnostics_from_legacy_remark_without_source_file(): void
    {
        $remark = '[LOAD_ERROR] 2026-02-14 10:30:00 plugin class not found';

        $diagnostics = Plugin::extractLoadErrorDiagnostics($remark);

        $this->assertTrue($diagnostics['is_load_error']);
        $this->assertSame('plugin class not found', $diagnostics['last_load_error']);
        $this->assertSame('-', $diagnostics['source_file']);
    }
}
