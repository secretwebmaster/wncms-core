<?php

namespace Wncms\Services\Managers;

class ThemeManager
{
    public function getActivatedTheme($sort = true)
    {
        // scan theme files from config/theme and extract theme name
        $themes = collect(glob(base_path('config/theme/*')))
            ->map(function($theme){
                return pathinfo($theme, PATHINFO_FILENAME);
            })
            ->toArray();

        if($sort){
            sort($themes);
        }
        
        return $themes;
    }
}