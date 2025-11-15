<?php

namespace Wncms\Services\Core;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Wncms\Models\Package;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


trait PackageMethods
{
    protected array $packages = [];
    protected array $packageMenus = [];

    /**
     * Register a package and auto-load its models, managers, and menus.
     *
     * Supports multiple models under the same package.
     *
     * @param string $packageId
     * @param array  $packageFiles
     */
    public function registerPackage(string $packageId, array $packageFiles): void
    {
        $packageId = Str::lower($packageId);

        // Translate info fields
        if (!empty($packageFiles['info'])) {
            foreach ($packageFiles['info'] as $key => $value) {
                $packageFiles['info'][$key] = $this->translatePackageInfo($value, $packageId);
            }
        }

        // Ensure base structure
        $paths = $packageFiles ?? [];

        // Directly store everything — no model_map or manager_map
        $this->packages[$packageId] = [
            'info' => $packageFiles['info']        ?? [],
            'base' => $paths['base']        ?? base_path(),
            'models' => $paths['models']      ?? [],
            'managers' => $paths['managers']    ?? [],
            'controllers' => $paths['controllers'] ?? [],
            'menus' => $packageFiles['menus']       ?? [],
            'permissions' => $packageFiles['permissions'] ?? [],
            'seeders' => $packageFiles['seeders']     ?? [],
        ];

        // Register models to wncms
        foreach ($this->packages[$packageId]['models'] as $alias => $class) {
            if (class_exists($class)) {
                wncms()->registerModel($class);
            }
        }

        /**
         * ===============================
         * Build Menus
         * ===============================
         */
        $menus = [];

        if (!empty($packageFiles['menus']) && is_array($packageFiles['menus'])) {
            $menus = $this->translateMenuItems($packageFiles['menus'], $packageId);
        }

        $icon = $packageFiles['info']['icon'] ?? 'fa-solid fa-box';

        if (!empty($menus)) {
            $this->packageMenus[$packageId] = [
                'title' => $this->translatePackageInfo($packageFiles['info']['name'] ?? ucfirst($packageId), $packageId),
                'icon' => $icon,
                'menus' => $menus,
                'permission' => $packageFiles['permissions'][0] ?? null,
            ];
        }
    }

    /**
     * Recursively translate all menu or item names in a package’s menu definition.
     */
    protected function translateMenuItems(array $items, string $packageId): array
    {
        $translated = [];

        foreach ($items as $item) {
            $translatedItem = $item;

            // Translate 'name' array or string
            if (!empty($item['name'])) {
                $translatedItem['name'] = $this->translatePackageInfo($item['name'], $packageId);
            }

            // Recursively process nested items
            if (!empty($item['items']) && is_array($item['items'])) {
                $translatedItem['items'] = $this->translateMenuItems($item['items'], $packageId);
            }

            $translated[] = $translatedItem;
        }

        return $translated;
    }

    /**
     * Get all registered packages.
     */
    public function getRegisteredPackages(): array
    {
        return $this->packages;
    }

    /**
     * Check if a package is registered in WNCMS.
     */
    public function hasPackage(string $packageId): bool
    {
        $packageId = Str::lower($packageId);
        return array_key_exists($packageId, $this->packages);
    }

    /**
     * Check if a package is active (cached for performance).
     */
    public function isPackageActive(string $packageId): bool
    {
        $packageId = Str::lower($packageId);
        $activePackages = $this->getActivePackageIds();
        return in_array($packageId, $activePackages, true);
    }

    /**
     * Get all active package IDs and cache the result.
     *
     * @param int $seconds Cache duration in seconds (default: 600 = 10 minutes)
     * @return array
     */
    public function getActivePackageIds(int $seconds = 600): array
    {
        $cacheKey = 'wncms_active_packages';

        // dd(wncms()->cache()->has($cacheKey));

        return wncms()->cache()->remember($cacheKey, $seconds, function () {
            return Package::where('status', 'active')->pluck('package_id')->map(fn($id) => strtolower($id))->toArray();
        });
    }

