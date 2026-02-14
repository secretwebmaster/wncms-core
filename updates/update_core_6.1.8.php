<?php

use Illuminate\Support\Facades\DB;

$thisVersion = '6.1.8';

info("running update_{$thisVersion}.php");

try {
    $prefix = DB::getTablePrefix();
    $table = $prefix . 'settings';

    $indexes = DB::select("SHOW INDEX FROM `{$table}`");

    foreach ($indexes as $index) {
        $keyName = $index->Key_name ?? null;
        $columnName = $index->Column_name ?? null;
        $nonUnique = (int) ($index->Non_unique ?? 1);

        if ($nonUnique === 0 && $columnName === 'key' && !in_array($keyName, ['PRIMARY', 'settings_group_key_unique'], true)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$keyName}`");
        }
    }

    DB::statement("UPDATE `{$table}` SET `group` = '' WHERE `group` IS NULL");
    DB::statement("ALTER TABLE `{$table}` MODIFY `group` VARCHAR(255) NOT NULL DEFAULT ''");

    $indexes = DB::select("SHOW INDEX FROM `{$table}`");
    $hasCompositeUnique = collect($indexes)
        ->where('Key_name', 'settings_group_key_unique')
        ->where('Non_unique', 0)
        ->count() > 0;

    if (!$hasCompositeUnique) {
        DB::statement("ALTER TABLE `{$table}` ADD UNIQUE INDEX `settings_group_key_unique` (`group`, `key`)");
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info('Error: ' . $e->getMessage());
    return;
}
