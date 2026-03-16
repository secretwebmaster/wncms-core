<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class ApiAuthSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $apiUser;
    protected ?Website $website = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiUser = User::factory()->create([
            'email' => 'api-auth@example.com',
            'password' => bcrypt('secret123'),
            'api_token' => Str::random(40),
        ]);

        $this->website = Website::first();

        uss('enable_api_access', 1);
        uss('enable_api_post', 1);
        uss('enable_api_website', 1);
        uss('wncms_api_post_index', 1);
        uss('wncms_api_website_index', 1);
        uss('api_access_whitelist', '');
    }

    public function test_post_index_allows_none_mode_without_whitelist(): void
    {
        uss('wncms_api_post_index_should_auth', '');

        $this->apiRequest('GET', '/api/v1/posts')
            ->assertOk()
            ->assertJson(['status' => 'success']);
    }

    public function test_post_index_allows_none_mode_with_matching_ip_whitelist(): void
    {
        uss('wncms_api_post_index_should_auth', '');
        uss('api_access_whitelist', "111.222.333.444\nexample.com");

        $this->apiRequest('GET', '/api/v1/posts', [], [], ['REMOTE_ADDR' => '111.222.333.444'])
            ->assertOk()
            ->assertJson(['status' => 'success']);
    }

    public function test_post_index_rejects_none_mode_with_non_matching_whitelist(): void
    {
        uss('wncms_api_post_index_should_auth', '');
        uss('api_access_whitelist', '111.222.333.444');

        $this->apiRequest('GET', '/api/v1/posts', [], [], ['REMOTE_ADDR' => '127.0.0.1'])
            ->assertForbidden()
            ->assertJson(['status' => 'fail']);
    }

    public function test_post_index_allows_simple_mode_with_valid_token_and_matching_domain_whitelist(): void
    {
        uss('wncms_api_post_index_should_auth', 'simple');
        uss('api_access_whitelist', 'example.com');

        $this->apiRequest(
            'GET',
            '/api/v1/posts',
            ['api_token' => $this->apiUser->api_token],
            ['Origin' => 'https://example.com']
        )->assertOk()->assertJson(['status' => 'success']);
    }

    public function test_post_index_rejects_simple_mode_with_valid_token_and_non_matching_whitelist(): void
    {
        uss('wncms_api_post_index_should_auth', 'simple');
        uss('api_access_whitelist', '111.222.333.444');

        $this->apiRequest(
            'GET',
            '/api/v1/posts',
            ['api_token' => $this->apiUser->api_token],
            [],
            ['REMOTE_ADDR' => '127.0.0.1']
        )->assertForbidden()->assertJson(['status' => 'fail']);
    }

    public function test_post_index_rejects_simple_mode_with_missing_token(): void
    {
        uss('wncms_api_post_index_should_auth', 'simple');

        $this->apiRequest('GET', '/api/v1/posts')
            ->assertUnauthorized()
            ->assertJson(['status' => 'fail']);
    }

    public function test_post_index_rejects_simple_mode_with_invalid_token(): void
    {
        uss('wncms_api_post_index_should_auth', 'simple');

        $this->apiRequest('GET', '/api/v1/posts', ['api_token' => 'invalid-token'])
            ->assertUnauthorized()
            ->assertJson(['status' => 'fail']);
    }

    public function test_post_index_allows_basic_mode_with_valid_credentials_and_matching_ip_whitelist(): void
    {
        uss('wncms_api_post_index_should_auth', 'basic');
        uss('api_access_whitelist', '111.222.333.444');

        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser->email . ':secret123'),
        ];

        $this->apiRequest('GET', '/api/v1/posts', [], $headers, ['REMOTE_ADDR' => '111.222.333.444'])
            ->assertOk()
            ->assertJson(['status' => 'success']);
    }

    public function test_post_index_rejects_basic_mode_without_credentials(): void
    {
        uss('wncms_api_post_index_should_auth', 'basic');

        $this->apiRequest('GET', '/api/v1/posts')
            ->assertUnauthorized()
            ->assertJson(['status' => 'fail']);
    }

    public function test_post_index_rejects_basic_mode_with_wrong_password(): void
    {
        uss('wncms_api_post_index_should_auth', 'basic');

        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser->email . ':wrong-password'),
        ];

        $this->apiRequest('GET', '/api/v1/posts', [], $headers)
            ->assertUnauthorized()
            ->assertJson(['status' => 'fail']);
    }

    public function test_post_index_rejects_basic_mode_with_non_matching_whitelist(): void
    {
        uss('wncms_api_post_index_should_auth', 'basic');
        uss('api_access_whitelist', '111.222.333.444');

        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->apiUser->email . ':secret123'),
        ];

        $this->apiRequest('GET', '/api/v1/posts', [], $headers, ['REMOTE_ADDR' => '127.0.0.1'])
            ->assertForbidden()
            ->assertJson(['status' => 'fail']);
    }

    public function test_website_index_uses_shared_auth_rules(): void
    {
        if (!$this->website) {
            $this->markTestSkipped('Prepared testing database does not include a website record.');
        }

        $owner = $this->website->user ?: User::first();
        $owner->forceFill([
            'api_token' => 'website-shared-token',
        ])->save();

        uss('wncms_api_website_index_should_auth', 'simple');
        uss('api_access_whitelist', '222.333.444.555');

        $this->apiRequest(
            'GET',
            '/api/v1/websites',
            ['api_token' => 'website-shared-token'],
            [],
            ['REMOTE_ADDR' => '222.333.444.555']
        )->assertOk()->assertJson(['status' => 'success']);
    }

    public function test_settings_update_persists_api_access_whitelist(): void
    {
        $admin = User::first();
        $this->actingAs($admin);

        $response = $this->put(route('settings.update'), [
            'settings' => [
                'api_access_whitelist' => "111.222.333.444\nexample.com",
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame("111.222.333.444\nexample.com", gss('api_access_whitelist'));
    }

    protected function apiRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = [],
        array $server = []
    ) {
        return $this->withHeaders($headers)
            ->withServerVariables($server)
            ->json($method, $uri, $data);
    }
}
