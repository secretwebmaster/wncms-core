<?php

namespace Wncms\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Wncms\Tests\TestCase;

class CheckBackendApiV2ParityCommandTest extends TestCase
{
    public function test_it_outputs_v7_ai_first_coverage_as_json(): void
    {
        $exitCode = Artisan::call('wncms:check-backend-api-v2-parity', [
            '--coverage' => true,
            '--json' => true,
        ]);

        $decoded = json_decode(trim(Artisan::output()), true);

        $this->assertSame(0, $exitCode);
        $this->assertIsArray($decoded);
        $this->assertSame('success', $decoded['status']);
        $this->assertSame('v7-ai-first-coverage', $decoded['meta']['mode']);
        $this->assertSame('links', $decoded['data']['reference_domain']);

        $domains = $this->indexDomainsByKey($decoded['data']['domains']);

        $this->assertArrayHasKey('links', $domains);
        $this->assertTrue($domains['links']['reference']);
        $this->assertSame('Complete', $domains['links']['surfaces']['backend_ui']['status']);
        $this->assertSame('Partial', $domains['links']['surfaces']['api_v2']['status']);
        $this->assertSame('Partial', $domains['links']['surfaces']['cli']['status']);
        $this->assertSame('Partial', $domains['links']['surfaces']['docs']['status']);
        $this->assertSame('Partial', $domains['links']['surfaces']['tests']['status']);
        $this->assertContains('documentations/manual/developer/command/overview.md', $domains['links']['surfaces']['docs']['found']);
        $this->assertContains('tests/Feature/LinkAutomationCommandTest.php', $domains['links']['surfaces']['tests']['found']);
        $this->assertSame('Missing', $domains['links']['surfaces']['mcp']['status']);

        $this->assertArrayHasKey('api_v2_backend_resources', $domains);
        $this->assertSame('Not applicable', $domains['api_v2_backend_resources']['surfaces']['backend_ui']['status']);
        $this->assertSame('Complete', $domains['api_v2_backend_resources']['surfaces']['cli']['status']);
        $this->assertSame('Needs design', $domains['api_v2_backend_resources']['surfaces']['mcp']['status']);
    }

    /**
     * Index decoded coverage domains by domain key.
     *
     * @param array $domains
     * @return array
     */
    protected function indexDomainsByKey(array $domains): array
    {
        $indexed = [];

        foreach ($domains as $domain) {
            $indexed[(string) $domain['key']] = $domain;
        }

        return $indexed;
    }
}
