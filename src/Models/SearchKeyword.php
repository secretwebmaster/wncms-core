<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class SearchKeyword extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-magnifying-glass'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public static function add($keyword){
        $search_keyword = self::firstOrCreate([
            'keyword' => $keyword,
        ]);
        $search_keyword->increment('count');
        return $search_keyword;
    }
}
