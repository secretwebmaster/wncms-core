<?php

namespace Wncms\Tests\Feature;

use Wncms\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Wncms\Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_post()
    {
        $post = Post::create($this->getPostData());

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Test Post Title',
            'status' => 'published',
        ]);
    }

    public function test_it_can_update_a_post()
    {
        $post = Post::create($this->getPostData());

        $post->update(['title' => 'Updated Title']);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_it_can_soft_delete_a_post()
    {
        $post = Post::create($this->getPostData());

        $post->delete();

        $this->assertSoftDeleted('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_it_can_force_delete_a_post()
    {
        // Create a post (using factory or directly)
        $post = Post::create($this->getPostData());

        // Force delete the post
        $post->forceDelete();

        // Assert that the post is permanently deleted
        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);
    }

    public function test_it_can_restore_a_soft_deleted_post()
    {
        // Create a post (using factory or directly)
        $post = Post::create($this->getPostData());

        // Soft delete the post
        $post->delete();

        // Restore the soft deleted post
        $post->restore();

        // Assert that the post is restored
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'deleted_at' => null,
        ]);
    }

    // Add more tests as needed for other methods and functionalities
    public function getPostData()
    {
        return [
            'status' => 'published',
            'visibility' => 'public',
            'slug' => wncms()->getUniqueSlug('posts'),
            'title' => 'Test Post Title',
            'published_at' => now(),
        ];
    }
}
