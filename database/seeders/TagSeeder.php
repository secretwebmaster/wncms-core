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
        $tagGroups = [
            [
                'type' => 'post_category',
                'slug' => 'uncategorized',
                'name' => [
                    'default' => 'Uncategorized',
                    'zh_TW' => '未分類',
                    'zh_CN' => '未分类',
                    'en' => 'Uncategorized',
                    'ja' => '未分類',
                ]
            ],
            // [
            //     'type' => 'post_category',
            //     'slug' => 'post_category_1',
            //     'name' => [
            //         'default' => 'Post Category 1',
            //         'zh_TW' => '分類1',
            //         'zh_CN' => '分类1',
            //         'en' => 'Post Category 1',
            //         'ja' => '分类1',
            //     ]
            // ],
            // [
            //     'type' => 'post_category',
            //     'slug' => 'post_category_2',
            //     'name' => [
            //         'default' => 'Post Category 2',
            //         'zh_TW' => '分類2',
            //         'zh_CN' => '分类2',
            //         'en' => 'Post Category 2',
            //         'ja' => '分类2',
            //     ]
            // ],
        ];

        foreach ($tagGroups as $tagGroup) {
            $tag = Tag::findOrCreate(($tagGroup['name'][config('app.locale')] ?? $tagGroup['name']['default']), $tagGroup['type']);
            $tag->update(['slug' => $tagGroup['slug']]);
            foreach ($tagGroup['name'] as $locale => $value) {

                if ($locale === 'default') {
                    continue;
                }
                
                // Set the translation for the tag
                $tag->setTranslation('name', $locale, $value);
            }
        }
    }
}