    public function getPackageMenus(): array
    {
        $result = [];
        $user = Auth::user();
        $currentRoute = Route::currentRouteName();

        foreach ($this->packageMenus as $packageId => $menuData) {

            if($this->isPackageActive($packageId) === false){
                continue;
            }

            $title      = $menuData['title'] ?? ucfirst($packageId);
            $icon       = $menuData['icon'] ?? 'fa-solid fa-box';
            $permission = $menuData['permission'] ?? null;
            $menus      = $menuData['menus'] ?? [];

            if ($permission && $user && Gate::denies($permission, $user)) {
                continue;
            }

            $items = [];
            $isActive = false;

            foreach ($menus as $group) {
                // Case 1: Direct route group
                if (!empty($group['route'])) {
                    $perm = $group['permission'] ?? $permission;
                    if (!$perm || ($user && Gate::allows($perm, $user))) {
                        $items[] = [
                            'name' => $group['name'] ?? ucfirst($packageId),
                            'route' => $group['route'],
                        ];

                        if (request()->routeIs($group['route'] . '*')) {
                            $isActive = true;
                        }
                    }
                }

                // Case 2: Nested "items"
                if (!empty($group['items'])) {
                    foreach ($group['items'] as $subItem) {
                        $perm = $subItem['permission'] ?? null;
                        if (!$perm || ($user && Gate::allows($perm, $user))) {
                            $items[] = [
                                'name' => $subItem['name'] ?? ucfirst($packageId),
                                'route' => $subItem['route'] ?? null,
                            ];

                            if (!empty($subItem['route']) && request()->routeIs($subItem['route'] . '*')) {
                                $isActive = true;
                            }
                        }
                    }
                }
            }

            if (empty($items)) {
                continue;
            }

            $result[] = [
                'title' => $title,
                'icon' => $icon,
                'items' => $items,
                'is_active' => $isActive,
            ];
        }

        return $result;
    }

    /**
     * Resolve a manager class from a given package.
     */
    protected function resolvePackageManager(string $packageId, array $args = [])
    {
        $packageId = Str::lower($packageId);

        if (empty($this->packages[$packageId])) {
            return null;
        }

        $package = $this->packages[$packageId];
        $managerPath = $package['managers'] ?? null;

        if (!$managerPath || !is_dir($managerPath)) {
            return null;
        }

        $class = $this->findManagerClass($managerPath, $packageId);
        return $class && class_exists($class) ? new $class($this) : null;
    }

    protected function tryPackageProxy(string $method, array $args = [])
    {
        return $this->resolvePackageManager($method, $args);
    }

    /**
     * Load all model classes from a given directory and register them under the package.
     *
     * @param  string  $path
     * @param  string  $packageId
     * @return array   alias => class map
     */
    protected function loadModelsFrom(string $path, string $packageId): array
    {
        $modelMap = [];

        if (!is_dir($path)) {
            return $modelMap; // Always return an array, even if invalid path
        }

        foreach (glob($path . '/*.php') as $file) {
            $className = $this->getClassFullNameFromFile($file);

            if (!class_exists($className)) {
                continue;
            }

            // Convert class name like "NovelChapter" -> "novel_chapter"
            $alias = Str::snake(class_basename($className));

            // Store in global registry
            $this->packages[$packageId]['model_map'][$alias] = $className;

            $modelMap[$alias] = $className;
        }

        return $modelMap;
    }

    /**
     * Extract full class name (with namespace) from PHP file.
     */
    protected function getClassFullNameFromFile(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        $namespace = '';
        $class = '';

        if (preg_match('/^namespace\s+(.+?);/m', $contents, $matches)) {
            $namespace = trim($matches[1]);
        }

        if (preg_match('/^class\s+(\w+)/m', $contents, $matches)) {
            $class = trim($matches[1]);
        }

        return $class ? $namespace . '\\' . $class : null;
    }

