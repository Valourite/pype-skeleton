<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Factories\UserFactory;
use Pype\Database\Connection;
use Pype\Testing\TestClient;
use Tests\TestCase;

/**
 * The browser side: session cookie, CSRF-protected forms, redirects. submit() loads the form page,
 * takes its CSRF token and posts, like a browser; actingAs() signs in without the form.
 */
final class WebAuthTest extends TestCase
{
    private function register(): void
    {
        $this->client->submit('/register', '/register', ['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'correct-horse'])
            ->assertRedirect('/account');
    }

    public function test_register_then_account_then_logout(): void
    {
        $this->register();

        $this->client->get('/account')->assertOk()->assertSee('<h1>Ada Lovelace</h1>')->assertSee('Welcome aboard!');

        $this->client->submit('/account', '/logout')->assertRedirect('/login');
        $this->client->get('/login')->assertSee('You have been signed out.');
        $this->client->get('/account')->assertRedirect('/login');
    }

    public function test_login_sends_you_back_where_you_were_going(): void
    {
        $this->register();
        $this->client->submit('/account', '/logout');

        $this->client->get('/account')->assertRedirect('/login');
        $this->client->submit('/login', '/login', ['email' => 'ada@example.com', 'password' => 'correct-horse'])->assertRedirect('/account');
    }

    public function test_wrong_password_goes_back_to_the_form_with_an_error(): void
    {
        $this->client->submit('/login', '/login', ['email' => 'ada@example.com', 'password' => 'nope'])
            ->assertStatus(303)
            ->assertRedirect('/login');

        $this->client->get('/login')
            ->assertSee('These credentials do not match our records.')
            ->assertSee('value="ada@example.com"');
    }

    public function test_forms_need_the_csrf_token(): void
    {
        $this->client->post('/login', ['email' => 'ada@example.com', 'password' => 'x'])->assertStatus(419);
    }

    public function test_acting_as_skips_the_login_form(): void
    {
        $ada = UserFactory::new($this->app->container->get(Connection::class))->create(['name' => 'Grace']);

        $this->client->actingAs($ada)->get('/account')->assertOk()->assertSee('<h1>Grace</h1>');
    }

    public function test_session_and_tokens_are_separate_doors_to_the_same_users(): void
    {
        $this->register();

        // The session cookie doesn't open the API...
        $this->client->get('/api/me')->assertUnauthorized();

        // ...a token does, for the same user.
        $api = $this->client->withoutCookies();
        $token = $api->post('/api/auth/login', ['email' => 'ada@example.com', 'password' => 'correct-horse'])->json('data.token.token');
        $api->withHeaders(['Authorization' => "Bearer {$token}"])->get('/api/me')->assertJsonPath('data.name', 'Ada Lovelace');
    }

    public function test_remember_me_keeps_you_signed_in_after_the_session_ends(): void
    {
        $this->register();
        $this->client->submit('/account', '/logout');

        $this->client->submit('/login', '/login', ['email' => 'ada@example.com', 'password' => 'correct-horse', 'remember' => '1']);
        $cookie = $this->client->cookies()->get('pype_remember');
        $this->assertNotNull($cookie);

        // Days later: the session is gone, the remember cookie isn't.
        $later = new TestClient($this->app);
        $later->cookies()->set('pype_remember', $cookie);
        $later->get('/account')->assertOk();

        // Signing out there forgets this device.
        $later->submit('/account', '/logout');
        $again = new TestClient($this->app);
        $again->cookies()->set('pype_remember', $cookie);
        $again->get('/account')->assertRedirect('/login');
    }
}
