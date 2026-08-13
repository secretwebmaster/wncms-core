<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Wncms\Auth\Api\V2\RefreshTokenConsumer;
use Wncms\Auth\Api\V2\RefreshTokenReuseException;
use Wncms\Models\ApiRefreshToken;

[$script, $root, $database, $contender, $tokenId] = $argv;

require $root.'/vendor/autoload.php';

$capsule = new Capsule();
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => $database,
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$capsule->getConnection()->statement('PRAGMA busy_timeout=10000');

$observed = ApiRefreshToken::query()->whereKey((int) $tokenId)->whereNull('consumed_at')->exists();
if (!$observed) {
    fwrite(STDERR, 'contender did not observe an unconsumed token');
    exit(2);
}

$capsule->getConnection()->table('refresh_race_barrier')->insert([
    'contender' => $contender,
    'state' => 'ready',
]);
$deadline = microtime(true) + 10;
while (!$capsule->getConnection()->table('refresh_race_barrier')->where('state', 'start')->exists() && microtime(true) < $deadline) {
    usleep(10_000);
}
if (!$capsule->getConnection()->table('refresh_race_barrier')->where('state', 'start')->exists()) {
    fwrite(STDERR, 'barrier timeout');
    exit(3);
}

try {
    (new RefreshTokenConsumer())->consume(
        ApiRefreshToken::class,
        (int) $tokenId,
        'replacement-'.$contender,
        CarbonImmutable::now('UTC'),
    );
    fwrite(STDOUT, 'success');
} catch (RefreshTokenReuseException $exception) {
    fwrite(STDOUT, 'reuse');
}
