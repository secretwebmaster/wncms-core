<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Support\Facades\File;
use Wncms\Api\V2\OpenApiDocumentBuilder;
use Wncms\Tests\TestCase;

class GenerateApiV2OpenApiCommandTest extends TestCase
{
    protected string $temporaryDirectory;

    /**
     * Create an isolated directory for command output.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = sys_get_temp_dir().'/wncms-openapi-'.uniqid();
        File::ensureDirectoryExists($this->temporaryDirectory);
    }

    /**
     * Remove command output after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryDirectory);

        parent::tearDown();
    }

    /**
     * Verify the write option creates normalized pretty JSON with one trailing newline.
     *
     * @return void
     */
    public function test_write_creates_a_normalized_openapi_snapshot(): void
    {
        $path = $this->temporaryDirectory.'/nested/openapi-v2.json';

        $this->artisan('wncms:api-v2-openapi', ['--write' => $path])
            ->assertExitCode(0);

        $contents = File::get($path);
        $expected = json_encode(
            app(OpenApiDocumentBuilder::class)->build(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        )."\n";

        $this->assertSame($expected, $contents);
        $this->assertStringEndsWith("\n", $contents);
        $this->assertFalse(str_ends_with($contents, "\n\n"));
    }

    /**
     * Verify the check option compares the generated document byte for byte.
     *
     * @return void
     */
    public function test_check_passes_only_for_an_exact_generated_snapshot(): void
    {
        $path = $this->temporaryDirectory.'/openapi-v2.json';

        $this->artisan('wncms:api-v2-openapi', ['--write' => $path])
            ->assertExitCode(0);
        $this->artisan('wncms:api-v2-openapi', ['--check' => $path])
            ->assertExitCode(0);

        File::append($path, "\n");

        $this->artisan('wncms:api-v2-openapi', ['--check' => $path])
            ->assertExitCode(1);
    }
}
