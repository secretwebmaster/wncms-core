<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Faker\Factory as Faker;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class ImportDemo extends Command
{
    protected $signature = 'wncms:import-demo {--count=10 : Number of demo posts to create}';
    protected $description = 'Generate demo posts for WNCMS.';

    public function handle(): int
    {
        $this->info('Generating demo posts...');

        try {
            $count = (int)$this->option('count');
            $postModel = wncms()->getModel('post');
            $tagModel = wncms()->getModel('tag');

            $imageDirectory = public_path('wncms/images/placeholders');
            if (!File::exists($imageDirectory)) {
                $this->error('Placeholder image directory not found: ' . $imageDirectory);
                return self::FAILURE;
            }

            $imageFilenames = preg_grep('/^placeholder_16_9_/', scandir($imageDirectory));
            $locales = LaravelLocalization::getSupportedLocales();
            $fakers = [];
            foreach ($locales as $localeCode => $localeData) {
                $fakers[$localeCode] = Faker::create($localeCode);
            }

            $categories = $tagModel::query()->where('type', 'post_category')->inRandomOrder()->limit(3)->get();
            $tags = $tagModel::query()->where('type', 'post_tag')->inRandomOrder()->limit(3)->get();

            for ($i = 0; $i < $count; $i++) {
                $faker = $fakers[config('app.locale')];
                $randomImageFilename = $faker->randomElement($imageFilenames);
                $imagePath = '/wncms/images/placeholders/' . $randomImageFilename;

                // build fake content
                $content = '';
                $paragraphCount = rand(2, 5);
                for ($j = 0; $j < $paragraphCount; $j++) {
                    $paragraphTitle = $faker->realText(20, 5);
                    $content .= "<h2>{$paragraphTitle}</h2><p>{$faker->realText(500, 5)}</p>";
                }

                // create post
                $post = $postModel::create([
                    'user_id' => 1, // default admin
                    'title' => $faker->realText(30, 5),
                    'slug' => wncms()->getUniqueSlug('posts'),
                    'content' => $content,
                    'published_at' => now(),
                    'external_thumbnail' => $imagePath,
                ]);

                // translations
                foreach ($locales as $localeCode => $localeData) {
                    if ($localeCode === config('app.locale')) {
                        continue;
                    }
                    $translatedTitle = $fakers[$localeCode]->realText(30, 5);
                    $translatedContent = '';
                    $paragraphCount = rand(2, 5);
                    for ($j = 0; $j < $paragraphCount; $j++) {
                        $pt = $fakers[$localeCode]->realText(20, 5);
                        $translatedContent .= "<h2>{$pt}</h2><p>" . $fakers[$localeCode]->realText(500, 5) . "</p>";
                    }
                    $post->setTranslation('title', $localeCode, $translatedTitle);
                    $post->setTranslation('content', $localeCode, $translatedContent);
                }

                // random categories & tags
                if ($categories->count()) {
                    $post->syncTagsWithType($categories->pluck('name'), 'post_category');
                }
                if ($tags->count()) {
                    $post->syncTagsWithType($tags->pluck('name'), 'post_tag');
                }
            }

            $this->info("✅ {$count} demo posts generated successfully!");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
