<?php

namespace Wncms\Services;

use Illuminate\Support\Str;


class Wncms
{
    use \Wncms\Services\Core\DomainMethods;
    use \Wncms\Services\Core\LocaleMethods;
    use \Wncms\Services\Core\LogMethods;
    use \Wncms\Services\Core\ModelMethods;
    use \Wncms\Services\Core\PackageMethods;
    use \Wncms\Services\Core\PathMethods;
    use \Wncms\Services\Core\RouteMethods;
    use \Wncms\Services\Core\UtilityMethods;
    use \Wncms\Services\Core\VersionMethods;
    use \Wncms\Services\Core\ViewMethods;
    use \Wncms\Services\Core\WebsiteMethods;

    protected array $customProperties = [];
    protected array $resolvedManagers = [];

    public function __construct()
    {
        // Nothing heavy — traits are attached statically.
    }

    /**
     * Magic method for dynamic method resolution.
     *
     * Resolves in order:
     *  1. Methods from traits (handled automatically by PHP)
     *  2. App\Services\Managers\{Xxx}Manager
     *  3. Wncms\Services\Managers\{Xxx}Manager
     *  4. Registered packages via registerPackage()
     */
    public function __call($method, $args)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$args);
        }

        $manager = $this->resolveManager($method, $args);
        if ($manager) {
            return $manager;
        }

        throw new \RuntimeException("Undefined method or manager [{$method}] in Wncms.");
    }


    /**
     * Magic getter for dynamic properties.
     */
    public function __get($name)
    {
        return $this->customProperties[$name] ?? null;
    }

    /**
     * Magic setter for dynamic properties.
     */
    public function __set($name, $value)
    {
        $this->customProperties[$name] = $value;
    }

    protected function resolveManager(string $method, array $args = [], ?string $package = null)
    {
        $managerKey = Str::snake($method);

        if (empty($args) && isset($this->resolvedManagers[$managerKey])) {
            return $this->resolvedManagers[$managerKey];
        }

        foreach ($this->getManagerStudlyCandidates($method) as $studly) {
            // App Managers
            $class = "App\\Services\\Managers\\{$studly}Manager";
            if (class_exists($class)) {
                $manager = $this->instantiateManager($class, $args);
                if (empty($args)) {
                    $this->resolvedManagers[$managerKey] = $manager;
                }
                return $manager;
            }

            // Core Managers
            $class = "Wncms\\Services\\Managers\\{$studly}Manager";
            if (class_exists($class)) {
                $manager = $this->instantiateManager($class, $args);
                if (empty($args)) {
                    $this->resolvedManagers[$managerKey] = $manager;
                }
                return $manager;
            }
        }

        // Package-specific Manager
        if ($package) {
            $pkg = $this->getRegisteredPackages()[$package] ?? null;
            if ($pkg) {
                $managerMap = $pkg['manager_map'] ?? [];
                $aliases = $this->getManagerAliasCandidates($method);

                foreach ($aliases as $alias) {
                    if (empty($managerMap[$alias]) || !class_exists($managerMap[$alias])) {
                        continue;
                    }

                    $pkgClass = $managerMap[$alias];
                    $manager = $this->instantiateManager($pkgClass, $args);
                    if (empty($args)) {
                        $this->resolvedManagers[$managerKey] = $manager;
                    }
                    return $manager;
                }
            }
        }

        // Fallback
        if (method_exists($this, 'tryPackageProxy')) {
            $proxy = $this->tryPackageProxy($method, $args);
            if ($proxy) return $proxy;
        }

        return null;
    }

    protected function instantiateManager(string $class, array $args = []): mixed
    {
        try {
            return app()->make($class);
        } catch (\Throwable $e) {
            return new $class($this, ...$args);
        }
    }

    protected function getManagerStudlyCandidates(string $method): array
    {
        return collect($this->getManagerAliasCandidates($method))
            ->map(fn(string $alias) => Str::studly($alias))
            ->unique()
            ->values()
            ->all();
    }

    protected function getManagerAliasCandidates(string $method): array
    {
        $alias = Str::snake($method);

        return collect([
            $alias,
            Str::singular($alias),
            Str::plural($alias),
        ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
