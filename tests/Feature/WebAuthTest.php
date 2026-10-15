<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/** The browser side: session cookie, CSRF-protected forms, redirects. */
final class WebAuthTest extends TestCase
{
    /** Load a form page and pull the CSRF token out of it, like a browser would submit it. */
    private function token(string $page = '/login'): string
    {
        preg_match('/name="_token" value="([0-9a-f]{64})"/', $this->client->get($page)->body(), $m);
        $this->assertNotEmpty($m, "No CSRF field on {$page}");

        return $m[1];
    }

    public function test_register_then_account_then_logout(): void
    {
        $res = $this->client->post('/register', ['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'correct-horse', '_token' => $this->token('/register')]);
        $this->assertSame(303, $res->status());
        $this->assertSame('/account', $res->header('Location'));

        $account = $this->client->get('/account');
        $this->assertSame(200, $account->status());
        $this->assertStringContainsString('<h1>Ada Lovelace</h1>', $account->body());
        $this->assertStringContainsString('Welcome aboard!', $account->body());

        $logout = $this->client->post('/logout', ['_token' => $this->token('/account')]);
        $this->assertSame('/login', $logout->header('Location'));
        $this->assertStringContainsString('You have been signed out.', $this->client->get('/login')->body());
        $this->assertSame('/login', $this->client->get('/account')->header('Location'));
    }

    public function test_login_sends_you_back_where_you_were_going(): void
    {
        $this->client->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'correct-horse', '_token' => $this->token('/register')]);
        $this->client->post('/logout', ['_token' => $this->token('/account')]);

        $this->assertSame('/login', $this->client->get('/account')->header('Location'));
        $res = $this->client->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse', '_token' => $this->token()]);
        $this->assertSame('/account', $res->header('Location'));
    }

    public function test_wrong_password_goes_back_to_the_form_with_an_error(): void
    {
        $res = $this->client->post('/login', ['email' => 'ada@example.com', 'password' => 'nope', '_token' => $this->token()], ['Referer' => 'http://localhost/login', 'Host' => 'localhost']);

        $this->assertSame(303, $res->status());
        $this->assertSame('/login', $res->header('Location'));

        $form = $this->client->get('/login')->body();
        $this->assertStringContainsString('These credentials do not match our records.', $form);
        $this->assertStringContainsString('value="ada@example.com"', $form);
    }

    public function test_forms_need_the_csrf_token(): void
    {
        $this->assertSame(419, $this->client->post('/login', ['email' => 'ada@example.com', 'password' => 'x'])->status());
    }

    public function test_session_and_tokens_are_separate_doors_to_the_same_users(): void
    {
        $this->client->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'correct-horse', '_token' => $this->token('/register')]);

        // The session cookie doesn't open the API...
        $this->assertSame(401, $this->client->get('/me')->status());

        // ...a token does, for the same user.
        $api = $this->client->withoutCookies();
        $token = $api->post('/auth/login', ['email' => 'ada@example.com', 'password' => 'correct-horse'])->json('data.token.token');
        $this->assertSame('Ada', $api->withHeaders(['Authorization' => "Bearer {$token}"])->get('/me')->json('data.name'));
    }
}
