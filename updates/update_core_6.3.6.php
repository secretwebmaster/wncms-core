<?php

$thisVersion = '6.3.6';

info("running update_{$thisVersion}.php");

try {
    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info('Error: ' . $e->getMessage());
    return;
}
