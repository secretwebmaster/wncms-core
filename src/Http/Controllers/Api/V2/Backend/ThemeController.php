<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;

class ThemeController extends ApiV2Controller
{
    public function index(Request $request)
    {
        try {
            $themes = collect(wncms()->theme()->getThemeMetas());
            $invalidThemes = [];

            $rows = $themes->filter(function ($theme) use (&$invalidThemes) {
                $id = data_get($theme, 'id');
                if (empty($id)) {
                    $invalidThemes[] = $theme;
                    return false;
                }
                return true;
            })->values();

            return $this->ok([
                'themes' => $rows,
                'invalid_themes' => $invalidThemes,
            ]);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }
}
