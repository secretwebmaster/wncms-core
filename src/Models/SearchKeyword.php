<?php

namespace Wncms\Models;

use Wncms\Traits\WnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchKeyword extends Model
{
    use HasFactory;
    use WnModelTrait;

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
