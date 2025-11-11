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
        // ① Methods from traits or this class
        if (method_exists($this, $method)) {
            return $this->$method(...$args);
        }

        // ② App Manager
        $class = 'App\\Services\\Managers\\' . ucfirst(Str::camel($method)) . 'Manager';
        if (class_exists($class)) {
            return new $class($this, ...$args);
        }

        // ③ Core Manager
        $class = 'Wncms\\Services\\Managers\\' . ucfirst(Str::camel($method)) . 'Manager';
        if (class_exists($class)) {
            return new $class($this, ...$args);
        }

        // ④ Registered Package Manager (via PackageMethods trait)
        if (method_exists($this, 'tryPackageProxy')) {
            $proxy = $this->tryPackageProxy($method, $args);
            if ($proxy) {
                return $proxy;
            }
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
        $studly = ucfirst(\Illuminate\Support\Str::camel($method));

        // ① App Managers (always highest priority)
        $class = "App\\Services\\Managers\\{$studly}Manager";
        if (class_exists($class)) {
            return new $class($this, ...$args);
        }

        // ② Core Managers
        $class = "Wncms\\Services\\Managers\\{$studly}Manager";
        if (class_exists($class)) {
            return new $class($this, ...$args);
        }

        // ③ Package-specific Manager (when package key is provided)
        if ($package) {
            $pkg = $this->getRegisteredPackages()[$package] ?? null;
            if ($pkg) {
                $managerMap = $pkg['manager_map'] ?? [];
                $alias = \Illuminate\Support\Str::lower($method);
                if (!empty($managerMap[$alias]) && class_exists($managerMap[$alias])) {
                    return new $managerMap[$alias]($this, ...$args);
                }
            }
        }

        // ④ Fallback: search any package (old tryPackageProxy behavior)
        if (method_exists($this, 'tryPackageProxy')) {
            $proxy = $this->tryPackageProxy($method, $args);
            if ($proxy) return $proxy;
        }

        return null;
    }
}
