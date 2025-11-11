<?php

namespace Wncms\Services\Core;

trait LogMethods
{
    public function log(string $message, string $level = 'debug'): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) continue;

            if (!str_contains($frame['file'], '/vendor/')) {
                $file = $frame['file'];
                $line = $frame['line'] ?? '?';
                \Log::log($level, "{$message} ({$file}:{$line})");
                return;
            }
        }

        \Log::log($level, "{$message} (unknown file)");
    }
}
