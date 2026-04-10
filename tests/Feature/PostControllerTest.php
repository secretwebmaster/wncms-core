<?php

namespace Wncms\Tests\Feature;

use Wncms\Models\Post;
use Wncms\Models\Tag;
use Wncms\Models\User;
use Wncms\Models\Website;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Spatie\Permission\Models\Permission;
use Wncms\Tests\TestCase;
use Illuminate\Http\UploadedFile;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function bypassPostAuthorization(): void
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authorize::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'admin@demo.com')->first() ?: User::first();
        foreach ([
            'post_index',
            'post_create',
            'post_clone',
            'post_edit',
            'post_show',
            'post_delete',
            'post_bulk_sync_tags',
            'post_generate_demo_posts',
            'post_bulk_clone',
        ] as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }
        $this->user->assignRole('admin');
        $this->user->givePermissionTo([
            'post_index',
            'post_create',
            'post_clone',
            'post_edit',
            'post_show',
            'post_delete',
            'post_bulk_sync_tags',
            'post_generate_demo_posts',
            'post_bulk_clone',
        ]);
        $this->actingAs($this->user);

        $this->website = $this->user->websites()->first();

        config(['laravellocalization.useAcceptLanguageHeader' => false]);
        config(['laravellocalization.hideDefaultLocaleInURL ' => false]);
        config(['app.setup_loaded' => true]);
    }

    public function test_index_displays_posts()
    {
        $this->bypassPostAuthorization();

        // Create some posts
        Post::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Call the index method
        $response = $this->get(route('posts.index'));

        // Assert that the view is loaded with posts
        $response->assertStatus(200);
        $response->assertViewHas('posts');
    }

    public function test_create_displays_create_post_form()
    {
        $this->bypassPostAuthorization();

        $response = $this->get(route('posts.create'));

        // Assert that the view is loaded
        $response->assertStatus(200);
        $response->assertViewIs('wncms::backend.posts.create');
    }

    public function test_store_post_as_non_admin_with_website_restriction()
    {
        // Acting as the non-authorized user
        $nonAdminUser = User::firstOrCreate(
            [
                'email' => 'nonadmin@demo.com',
            ],
            [
                'username' => 'nonadmin',
                'password' => bcrypt('password'),
            ]
        );

        $this->actingAs($nonAdminUser);

        // Send POST request to store route
        $response = $this->post(route('posts.store'), $this->getPostData());

        // Aseert forbidden status (403)
        $response->assertStatus(403);

        // TODO:: add permission and assert success status
    }

    public function test_store_post_with_invalid_data()
    {
        $this->bypassPostAuthorization();

        // Simulate an invalid request payload (missing required fields)
        $requestData = [
            'title' => '', // Title is required
            'status' => '', // Status is required
        ];

        // Send POST request to store route
        $response = $this->post(route('posts.store'), $requestData);

        // Assert validation errors
        $response->assertSessionHasErrors([
            'title' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.title')]),
            'status' => __('wncms::word.field_is_required', ['field_name' => __('wncms::word.status')]),
        ]);
    }

    // test_store_post_without_thumbnail
    public function test_store_post_without_thumbnail()
    {
        $this->bypassPostAuthorization();

        // Simulate the request payload
        $requestData = $this->getPostData();

        // Send POST request to store route
        $response = $this->post(route('posts.store'), $requestData);

        // Get the latest post
        $post = Post::latest()->first();

        // Ensure the post was created
        $this->assertNotNull($post, 'Post was not created.');

        // Assert the post creation
        $response->assertRedirect(route('posts.edit', [
            'id' => $post->id,
            'tab' => 'basic',
        ]));
    }

    public function test_store_post_with_thumbnail()
    {
        $this->bypassPostAuthorization();

        // Simulate file upload
        Storage::fake('public');
        $imageName = 'thumbnail.jpg';
        $thumbnail = UploadedFile::fake()->image($imageName);

        // Simulate the request payload
        $requestData = $this->getPostData();

        // Send POST request to store route.
        // Laravel 13's testing layer correctly handles UploadedFile instances in the payload.
        $response = $this->post(route('posts.store'), array_merge($requestData, [
            'post_thumbnail' => $thumbnail,
        ]));

        // Get the latest post
        $post = Post::latest()->first()?->fresh();

        // Ensure the post was created
        $this->assertNotNull($post, 'Post was not created.');

        // Assert redirect to edit page
        $response->assertRedirect(route('posts.edit', [
            'id' => $post->id,
            'tab' => 'basic',
        ]));
    }

    public function test_store_post_as_non_authorized_user_fails()
    {
        // Acting as the non-authorized user
        $nonAdminUser = User::firstOrCreate(
            [
                'email' => 'nonadmin@demo.com',
            ],
            [
                'username' => 'nonadmin',
                'password' => bcrypt('password'),
            ]
        );

        $this->actingAs($nonAdminUser);

        // Send POST request
        $response = $this->post(route('posts.store'), $this->getPostData());

        // Assert forbidden status (403)
        $response->assertStatus(403);
    }

    public function test_clone_creates_new_post()
    {
        $this->bypassPostAuthorization();

        $post = Post::create($this->getPostData());
        $response = $this->get(route('posts.clone', $post->id));

        // check if view is posts.create
        $response->assertViewIs('wncms::backend.posts.create');

        // check if title is displayed in the form title field
        $response->assertSee($post->title);
    }

    public function test_edit_displays_edit_post_form()
    {
        $this->bypassPostAuthorization();

        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->get(route('posts.edit', $post->id));

        // Assert that the view is loaded
        $response->assertStatus(200);
        $response->assertViewIs('wncms::backend.posts.edit');
    }

    public function test_update_changes_post_data()
    {
        $this->bypassPostAuthorization();

        $post = Post::create($this->getPostData());

        $updatedData = $this->getPostData(title: 'Updated Title');

        $response = $this->patch(route('posts.update', $post->id), $updatedData);

        $post->refresh();

        // Assert that value is updated by calling $post->title
        $this->assertEquals('Updated Title', $post->title);

        $response->assertRedirect(route('posts.edit', [
            'id' => $post->id,
            'tab' => 'basic',
        ]));
    }

    public function test_destroy_deletes_post()
    {
        $this->bypassPostAuthorization();

        $post = Post::create($this->getPostData());

        $response = $this->delete(route('posts.destroy', $post->id));

        // Assert that the post was deleted
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $response->assertRedirect(route('posts.index'));
    }

    public function test_restore_restores_trashed_post()
    {
        $this->bypassPostAuthorization();

        $post = Post::create($this->getPostData());
        $post->delete();
        $response = $this->get(route('posts.restore', $post->id));

        // Assert that the post was restored and set to drafted status
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'status' => 'drafted']);
        $response->assertRedirect(route('posts.index'));
    }

    public function test_bulk_sync_tags()
    {
        $this->bypassPostAuthorization();

        // Create some posts and tags for testing
        $post1 = Post::create($this->getPostData(title: 'Post 1'));
        $post2 = Post::create($this->getPostData(title: 'Post 2'));


        // Prepare form data
        $formData = [
            'action' => 'sync',
            'post_categories' => json_encode([['name' => 'Category1'], ['name' => 'Category2']]),
            'post_tags' => json_encode([['name' => 'Tag1'], ['name' => 'Tag2']]),
        ];

        // Simulate the form submission with post IDs and formData
        $response = $this->postJson(route('posts.bulk_sync_tags'), [
            'model_ids' => [$post1->id, $post2->id],
            'formData' => http_build_query($formData),
        ]);

        // Assert that the response was successful
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => __('wncms::word.successfully_updated_all'),
            ]);

        // Assert that the posts have the appropriate tags
        $this->assertTrue($post1->tags()->where('name', 'Category1')->where('type', 'post_category')->exists());
        $this->assertTrue($post1->tags()->where('name', 'Category2')->where('type', 'post_category')->exists());
        $this->assertTrue($post1->tags()->where('name', 'Tag1')->where('type', 'post_tag')->exists());
        $this->assertTrue($post1->tags()->where('name', 'Tag2')->where('type', 'post_tag')->exists());
    }

    public function test_generate_demo_posts()
    {
        $this->bypassPostAuthorization();

        // Simulate the request to generate posts
        $response = $this->postJson(route('posts.generate_demo_posts'), [
            'count' => 5
        ]);

        // Assert that the correct number of posts were created
        $this->assertCount(5, Post::all());
    }

    public function test_bulk_clone()
    {
        $this->bypassPostAuthorization();

        $post1 = Post::create($this->getPostData(title: 'Post 1'));
        $post2 = Post::create($this->getPostData(title: 'Post 2'));

        $response = $this->postJson(route('posts.bulk_clone'), [
            'model_ids' => [$post1->id, $post2->id],
        ]);

        // asset 4 posts in total
        $this->assertCount(4, Post::all());
    }

    public function getPostData(
        $status = 'published',
        $visibility = 'public',
        $slug = null,
        $title = 'Test Post Title',
        $publishedAt = null
    ) {
        // return with fallback values
        return [
            'status' => $status,
            'visibility' => $visibility,
            'slug' => $slug ?? wncms()->getUniqueSlug('posts'),
            'title' => $title,
            'published_at' => $publishedAt ?? now(),
        ];
    }
}
