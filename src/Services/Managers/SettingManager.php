<?php

namespace Wncms\Services\Managers;

use Wncms\Models\Setting;
use Illuminate\Support\Facades\Schema;

class SettingManager
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "setting";

    protected ?bool $hasGroupColumn = null;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get system setting by key
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @param string|array|null $key The key in database of table settings
     * @param string|array|null $fallback Return when key is not found or value is null
     * @param boolean $fromCache pass false to get value without cache
     * @return string|null
     * @example wncms()->system_seetings()->get('data_cache_time', 3600)
     * @alias gss('data_cache_time', 3600)
     * ----------------------------------------------------------------------------------------------------
     */
    function get(string $key, $fallback = null, $fromCache = true)
    {
        $parsed = $this->parseLookupKey($key);
        if (!$parsed['valid']) {
            return $fallback;
        }

        if (empty($fromCache)) {
            return $this->buildSettingQuery($parsed['key'], $parsed['group'])->first()?->value ?? $fallback;
        }

        $systemSettings = $this->getList();
        return $systemSettings[$parsed['lookup']] ?? $fallback;
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get system settings by multiple keys
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @param array|null $keys Return only specific keys. Return all keys if $keys is not set
     * @return array
     * @example wncms()->system_seetings()->getList()
     * ----------------------------------------------------------------------------------------------------
     */
    function getList(string|array|null $keys = [])
    {
        $method = "getList";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['settings'];
        //! gss() will call this getList funciton. Cannot use gss insite this
        $cacheTime = 3600;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($keys) {
            try {
                if (!wncms_is_installed()) return [];
                $q = Setting::query();

                if (!empty($keys)) {
                    if (is_string($keys)) {
                        $keys = explode(",", $keys);
                    }

                    $parsedKeys = collect((array) $keys)
                        ->map(fn($lookupKey) => $this->parseLookupKey((string) $lookupKey))
                        ->filter(fn($item) => $item['valid'])
                        ->values()
                        ->all();

                    if (empty($parsedKeys)) {
                        return [];
                    }

                    $q->where(function ($query) use ($parsedKeys) {
                        foreach ($parsedKeys as $item) {
                            $query->orWhere(function ($subQuery) use ($item) {
                                if (!$this->hasGroupColumn()) {
                                    $subQuery->where('key', $this->getStorageLookupKey($item['key'], $item['group']));
                                    return;
                                }

                                $subQuery->where('key', $item['key']);

                                if ($item['group'] === null) {
                                    $subQuery->where(function ($groupQuery) {
                                        $groupQuery->whereNull('group')->orWhere('group', '');
                                    });
                                } else {
                                    $subQuery->where('group', $item['group']);
                                }
                            });
                        }
                    });
                }

                $columns = $this->hasGroupColumn() ? ['key', 'value', 'group'] : ['key', 'value'];
                $settings = $q->get($columns);
                $result = [];

                foreach ($settings as $setting) {
                    if (!$this->hasGroupColumn()) {
                        $lookup = (string) $setting->key;
                    } else {
                        $lookup = !empty($setting->group)
                            ? "{$setting->group}:{$setting->key}"
                            : (string) $setting->key;
                    }

                    $result[$lookup] = $setting->value;
                }

                return $result;
            } catch (\Exception $e) {
                logger()->error($e);
                return [];
            }
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Update system setting by key
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @param string|array|null $key The key in database of table settings. Create key if key does not exist
     * @param string|array|null $value The value to be updated
     * @return boolean success = true, fail = false
     * @example wncms()->system_seetings()->update('version', '1.0.1');
     * @alias uss('system_name', "WNCMS")
     * ----------------------------------------------------------------------------------------------------
     */
    function update($key, $value)
    {
        $parsed = $this->parseLookupKey((string) $key);
        if (!$parsed['valid']) {
            return false;
        }

        if ($this->hasGroupColumn()) {
            $result = Setting::query()->updateOrCreate(
                [
                    'key' => $parsed['key'],
                    'group' => $this->normalizeGroupForStorage($parsed['group']),
                ],
                ['value' => $value]
            );
        } else {
            $result = Setting::query()->updateOrCreate(
                ['key' => $this->getStorageLookupKey($parsed['key'], $parsed['group'])],
                ['value' => $value]
            );
        }

        wncms()->cache()->flush(['settings']);
        return $result !== false;
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Delete system setting by key
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @param string|array|null $key The key in database of table settings.
     * @return int|boolean 
     *      success = number of data deleted
     *      fail = false
     * @example 
     *      wncms()->system_seetings()->delete('version'); //return false because version is core key
     *      wncms()->settings()->delete('test'); //return 1 if one key is delete
     * ----------------------------------------------------------------------------------------------------
     */
    function delete($key)
    {
        $parsed = $this->parseLookupKey((string) $key);
        if (!$parsed['valid']) {
            return false;
        }

        $core_keys = [
            'version',
            'active_models',
            'request_timeout',
        ];

        if ($parsed['group'] === null && in_array($parsed['key'], $core_keys)) {
            return false;
        }

        $result = $this->buildSettingQuery($parsed['key'], $parsed['group'])->delete();

        wncms()->cache()->tags(['settings'])->flush();

        return $result;
    }

    protected function parseLookupKey(string $lookupKey): array
    {
        $lookupKey = trim($lookupKey);

        if ($lookupKey === '' || str_starts_with($lookupKey, ':')) {
            return [
                'valid' => false,
                'lookup' => $lookupKey,
                'group' => null,
                'key' => '',
            ];
        }

        if (!str_contains($lookupKey, ':')) {
            return [
                'valid' => true,
                'lookup' => $lookupKey,
                'group' => null,
                'key' => $lookupKey,
            ];
        }

        [$group, $key] = explode(':', $lookupKey, 2);
        $group = trim($group);
        $key = trim($key);

        if ($group === '' || $key === '') {
            return [
                'valid' => false,
                'lookup' => $lookupKey,
                'group' => null,
                'key' => '',
            ];
        }

        return [
            'valid' => true,
            'lookup' => "{$group}:{$key}",
            'group' => $group,
            'key' => $key,
        ];
    }

    protected function buildSettingQuery(string $key, ?string $group)
    {
        if (!$this->hasGroupColumn()) {
            return Setting::query()->where('key', $this->getStorageLookupKey($key, $group));
        }

        $query = Setting::query()->where('key', $key);

        if ($group === null) {
            return $query->where(function ($q) {
                $q->whereNull('group')->orWhere('group', '');
            });
        }

        return $query->where('group', $this->normalizeGroupForStorage($group));
    }

    protected function normalizeGroupForStorage(?string $group): string
    {
        return trim((string) $group);
    }

    protected function getStorageLookupKey(string $key, ?string $group): string
    {
        if ($group === null || trim($group) === '') {
            return $key;
        }

        return trim($group) . ':' . $key;
    }

    protected function hasGroupColumn(): bool
    {
        if ($this->hasGroupColumn !== null) {
            return $this->hasGroupColumn;
        }

        try {
            $this->hasGroupColumn = Schema::hasColumn('settings', 'group');
        } catch (\Throwable $e) {
            $this->hasGroupColumn = false;
        }

        return $this->hasGroupColumn;
    }
}
