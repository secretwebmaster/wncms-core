<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Wncms\Models\Comment;
use Wncms\Models\Post;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'admin@demo.com')->first() ?: User::first();
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authorize::class);
        foreach ([
            'post_edit',
            'comment_create',
            'comment_edit',
            'comment_delete',
        ] as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }
        $this->admin->givePermissionTo([
            'post_edit',
            'comment_create',
            'comment_edit',
            'comment_delete',
        ]);
        $this->actingAs($this->admin);

        $this->post = Post::factory()->create([
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_post_update_redirects_back_to_requested_tab(): void
    {
        $response = $this->patch(route('posts.update', $this->post->id), [
            'title' => 'Updated title',
            'status' => 'published',
            'visibility' => 'public',
            'slug' => $this->post->slug,
            'active_tab' => 'basic',
        ]);

        $response->assertRedirect(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'basic',
        ]));
    }

    public function test_comment_store_can_set_author_and_status(): void
    {
        $author = User::factory()->create();

        $response = $this->from(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]))->post(route('comments.store'), [
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'user_id' => $author->id,
            'status' => 'pending',
            'content' => 'Admin seeded comment',
        ]);

        $response->assertRedirect(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]));
        $response->assertSessionHas('active_tab', 'comments');

        $comment = Comment::latest()->first();

        $this->assertNotNull($comment);
        $this->assertSame($author->id, $comment->user_id);
        $this->assertSame('pending', $comment->status);
        $this->assertSame('Admin seeded comment', $comment->content);
    }

    public function test_comment_update_can_edit_author_status_and_content(): void
    {
        $author = User::factory()->create();
        $updatedAuthor = User::factory()->create();

        $comment = Comment::create([
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'content' => 'Original content',
            'user_id' => $author->id,
            'status' => 'visible',
        ]);

        $response = $this->from(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]))->patch(route('comments.update', $comment->id), [
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'user_id' => $updatedAuthor->id,
            'status' => 'rejected',
            'content' => 'Updated content',
            'parent_id' => null,
        ]);

        $response->assertRedirect(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]));
        $response->assertSessionHas('active_tab', 'comments');

        $comment->refresh();

        $this->assertSame($updatedAuthor->id, $comment->user_id);
        $this->assertSame('rejected', $comment->status);
        $this->assertSame('Updated content', $comment->content);
    }

    public function test_comment_store_can_reply_to_existing_comment(): void
    {
        $parent = Comment::create([
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'content' => 'Parent comment',
            'user_id' => $this->admin->id,
            'status' => 'visible',
        ]);

        $response = $this->from(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]))->post(route('comments.store'), [
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'parent_id' => $parent->id,
            'status' => 'visible',
            'content' => 'Reply comment',
        ]);

        $response->assertRedirect(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]));

        $reply = Comment::where('parent_id', $parent->id)->latest()->first();

        $this->assertNotNull($reply);
        $this->assertSame('Reply comment', $reply->content);
        $this->assertSame($this->post->id, $reply->commentable_id);
    }

    public function test_comment_store_can_save_guest_author(): void
    {
        $response = $this->from(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]))->post(route('comments.store'), [
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'user_id' => null,
            'status' => 'visible',
            'content' => 'Guest comment',
        ]);

        $response->assertRedirect(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]));

        $comment = Comment::latest()->first();

        $this->assertNotNull($comment);
        $this->assertNull($comment->user_id);
        $this->assertSame('Guest comment', $comment->content);
    }

    public function test_comment_update_can_switch_author_to_guest(): void
    {
        $author = User::factory()->create();

        $comment = Comment::create([
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'content' => 'Author comment',
            'user_id' => $author->id,
            'status' => 'visible',
        ]);

        $response = $this->from(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]))->patch(route('comments.update', $comment->id), [
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'user_id' => null,
            'status' => 'pending',
            'content' => 'Now guest comment',
            'parent_id' => null,
        ]);

        $response->assertRedirect(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]));

        $comment->refresh();

        $this->assertNull($comment->user_id);
        $this->assertSame('pending', $comment->status);
        $this->assertSame('Now guest comment', $comment->content);
    }

    public function test_comment_update_can_edit_created_time(): void
    {
        $comment = Comment::create([
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'content' => 'Initial comment',
            'user_id' => $this->admin->id,
            'status' => 'visible',
            'created_at' => now()->subDays(2),
        ]);
        $targetCreatedAt = now()->subHours(5)->format('Y-m-d H:i');

        $response = $this->from(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]))->patch(route('comments.update', $comment->id), [
            'commentable_type' => Post::class,
            'commentable_id' => $this->post->id,
            'user_id' => $this->admin->id,
            'status' => 'visible',
            'created_at' => $targetCreatedAt,
            'content' => 'Edited with new timestamp',
            'parent_id' => null,
        ]);

        $response->assertRedirect(route('posts.edit', [
            'id' => $this->post->id,
            'tab' => 'comments',
        ]));

        $comment->refresh();

        $this->assertSame('Edited with new timestamp', $comment->content);
        $this->assertSame(date('Y-m-d H:i', strtotime($targetCreatedAt)), $comment->created_at->format('Y-m-d H:i'));
    }

    public function test_comment_user_search_returns_filtered_results_and_limits_to_fifty(): void
    {
        User::factory()->count(55)->create();
        $matchedUser = User::factory()->create([
            'username' => 'inline-target-user',
            'email' => 'inline-target@example.com',
        ]);

        $response = $this->getJson(route('comments.users', [
            'keyword' => 'inline-target',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'users');
        $response->assertJsonFragment([
            'id' => $matchedUser->id,
            'username' => 'inline-target-user',
            'email' => 'inline-target@example.com',
        ]);

        $limitedResponse = $this->getJson(route('comments.users'));

        $limitedResponse->assertOk();
        $this->assertCount(50, $limitedResponse->json('users'));
    }

    public function test_non_admin_comment_user_search_only_returns_current_user(): void
    {
        $regularUser = User::factory()->create([
            'username' => 'comment-owner',
            'email' => 'comment-owner@example.com',
        ]);
        User::factory()->create([
            'username' => 'someone-else',
            'email' => 'someone-else@example.com',
        ]);

        $this->actingAs($regularUser);

        $response = $this->getJson(route('comments.users', [
            'keyword' => 'someone',
        ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'users');
        $response->assertJsonFragment([
            'id' => $regularUser->id,
            'username' => 'comment-owner',
            'email' => 'comment-owner@example.com',
        ]);
    }
}
