<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use RuntimeException;
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

    /**
     * Verify the command rejects missing or conflicting operation modes.
     *
     * @return void
     */
    public function test_it_requires_exactly_one_write_or_check_option(): void
    {
        $path = $this->temporaryDirectory.'/openapi-v2.json';

        $this->artisan('wncms:api-v2-openapi')
            ->expectsOutput('Provide exactly one of --write or --check.')
            ->assertExitCode(1);
        $this->artisan('wncms:api-v2-openapi', ['--write' => $path, '--check' => $path])
            ->expectsOutput('Provide exactly one of --write or --check.')
            ->assertExitCode(1);
    }

    /**
     * Verify a false filesystem write result cannot be reported as success.
     *
     * @return void
     */
    public function test_write_fails_when_the_filesystem_returns_false(): void
    {
        $filesystem = File::getFacadeRoot();
        File::swap(new class extends Filesystem
        {
            /**
             * Simulate a filesystem write failure without throwing.
             *
             * @param  string  $path
             * @param  string  $contents
             * @param  bool  $lock
             *
             * @return false
             */
            public function put($path, $contents, $lock = false)
            {
                return false;
            }
        });

        try {
            $this->artisan('wncms:api-v2-openapi', ['--write' => $this->temporaryDirectory.'/openapi-v2.json'])
                ->expectsOutputToContain('Unable to write OpenAPI snapshot')
                ->assertExitCode(1);
        } finally {
            File::swap($filesystem);
        }
    }

    /**
     * Verify a filesystem exception cannot escape or be reported as success.
     *
     * @return void
     */
    public function test_write_fails_when_the_filesystem_throws(): void
    {
        $filesystem = File::getFacadeRoot();
        File::swap(new class extends Filesystem
        {
            /**
             * Simulate a filesystem write exception.
             *
             * @param  string  $path
             * @param  string  $contents
             * @param  bool  $lock
             *
             * @return never
             */
            public function put($path, $contents, $lock = false)
            {
                throw new RuntimeException('Simulated filesystem failure.');
            }
        });

        try {
            $this->artisan('wncms:api-v2-openapi', ['--write' => $this->temporaryDirectory.'/openapi-v2.json'])
                ->expectsOutputToContain('Unable to write OpenAPI snapshot')
                ->assertExitCode(1);
        } finally {
            File::swap($filesystem);
        }
    }

    /**
     * Verify directory paths fail cleanly in both command modes.
     *
     * @return void
     */
    public function test_directory_targets_fail_cleanly(): void
    {
        $this->artisan('wncms:api-v2-openapi', ['--write' => $this->temporaryDirectory])
            ->expectsOutputToContain('Unable to write OpenAPI snapshot')
            ->assertExitCode(1);
        $this->artisan('wncms:api-v2-openapi', ['--check' => $this->temporaryDirectory])
            ->expectsOutputToContain('Unable to check OpenAPI snapshot')
            ->assertExitCode(1);
    }
}
