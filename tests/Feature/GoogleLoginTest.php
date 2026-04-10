<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        uss('disable_registration', 0);
        uss('allow_google_login', 1);
        uss('google_client_id', 'google-client-id');
        uss('google_client_secret', 'google-client-secret');
        uss('google_redirect', '/panel/login/google/callback');

        Config::set('services.google.client_id', 'google-client-id');
        Config::set('services.google.client_secret', 'google-client-secret');
        Config::set('services.google.redirect', '/panel/login/google/callback');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_login_page_shows_google_button_when_enabled_and_configured(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('login.google'), false)
            ->assertSee(__('wncms::word.login_with_google'), false);
    }

    public function test_register_page_shows_google_button_when_enabled_and_configured(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('login.google'), false)
            ->assertSee(__('wncms::word.register_with_google'), false);
    }

    public function test_login_page_hides_google_button_when_required_settings_are_missing(): void
    {
        uss('google_client_secret', '');
        Config::set('services.google.client_secret', '');

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee(route('login.google'), false);
    }

    public function test_google_login_redirect_route_uses_socialite_driver(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('login.google'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_callback_logs_in_existing_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'google-existing@example.com',
            'social_login_type' => null,
            'social_login_id' => null,
            'last_login_at' => null,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($this->makeSocialiteUser([
            'id' => 'google-existing-id',
            'email' => 'google-existing@example.com',
            'name' => 'Existing User',
            'nickname' => 'existing-user',
            'avatar' => null,
            'user' => [
                'given_name' => 'Existing',
                'family_name' => 'User',
            ],
        ]));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('dashboard'));

        $user = $user->fresh();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google', $user->social_login_type);
        $this->assertSame('google-existing-id', $user->social_login_id);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_google_callback_creates_a_new_user_when_email_does_not_exist(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($this->makeSocialiteUser([
            'id' => 'google-new-id',
            'email' => 'google-new@example.com',
            'name' => 'New User',
            'nickname' => 'new-user',
            'avatar' => null,
            'user' => [
                'given_name' => 'New',
                'family_name' => 'User',
            ],
        ]));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('dashboard'));

        $user = User::where('email', 'google-new@example.com')->first();

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google', $user->social_login_type);
        $this->assertSame('google-new-id', $user->social_login_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->last_login_at);
    }

    public function test_google_callback_redirects_to_login_when_socialite_throws(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andThrow(new \RuntimeException('Google auth failed.'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->from(route('login'))
            ->get(route('login.google.callback'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    protected function makeSocialiteUser(array $attributes): object
    {
        return (object) $attributes;
    }
}
