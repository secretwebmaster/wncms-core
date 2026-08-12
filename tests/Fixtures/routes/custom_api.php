<?php

use Illuminate\Support\Facades\Route;

Route::get('v2/openapi.json', static fn (): array => ['source' => 'custom'])
    ->name('custom.openapi.override');

Route::get('custom/health', static fn (): array => ['source' => 'custom'])
    ->name('custom.health');

Route::get('{path}', static fn (string $path): array => ['path' => $path])
    ->where('path', '.*')
    ->name('custom.fallback');
