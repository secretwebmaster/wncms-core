<?php

namespace Wncms\Services\Core;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

trait UtilityMethods
{
    public function array_fnmatch(array $patterns, string $path): bool
    {
        foreach ($patterns as $pattern) {
            if (fnmatch("*/" . $pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    public function getAllArgs($func, array $func_get_args = []): array
    {
        if ((is_string($func) && function_exists($func)) || $func instanceof \Closure) {
            $ref = new \ReflectionFunction($func);
        } elseif (is_string($func) && !call_user_func_array('method_exists', explode('::', $func))) {
            return $func_get_args;
        } else {
            $ref = new \ReflectionMethod($func);
        }

        foreach ($ref->getParameters() as $key => $param) {
            if (!isset($func_get_args[$key]) && $param->isDefaultValueAvailable()) {
                $func_get_args[$key] = $param->getDefaultValue();
            }
        }

        return $func_get_args;
    }

    public static function isValidTagifyJson(string $string): bool
    {
        try {
            $data = json_decode($string, true);

            if (!is_array($data)) {
                return false;
            }

            foreach ($data as $item) {
                if (!is_array($item) || !array_key_exists('value', $item) || !array_key_exists('name', $item)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function paginateWithLimit(LengthAwarePaginator $collection, ?int $pageSize = null, ?int $limit = null, ?int $currentPage = null, string $pageName = 'page'): LengthAwarePaginator
    {
        if (empty($pageSize)) {
            return $collection;
        }

        $currentPage ??= LengthAwarePaginator::resolveCurrentPage($pageName);

        $total = (!empty($limit) && $collection->total() > $limit) ? $limit : $collection->total();

        if ($currentPage > ceil($limit / $pageSize)) {
            $items = collect([]);
        } elseif ($currentPage == ceil($limit / $pageSize)) {
            $start = ($currentPage - 1) * $pageSize;
            $remainingItems = $total - $start;
            $items = $collection->take($remainingItems);
        } else {
            $items = $collection->take($pageSize);
        }

        return new LengthAwarePaginator(
            $items,
            $total,
            $pageSize,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    public function getUniqueSlug(string $table, string $column = 'slug', int $length = 8, string $case = 'lower', string $prefix = ''): string
    {
        do {
            $slug = str()->random($length);

            if ($case === 'upper') {
                $slug = $prefix . strtoupper($slug);
            } elseif ($case === 'lower') {
                $slug = $prefix . strtolower($slug);
            } else {
                $slug = $prefix . $slug;
            }

            $duplicated = DB::table($table)->where($column, $slug)->exists();
        } while ($duplicated);

        return $slug;
    }

    public function displayPrice($number, $digits = 2): string
    {
        return rtrim(rtrim(number_format($number, $digits), '0'), '.');
    }

    /**
     * Normalize a width/height value.
     *
     * Supports:
     *  - int         → "200px"
     *  - "200px"
     *  - "50%"
     *  - "auto"
     *  - fallback default
     *
     * @param mixed $value
     * @param string $default
     * @return string
     */
    public function normalizeDimension($value, ?string $default = '200px')
    {
        if (empty($value)) {
            return $default;
        }

        // integer → px
        if (is_numeric($value)) {
            return $value . 'px';
        }

        $value = trim((string) $value);

        // valid px
        if (preg_match('/^\d+px$/i', $value)) {
            return $value;
        }

        // valid percent
        if (preg_match('/^\d+%$/', $value)) {
            return $value;
        }

        // auto is allowed
        if (strtolower($value) === 'auto') {
            return 'auto';
        }

        // fallback
        return $default;
    }

    /**
     * Calculate aspect ratio from string.
     * 
     * Examples:
     * - "16/9"
     * - "4/3"
     */
    public function calculateAspect(?string $aspect): ?array
    {
        if (!$aspect || !str_contains($aspect, '/')) {
            return null;
        }

        [$w, $h] = explode('/', $aspect);

        if (!is_numeric($w) || !is_numeric($h) || $w == 0 || $h == 0) {
            return null;
        }

        return [
            'w' => (float)$w,
            'h' => (float)$h,
            'ratio' => (float)$h / (float)$w,
            'inverse' => (float)$w / (float)$h
        ];
    }
}
