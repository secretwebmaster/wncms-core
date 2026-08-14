<?php

namespace Wncms\Tests\Unit\Api\V2;

use PHPUnit\Framework\TestCase;

class AuthSecurityDocumentationTest extends TestCase
{
    private const FILES = ['sessions.md', 'service-tokens.md', 'security-policy.md', 'api-only-mode.md', 'legacy-authentication.md'];

    public function test_three_language_security_docs_share_machine_tokens_and_valid_relative_links(): void
    {
        foreach (self::FILES as $file) {
            $english = $this->path('documentations/manual/api/'.$file);
            foreach (['documentations/manual/zh-CN/api/', 'documentations/manual/zh-TW/api/'] as $directory) {
                $localized = $this->path($directory.$file);
                $this->assertFileExists($localized);
                $this->assertSame($this->machineTokens($english), $this->machineTokens($localized), $file.' machine tokens differ.');
            }
            $contents = file_get_contents($english);
            preg_match_all('/\]\((\.\/[^)#]+\.md)(?:#[^)]+)?\)/', $contents, $links);
            foreach ($links[1] as $link) $this->assertFileExists(dirname($english).'/'.$link);
        }
    }

    public function test_docs_do_not_publish_working_credentials_or_browser_refresh_storage(): void
    {
        foreach (glob($this->path('documentations/manual/{,zh-CN/,zh-TW/}api/*.md'), GLOB_BRACE) as $path) {
            $contents = file_get_contents($path);
            $this->assertDoesNotMatchRegularExpression('/wncms_(?:at|rt|st)_[0-9A-HJKMNP-TV-Z]{26}\.[A-Za-z0-9_-]{43}/', $contents);
            $this->assertStringNotContainsString("localStorage.setItem('refresh_token'", $contents);
        }
    }

    public function test_legacy_deprecation_and_four_locale_ui_keys_are_present(): void
    {
        $legacy = file_get_contents($this->path('documentations/manual/api/legacy-authentication.md'));
        foreach (['Deprecation', 'Sunset', 'Link'] as $header) $this->assertStringContainsString('`'.$header.'`', $legacy);

        foreach (['en', 'zh_CN', 'zh_TW', 'ja'] as $locale) {
            $words = file_get_contents($this->path('lang/'.$locale.'/word.php'));
            foreach (['api_sessions', 'api_service_tokens', 'api_security_policy', 'api_only_mode', 'api_legacy_authentication'] as $key) {
                $this->assertStringContainsString("'{$key}' =>", $words, "{$key} missing in {$locale}");
            }
        }
    }

    /** @return array<int, string> */
    private function machineTokens(string $path): array
    {
        preg_match_all('/`([^`\n]+)`/', file_get_contents($path), $matches);
        $tokens = array_values(array_unique($matches[1]));
        sort($tokens);
        return $tokens;
    }

    private function path(string $relative): string
    {
        return dirname(__DIR__, 4).'/'.$relative;
    }
}
