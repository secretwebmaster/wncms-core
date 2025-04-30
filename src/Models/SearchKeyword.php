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
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }

    public static function add($keyword, Website $website = null){
        $website = $website ?? wn('website')->get();
        $search_keyword = $website->search_keywords()->firstOrCreate([
            'keyword' => $keyword,
        ]);
        $search_keyword->increment('count');
        return $search_keyword;
    }
}
