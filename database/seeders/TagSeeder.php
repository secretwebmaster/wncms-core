<?php

namespace Wncms\Database\Seeders;

use Illuminate\Database\Seeder;
use Wncms\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $nameGroups = [
            [
                'default' => 'Uncategorized',
                'zh_TW' => '未分類',
                'zh_CN' => '未分类',
                'en' => 'Uncategorized',
                'ja' => '未分類',
            ]
        ];

        foreach ($nameGroups as $nameGroup) {
            $tag = Tag::findOrCreate(($nameGroup[config('app.locale')] ?? $nameGroup['default']), 'post_category');
            foreach ($nameGroup as $locale => $value) {

                if ($locale === 'default') {
                    continue;
                }
                
                // Set the translation for the tag
                $tag->setTranslation('name', $locale, $value);
            }
        }
    }
}
