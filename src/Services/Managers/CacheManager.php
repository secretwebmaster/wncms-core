<?php

namespace Wncms\Services\Managers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class CacheManager
{
    /**
     * Get a value from cache by key.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return cache()->get($key);
    }

    /**
     * Put a value into the cache with optional tags.
     *
     * @param string $key
     * @param mixed $value
     * @param int $seconds
     * @param array|string|null $tags
     * @return bool
     */
    public function put(string $key, mixed $value, int $seconds = 0, array|string|null $tags = null): bool
    {
        return $this->tags($tags)->put($key, $value, $seconds);
    }

    /**
     * Forget a cache entry, optionally within tags.
     *
     * @param string|null $key
     * @param array|string|null $tags
     * @return bool
     */
    public function forget(?string $key = null, array|string|null $tags = null): bool
    {
        if (empty($tags)) {
            return $key ? cache()->forget($key) : cache()->flush();
        }

        return $key
            ? $this->tags($tags)->forget($key)
            : $this->tags($tags)->flush();
    }

    /**
     * Flush all cache or only those with specific tags.
     *
     * @param array|string|null $tags
     * @return bool
     */
    public function flush(array|string|null $tags = null): bool
    {
        $result = $this->tags($tags)->flush();
        return $result;
    }

    /**
     * Generate a unique cache key based on model, method, auth, args, domain, and request page.
     *
     * @param string $prefix
     * @param string $method
     * @param string|int|bool|null $auth
     * @param array $args
     * @param string|null $domain
     * @return string
     */
    public function createKey(
        string $prefix,
        string $method,
        string|int|bool|null $auth = null,
        array $args = [],
        ?string $domain = null
    ): string {
        $keyParts = [
            'prefix' => $prefix,
            'method' => $method,
            'auth' => $auth === false || $auth === null ? 'public' : 'user_' . $auth,
            'domain' => $domain ?? 'global',
        ];

        // Special handling for wheres
        if (isset($args['wheres'])) {
            $wheres = $args['wheres'];
            unset($args['wheres']);

            // SUPER SIMPLE HASH
            $args['wheres_hash'] = md5(var_export($wheres, true));
        }

        // Normalize other args
        $argStrings = array_map(function ($arg) {
            if ($arg === null) {
                return 'null';
            }

            if (is_scalar($arg)) {
                return (string) $arg;
            }

            if ($arg instanceof Model) {
                return 'model:' . $arg->getKey();
            }

            if (is_array($arg)) {
                ksort($arg);
                return 'array:' . md5(json_encode($arg));
            }

            if ($arg instanceof \Closure) {
                // normalize closures too
                $ref = new \ReflectionFunction($arg);
                $signature = $ref->getFileName() . ':' . $ref->getStartLine() . ':' . $ref->getEndLine();
                return 'closure:' . md5($signature);
            }

            return gettype($arg);
        }, $args);

        $keyParts['args'] = implode('_', $argStrings);

        // Include page number for pagination caches
        if (request()->has('page')) {
            $keyParts['page'] = 'page_' . request()->get('page');
        }

        return md5(implode('_', $keyParts));
    }


    /**
     * Use remember pattern with optional cache tags.
     *
     * @param string $key
     * @param int $seconds
     * @param \Closure $callback
     * @param array|string|null $tags
     * @return mixed
     */
    public function remember(string $key, int $seconds, \Closure $callback, array|string|null $tags = null): mixed
    {
        return $this->tags($tags)->remember($key, $seconds, $callback);
    }

    /**
     * Get a tag-aware cache repository.
     *
     * @param array|string|null $tags
     * @return \Illuminate\Contracts\Cache\Repository|\Illuminate\Cache\TaggedCache
     */
    public function tags(array|string|null $tags = null)
    {
        $isTaggable = Cache::getStore() instanceof \Illuminate\Cache\TaggableStore;

        if ($isTaggable && !empty($tags)) {
            $repo = cache()->tags(is_array($tags) ? $tags : explode(',', $tags));
            return $repo;
        }

        return cache();
    }

    /**
     * Check if a cache entry exists, optionally within tags.
     */
    public function has(string $key, array|string|null $tags = null): bool
    {
        if (empty($tags)) {
            return cache()->has($key);
        }
        return $this->tags($tags)->has($key);
    }
}
