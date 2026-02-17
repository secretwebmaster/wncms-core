<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InspectHookRegistry extends Command
{
    protected $signature = 'wncms:hook-list {--listeners : Show listener details for each hook} {--only-listened : Show only hooks that currently have listeners} {--json : Output registry data as JSON}';

    protected $description = 'List hook dispatch points and registered extensions for plugin development';

    public function handle(): int
    {
        $registry = $this->buildRegistry();

        if ((bool) $this->option('json')) {
            $this->line(json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return self::SUCCESS;
        }

        $this->line('WNCMS Hook / Extension Registry');
        $this->line('Hooks: ' . count($registry['hooks']) . ', Macros: ' . count($registry['extensions']['macros']));
        $this->newLine();

        $rows = [];
        foreach ($registry['hooks'] as $hook) {
            if ((bool) $this->option('only-listened') && empty($hook['listeners'])) {
                continue;
            }

            $rows[] = [
                $hook['name'],
                (string) count($hook['dispatch_points']),
                (string) count($hook['listeners']),
            ];
        }

        if (!empty($rows)) {
            $this->table(['Hook', 'Dispatch Points', 'Listeners'], $rows);
        } else {
            $this->line('No hooks matched the selected filters.');
        }

        if ((bool) $this->option('listeners')) {
            $this->newLine();
            $this->line('Listener Details');
            foreach ($registry['hooks'] as $hook) {
                if ((bool) $this->option('only-listened') && empty($hook['listeners'])) {
                    continue;
                }

                $this->line('- ' . $hook['name']);
                if (empty($hook['listeners'])) {
                    $this->line('  (none)');
                    continue;
                }

                foreach ($hook['listeners'] as $listener) {
                    $this->line('  - ' . $listener);
                }
            }
        }

        $this->newLine();
        $this->line('Registered Macros (Extension Registry)');
        if (empty($registry['extensions']['macros'])) {
            $this->line('(none)');
            return self::SUCCESS;
        }

        $macroRows = [];
        foreach ($registry['extensions']['macros'] as $macro) {
            $macroRows[] = [
                $macro['name'],
                implode(', ', $macro['models']),
                (string) count($macro['models']),
            ];
        }
        $this->table(['Macro', 'Models', 'Model Count'], $macroRows);

        return self::SUCCESS;
    }

    protected function buildRegistry(): array
    {
        $dispatchMap = $this->collectDispatchedHooks();
        $dispatcher = app('events');
        $hooks = [];

        foreach ($dispatchMap as $hookName => $dispatchPoints) {
            $listeners = $dispatcher->getListeners($hookName);

            $hooks[] = [
                'name' => $hookName,
                'dispatch_points' => $dispatchPoints,
                'listeners' => $this->normalizeListeners($listeners),
            ];
        }

        usort($hooks, fn(array $a, array $b) => $a['name'] <=> $b['name']);

        $macros = [];
        $registeredMacros = app('macroable-models')->getAllMacros();
        ksort($registeredMacros);

        foreach ($registeredMacros as $macroName => $modelClosures) {
            $models = array_keys((array) $modelClosures);
            sort($models);
            $macros[] = [
                'name' => (string) $macroName,
                'models' => $models,
            ];
        }

        return [
            'hooks' => $hooks,
            'extensions' => [
                'macros' => $macros,
            ],
        ];
    }

    protected function collectDispatchedHooks(): array
    {
        $paths = [];

        $coreSrcPath = realpath(__DIR__ . '/../../');
        if ($coreSrcPath && is_dir($coreSrcPath)) {
            $paths[] = $coreSrcPath;
        }

        $appPath = app_path();
        if (is_dir($appPath)) {
            $paths[] = $appPath;
        }

        $hooks = [];
        $seenPaths = [];

        foreach ($paths as $path) {
            $realPath = realpath($path);
            if (!$realPath || isset($seenPaths[$realPath])) {
                continue;
            }
            $seenPaths[$realPath] = true;

            foreach (File::allFiles($realPath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = File::get($file->getPathname());
                $lines = preg_split("/\r\n|\n|\r/", $content);
                if (!is_array($lines)) {
                    continue;
                }

                foreach ($lines as $lineNumber => $line) {
                    $lineMatches = [];

                    if (preg_match_all("/Event::dispatch\\(\\s*['\\\"]([^'\\\"]+)['\\\"]/", $line, $lineMatches)) {
                        foreach ((array) ($lineMatches[1] ?? []) as $eventName) {
                            $this->appendDispatchPoint($hooks, $eventName, $file->getPathname(), $lineNumber + 1);
                        }
                    }

                    $lineMatches = [];
                    if (preg_match_all("/\\bevent\\(\\s*['\\\"]([^'\\\"]+)['\\\"]/", $line, $lineMatches)) {
                        foreach ((array) ($lineMatches[1] ?? []) as $eventName) {
                            $this->appendDispatchPoint($hooks, $eventName, $file->getPathname(), $lineNumber + 1);
                        }
                    }
                }
            }
        }

        ksort($hooks);
        foreach ($hooks as &$points) {
            sort($points);
            $points = array_values(array_unique($points));
        }

        return $hooks;
    }

    protected function appendDispatchPoint(array &$hooks, string $eventName, string $filePath, int $lineNumber): void
    {
        $eventName = trim($eventName);
        if ($eventName === '') {
            return;
        }

        if (!isset($hooks[$eventName])) {
            $hooks[$eventName] = [];
        }

        $hooks[$eventName][] = $filePath . ':' . $lineNumber;
    }

    protected function normalizeListeners(array $listeners): array
    {
        $result = [];

        foreach ($listeners as $listener) {
            if ($listener instanceof \Closure) {
                $reflection = new \ReflectionFunction($listener);
                $file = (string) ($reflection->getFileName() ?: 'closure');
                $line = (int) $reflection->getStartLine();
                $result[] = 'Closure @ ' . $file . ':' . $line;
                continue;
            }

            if (is_array($listener) && count($listener) === 2) {
                $left = is_object($listener[0]) ? get_class($listener[0]) : (string) $listener[0];
                $right = (string) $listener[1];
                $result[] = $left . '@' . $right;
                continue;
            }

            if (is_string($listener)) {
                $result[] = $listener;
                continue;
            }

            if (is_object($listener)) {
                $result[] = get_class($listener);
                continue;
            }

            $result[] = gettype($listener);
        }

        $result = array_values(array_unique($result));
        sort($result);

        return $result;
    }
}
