<?php

namespace Wncms\Services\Managers;

use Illuminate\Support\Facades\Schema;

class SettingManager
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "setting";

    protected ?bool $hasGroupColumn = null;

    /**
     * Resolve the active Setting model class.
     *
     * This respects `wncms.models.setting` overrides (app/package/core fallback).
     */
    public function getModelClass(): string
    {
        return wncms()->getModelClass('setting');
    }

    /**
     * Get one setting value by lookup key.
     *
     * Lookup key formats:
     * - `key`
     * - `group:key`
     *
     * @param string $key Lookup key.
     * @param mixed $fallback Returned when key is invalid or not found.
     * @param bool $fromCache Set false to bypass cached setting map.
     * @return mixed
     * @example wncms()->settings()->get('data_cache_time', 3600)
     * @example gss('data_cache_time', 3600)
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
     * Get settings map from cache/database.
     *
     * Returned array format is always `lookup_key => value`.
     * When `$keys` is empty, all settings are returned.
     *
     * @param string|array|null $keys Single key, comma-separated keys, or key array.
     * @return array<string, mixed>
     * @example wncms()->settings()->getList()
     * @example wncms()->settings()->getList(['data_cache_time', 'ui:theme'])
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
                $q = $this->query();

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
     * Create or update a setting value by lookup key.
     *
     * Supports grouped key format (`group:key`).
     *
     * @param string $key Lookup key.
     * @param mixed $value Value to be stored.
     * @return bool
     * @example wncms()->settings()->update('site_name', 'WNCMS')
     * @example uss('site_name', 'WNCMS')
     */
    function update($key, $value)
    {
        $parsed = $this->parseLookupKey((string) $key);
        if (!$parsed['valid']) {
            return false;
        }

        if ($this->hasGroupColumn()) {
            $result = $this->query()->updateOrCreate(
                [
                    'key' => $parsed['key'],
                    'group' => $this->normalizeGroupForStorage($parsed['group']),
                ],
                ['value' => $value]
            );
        } else {
            $result = $this->query()->updateOrCreate(
                ['key' => $this->getStorageLookupKey($parsed['key'], $parsed['group'])],
                ['value' => $value]
            );
        }

        wncms()->cache()->flush(['settings']);
        return $result !== false;
    }

    /**
     * Delete a setting by lookup key.
     *
     * Protected core keys (non-grouped) are blocked:
     * `version`, `active_models`, `request_timeout`.
     *
     * @param string $key Lookup key.
     * @return int|false Deleted row count, or false when blocked/invalid.
     * @example wncms()->settings()->delete('tmp_key')
     * @example wncms()->settings()->delete('ui:theme')
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

    /**
     * Normalize and validate lookup key into `{group, key}` parts.
     *
     * @param string $lookupKey
     * @return array{valid: bool, lookup: string, group: ?string, key: string}
     */
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

    /**
     * Build a setting query with grouped/non-grouped compatibility.
     *
     * @param string $key
     * @param string|null $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildSettingQuery(string $key, ?string $group)
    {
        if (!$this->hasGroupColumn()) {
            return $this->query()->where('key', $this->getStorageLookupKey($key, $group));
        }

        $query = $this->query()->where('key', $key);

        if ($group === null) {
            return $query->where(function ($q) {
                $q->whereNull('group')->orWhere('group', '');
            });
        }

        return $query->where('group', $this->normalizeGroupForStorage($group));
    }

    /**
     * Normalize group string before storing to database.
     */
    protected function normalizeGroupForStorage(?string $group): string
    {
        return trim((string) $group);
    }

    /**
     * Get storage key for legacy schema without `group` column.
     */
    protected function getStorageLookupKey(string $key, ?string $group): string
    {
        if ($group === null || trim($group) === '') {
            return $key;
        }

        return trim($group) . ':' . $key;
    }

    /**
     * Check whether resolved settings table has `group` column.
     */
    protected function hasGroupColumn(): bool
    {
        if ($this->hasGroupColumn !== null) {
            return $this->hasGroupColumn;
        }

        try {
            $table = (new ($this->getModelClass()))->getTable();
            $this->hasGroupColumn = Schema::hasColumn($table, 'group');
        } catch (\Throwable $e) {
            $this->hasGroupColumn = false;
        }

        return $this->hasGroupColumn;
    }

    /**
     * Start a new query from the resolved Setting model class.
     */
    protected function query()
    {
        $modelClass = $this->getModelClass();

        return $modelClass::query();
    }
}
