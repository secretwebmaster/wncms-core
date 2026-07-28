<?php

namespace Wncms\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wncms\Tests\TestCase;

class PersonalAccessTokensMigrationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Verify rolling back a skipped migration preserves a host-owned token table.
     *
     * @return void
     */
    public function test_rollback_preserves_an_existing_personal_access_tokens_table(): void
    {
        $originalConnection = config('database.default');
        $connection = 'personal_access_tokens_migration_regression';
        config([
            'database.connections.' . $connection => array_merge(
                config('database.connections.' . $originalConnection),
                ['database' => ':memory:']
            ),
            'database.default' => $connection,
        ]);
        DB::purge($connection);

        try {
            Schema::create('personal_access_tokens', function (Blueprint $table): void {
                $table->id();
                $table->string('host_marker');
            });

            $migration = require __DIR__ . '/../../database/migrations/0001_01_01_000040_create_personal_access_tokens_table.php';
            $migration->up();
            $migration->down();

            $this->assertTrue(Schema::hasTable('personal_access_tokens'));
            $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'host_marker'));
        } finally {
            DB::disconnect($connection);
            DB::purge($connection);
            config(['database.default' => $originalConnection]);
        }
    }
}
