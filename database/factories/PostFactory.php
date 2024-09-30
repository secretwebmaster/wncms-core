<?php

namespace Wncms\Database\Factories;

use Wncms\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as FakerFactory;
use LaravelLocalization;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Wncms\Models\Post>
 */
class PostFactory extends Factory
{

    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $this->faker = FakerFactory::create(LaravelLocalization::getCurrentLocale());
        
        return [
            'title' => $this->faker->sentence,
            'slug' => $this->faker->slug,
            'content' => $this->faker->paragraph,
            'status' => 'published',
            'published_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