    protected function loadManagersFrom(string $path, string $packageId): void
    {
        foreach (glob(rtrim($path, '/') . '/*.php') as $file) {
            $class = $this->getClassFromFile($file);
            if ($class && class_exists($class)) {
                $alias = Str::lower(str_replace('manager', '', class_basename($class)));
                $this->packages[$packageId]['manager_map'][$alias] = $class;
            }
        }
    }

    public function findManagerClass(string $managerPath, string $packageId): ?string
    {
        $studly = Str::studly($packageId);
        $expected = "{$managerPath}/{$studly}Manager.php";

        if (file_exists($expected)) {
            return $this->getClassFromFile($expected);
        }

        $first = glob("{$managerPath}/*.php")[0] ?? null;
        return $first ? $this->getClassFromFile($first) : null;
    }

    protected function getClassFromFile(string $file): ?string
    {
        $content = file_get_contents($file);

        if (
            preg_match('/namespace\s+([^;]+);/m', $content, $ns)
            && preg_match('/class\s+([^\s]+)/m', $content, $class)
        ) {
            return trim($ns[1]) . '\\' . trim($class[1]);
        }

        return null;
    }

    /**
     * Activate and migrate a package.
     */
    public function activatePackage(string $packageId)
    {
        $packageId = strtolower($packageId);
        $registered = $this->getRegisteredPackages()[$packageId] ?? null;

        if (!$registered || empty($registered['base'])) {
            throw new \Exception("Package [{$packageId}] not registered or missing base path.");
        }

        $info = $registered['info'] ?? [];
        $paths = $registered ?? [];
        $basePath = rtrim($paths['base'], '/');

        // Run migrations
        $migrationPath = "{$basePath}/database/migrations";
        if (is_dir($migrationPath)) {
            Artisan::call('migrate', [
                '--path' => str_replace(base_path() . '/', '', $migrationPath),
                '--force' => true,
            ]);
        }

        // Run defined seeders (if any)
        $seeders = $registered['seeders'] ?? [];

        if (!empty($seeders)) {
            foreach ($seeders as $seederClass) {
                if (class_exists($seederClass)) {
                    try {
                        Artisan::call('db:seed', [
                            '--class' => $seederClass,
                            '--force' => true,
                        ]);
                    } catch (\Throwable $e) {
                        info("Error running seeder [{$seederClass}]: " . $e->getMessage());
                    }
                }
            }
        }

        // Sync permissions
        $permissions = $registered['permissions'] ?? [];
        $roles = Role::whereIn('name', ['superadmin', 'admin'])->get();

        foreach ($permissions as $permission) {
            $perm = Permission::firstOrCreate(['name' => $permission]);
            foreach ($roles as $role) {
                $role->givePermissionTo($perm);
            }
        }

        // Record in DB
        $package = Package::updateOrCreate(
            ['package_id' => $packageId],
            [
                'name' => $info['name'] ?? ucfirst($packageId),
                'description' => $info['description'] ?? '',
                'version' => $info['version'] ?? '1.0.0',
                'author' => $info['author'] ?? '',
                'path' => $basePath,
                'status' => 'active',
            ]
        );

        wncms()->cache()->forget('wncms_active_packages');

        return $package;
    }

