<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class SearchKeyword extends BaseModel
{
    use HasFactory;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'search_keyword';

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-magnifying-glass'
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Models Methods
     * ----------------------------------------------------------------------------------------------------
     */
    public static function add($keyword){
        $search_keyword = self::firstOrCreate([
            'keyword' => $keyword,
        ]);
        $search_keyword->increment('count');
        return $search_keyword;
    }
}
