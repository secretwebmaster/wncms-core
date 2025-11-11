<?php

namespace Wncms\Services\Managers;

class ThemeManager
{
    public function getActivatedTheme($sort = true)
    {
        // scan theme files from config/theme and extract theme name
        // $themes = collect(glob(base_path('config/theme/*')))
        //     ->map(function($theme){
        //         return pathinfo($theme, PATHINFO_FILENAME);
        //     })
        //     ->toArray();

        // if($sort){
        //     sort($themes);
        // }

        $themes = [];

        // From merged config (e.g., mergeConfigFrom)
        $themes = array_merge($themes, array_keys(config('theme', [])));

        // From public/themes directory
        $themeDir = public_path('themes');
        if (is_dir($themeDir)) {
            $dirThemes = collect(glob($themeDir . '/*', GLOB_ONLYDIR))
                ->map(fn($path) => basename($path))
                ->toArray();

            $themes = array_merge($themes, $dirThemes);
        }

        // Remove duplicates
        $themes = array_unique($themes);

        if ($sort) {
            sort($themes);
        }

        return $themes;
    }
}
