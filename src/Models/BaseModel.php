<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Secretwebmaster\LaravelOptionable\Traits\HasOptions;
use Wncms\Interfaces\BaseModelInterface;
use Wncms\Tags\HasTags;
use Wncms\Traits\HasMultisite;

abstract class BaseModel extends Model implements BaseModelInterface
{
    use HasOptions;
    use HasMultisite;
    use HasTags;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $packageId = 'wncms';

    public static $modelKey = '';

    /**
     * Tag meta definition for this model.
     *
     * Example:
     * [
     *     [
     *         'key'   => 'product_category',
     *         'short' => 'category',
     *         'route' => 'frontend.products.tag',
     *     ],
     *     ...
     * ]
     *
     * If a model does not define tagging, leave this empty.
     */
    protected static array $tagMetas = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const SORTS = [
        'id',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Contracts
     * ----------------------------------------------------------------------------------------------------
     */
    public static function getModelKey(): string
    {
        return static::$modelKey;
    }

    /**
     * Resolve model multi-website mode with runtime overrides.
     */
    public static function getMultiWebsiteMode(): string
    {
        $modelKey = (string) str(static::getModelKey() ?: class_basename(static::class))->singular()->snake();
        $mode = config("wncms.models.$modelKey.website_mode", config("wncms.model_website_modes.$modelKey", 'global'));

        if (function_exists('gss')) {
            $raw = gss('model_website_modes', '{}');
            $overrides = is_array($raw) ? $raw : json_decode((string) $raw, true);

            if (is_array($overrides)) {
                foreach ($overrides as $key => $overrideMode) {
                    $normalizedKey = (string) str((string) $key)->singular()->snake();
                    if ($normalizedKey === $modelKey && in_array($overrideMode, ['global', 'single', 'multi'], true)) {
                        $mode = $overrideMode;
                        break;
                    }
                }
            }
        }

        return in_array($mode, ['global', 'single', 'multi'], true) ? $mode : 'global';
    }

    /**
     * Check if model website mode requires website scoping.
     */
    public static function isWebsiteScopedModel(): bool
    {
        return in_array(static::getMultiWebsiteMode(), ['single', 'multi'], true);
    }

    public function isActive(): bool
    {
        if (!function_exists('wncms')) {
            return true;
        }

        try {
            return (bool) wncms()->isModelActive(static::class);
        } catch (\Throwable $e) {
            return true;
        }
    }

    public static function getTagMeta(): array
    {
        $raw = static::$tagMetas ?? [];

        if (empty($raw)) {
            return [];
        }

        $package = static::getPackageId();
        $modelClass = static::class;

        $metas = [];

        foreach ($raw as $meta) {
            $metas[] = array_merge($meta, [
                'model' => $modelClass,
                'model_key' => static::getModelKey(),
                'package' => $package,
                'label' => $package . "::word." . $meta['key'],
            ]);
        }

        return $metas;
    }


    /**
     * Boot logic executed after model is initialized.
     * 1. Ensure modelKey exists.
     * 2. Register tag meta (if any) into TagManager.
     */
    protected static function booted()
    {
        if (empty(static::$modelKey)) {
            throw new \Exception(static::class . ' must define public static $modelKey.');
        }
    }

    /**
     * Get the short, un-namespaced model name.
     *
     * @return string
     */
    public function getParentModelName(): string
    {
        return class_basename($this);
    }

    /**
     * Override getAttribute to support ModelGettingAttribute event
     * and prevent double casting for arrays/objects.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (array_key_exists($key, $this->attributes)) {
            $event = new \Wncms\Events\ModelGettingAttribute($this, $key, $value);
            event($event);
            $value = $event->value;
        }

        if ($this->hasCast($key)) {
            if (is_string($value) || is_null($value)) {
                return $this->castAttribute($key, $value);
            }
            return $value;
        }

        return $value;
    }

    /**
     * Get human-friendly model display name.
     * Priority:
     *  1. User override via gss()
     *  2. Package translation
     *  3. Core translation
     *  4. Fallback formatted class name
     *
     * @param string|null $locale
     * @return string
     */
    public static function getModelName(?string $locale = null): string
    {
        $short = strtolower(class_basename(static::class));
        $packageId = static::getPackageId();

        if (function_exists('gss') && $packageId) {
            $override = gss("{$packageId}::{$short}_model_name");
            if (!empty($override)) {
                return $override;
            }
        }

        if ($packageId) {
            $translated = __("{$packageId}::word.{$short}", locale: $locale);
            if ($translated !== "{$packageId}::word.{$short}") {
                return $translated;
            }
        }

        $core = __("wncms::word.{$short}", locale: $locale);
        if ($core !== "wncms::word.{$short}") {
            return $core;
        }

        return ucfirst(str_replace('_', ' ', $short));
    }

    /**
     * Get the package ID for this model.
     * Returns null if the model does not define a $packageId property.
     *
     * @return string|null
     */
    public static function getPackageId(): ?string
    {
        return property_exists(static::class, 'packageId')
            ? static::$packageId
            : null;
    }

    /**
     * Normalize ROUTES definitions into a consistent structure.
     *
     * Supported formats:
     * 1) ['index', 'summary']
     * 2) [
     *      ['name' => 'clicks.index', 'permission' => 'click_index'],
     *      ['name' => 'summary', 'permission' => 'click_index'],
     *    ]
     *
     * @return array<int, array{name:string,suffix:string,permission:string}>
     */
    public static function getNormalizedRoutes(): array
    {
        $model = new static;
        $tableName = $model->getTable();
        $modelKey = static::getModelKey() ?: class_basename(static::class);
        $snakeName = Str::snake(Str::singular((string) $modelKey));

        $routes = defined(static::class . '::ROUTES') ? static::ROUTES : [];
        if (!is_array($routes)) {
            return [];
        }

        $normalized = [];
        foreach ($routes as $route) {
            $rawName = null;
            $permission = null;

            if (is_string($route)) {
                $rawName = $route;
            } elseif (is_array($route)) {
                $rawName = isset($route['name']) && is_string($route['name']) ? $route['name'] : null;
                $permission = isset($route['permission']) && is_string($route['permission']) ? $route['permission'] : null;
            }

            if (empty($rawName)) {
                continue;
            }

            $fullRouteName = str_contains($rawName, '.') ? $rawName : $tableName . '.' . $rawName;
            $suffix = str_contains($rawName, '.') ? (string) Str::afterLast($rawName, '.') : $rawName;
            $permission ??= $snakeName . '_' . $suffix;

            $normalized[] = [
                'name' => $fullRouteName,
                'suffix' => $suffix,
                'permission' => $permission,
            ];
        }

        return $normalized;
    }
}