    /**
     * Deactivate and rollback a package.
     */
    public function deactivatePackage(string $packageId)
    {
        $packageId = strtolower($packageId);
        $registered = $this->getRegisteredPackages()[$packageId] ?? null;

        if (!$registered || empty($registered['base'])) {
            throw new \Exception("Package [{$packageId}] not registered or missing base path.");
        }

        $basePath = rtrim($registered['base'], '/');

        // Rollback migrations
        $migrationPath = "{$basePath}/database/migrations";

        if (is_dir($migrationPath)) {
            $migrationFiles = glob($migrationPath . '/*.php');

            // Disable foreign key checks to avoid constraint errors
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            foreach ($migrationFiles as $file) {
                $migrationName = basename($file, '.php');

                try {
                    $migrationObject = include $file;

                    if ($migrationObject instanceof \Illuminate\Database\Migrations\Migration) {
                        $migrationObject->down();

                        // Remove migration record so activation re-runs it
                        DB::table('migrations')->where('migration', $migrationName)->delete();
                    }
                } catch (\Throwable $e) {
                    // Silently continue on error
                }
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // Remove permissions for this package
        $permissions = $registered['permissions'] ?? [];

        if (!empty($permissions)) {
            $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');

            // Clean related pivot records first
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();

            // Delete the permissions themselves
            Permission::whereIn('id', $permissionIds)->delete();
        }

        // Mark package as inactive
        $package = Package::where('package_id', $packageId)->first();
        if ($package) {
            $package->update(['status' => 'inactive']);
        }

        wncms()->cache()->forget('wncms_active_packages');

        return $package;
    }

    /**
     * Access a specific package instance for manager, model, or controller calls.
     */
    public function package(string $packageId)
    {
        $packageId = Str::lower($packageId);

        if (empty($this->packages[$packageId])) {
            throw new \Exception("Package [{$packageId}] not registered.");
        }

        return new class($this, $packageId)
        {
            protected $wncms;
            protected string $packageId;

            public function __construct($wncms, string $packageId)
            {
                $this->wncms = $wncms;
                $this->packageId = $packageId;
            }

            /**
             * Dynamic proxy:
             *   ->novel()       → manager instance
             *   ->model('novel') → model class name
             *   ->controller('novel') → controller class name
             */
            public function __call(string $method, array $args)
            {
                $package = $this->wncms->getRegisteredPackages()[$this->packageId] ?? null;

                if (!$package) {
                    throw new \Exception("Package [{$this->packageId}] not found.");
                }

                $paths = $package ?? [];
                $alias = Str::snake($method);

                // Try MANAGER
                $managers = $paths['managers'] ?? [];

                if (!empty($managers[$alias]) && class_exists($managers[$alias])) {
                    return new $managers[$alias]($this->wncms);
                }

                // MODEL access (explicit call: ->model('novel'))
                if ($method === 'model' && !empty($args[0])) {
                    $key = Str::snake($args[0]);
                    $models = $paths['models'] ?? [];
                    if (!empty($models[$key]) && class_exists($models[$key])) {
                        return $models[$key];
                    }
                    throw new \RuntimeException("Model [{$key}] not found in package [{$this->packageId}].");
                }

                // CONTROLLER access (explicit call: ->controller('novel'))
                if ($method === 'controller' && !empty($args[0])) {
                    $key = Str::snake($args[0]);
                    $controllers = $paths['controllers'] ?? [];
                    if (!empty($controllers[$key]) && class_exists($controllers[$key])) {
                        return $controllers[$key];
                    }
                    throw new \RuntimeException("Controller [{$key}] not found in package [{$this->packageId}].");
                }

                // Not found
                throw new \BadMethodCallException("No manager, model, or controller found for [{$method}] in package [{$this->packageId}].");
            }
        };
    }

    /**
     * Translate a single package field (universal helper).
     *
     * Behavior:
     * - If the value is a plain string → return as-is.
     * - If the value is an array of translations:
     *      1. Use current locale if available.
     *      2. Fallback to app fallback locale (config('app.fallback_locale')).
     *      3. Fallback to the first defined translation.
     * - If none applies → return the packageId itself (last resort).
     */
    protected function translatePackageInfo(mixed $field, ?string $packageId = null): mixed
    {
        $currentLocale  = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale', 'en');

        // --- Case 1: plain string
        if (is_string($field)) {
            return $field;
        }

        // --- Case 2: translation array
        if (is_array($field)) {
            if (array_key_exists($currentLocale, $field)) {
                return $field[$currentLocale];
            }

            if (array_key_exists($fallbackLocale, $field)) {
                return $field[$fallbackLocale];
            }

            return reset($field); // fallback to first available translation
        }

        // --- Case 3: invalid / null
        return $packageId ?? '';
    }
}
