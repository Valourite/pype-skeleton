<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ExampleRoutesTest extends TestCase
{
    /** Register a user through the API and return [token, user id]. */
    private function register(string $email = 'ada@example.com'): array
    {
        $res = $this->client->post('/auth/register', ['name' => 'Ada Lovelace', 'email' => $email, 'password' => 'correct-horse']);
        $this->assertSame(201, $res->status(), $res->body());

        return [$res->json('data.token.token'), $res->json('data.user.id')];
    }

    public function test_register_login_me_logout(): void
    {
        [$token] = $this->register();

        $login = $this->client->post('/auth/login', ['email' => 'ada@example.com', 'password' => 'correct-horse']);
        $this->assertSame(200, $login->status());
        $this->assertSame('Bearer', $login->json('data.token.type'));
        $this->assertNull($login->json('data.user.password'));

        $authed = $this->client->withHeaders(['Authorization' => "Bearer {$token}"]);
        $this->assertSame('Ada Lovelace', $authed->get('/me')->json('data.name'));

        $this->assertSame(204, $authed->post('/auth/logout')->status());
        $this->assertSame(401, $authed->get('/me')->status());
    }

    public function test_bad_credentials_and_duplicate_registration(): void
    {
        $this->register();

        $this->assertSame(401, $this->client->post('/auth/login', ['email' => 'ada@example.com', 'password' => 'nope'])->status());

        $dupe = $this->client->post('/auth/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'another-pass']);
        $this->assertSame(422, $dupe->status());
        $this->assertSame(['That email is already registered.'], $dupe->json('error.errors.email'));
    }

    public function test_validation_errors(): void
    {
        $res = $this->client->post('/auth/register', ['email' => 'nope', 'password' => 'short']);

        $this->assertSame(422, $res->status());
        $this->assertSame(['name', 'email', 'password'], array_keys($res->json('error.errors')));
    }

    public function test_login_is_throttled(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->client->post('/auth/login', ['email' => 'x@example.com', 'password' => 'x']);
        }

        $this->assertSame(429, $this->client->post('/auth/login', ['email' => 'x@example.com', 'password' => 'x'])->status());
    }

    public function test_user_routes_require_auth_and_read_from_database(): void
    {
        [$token, $id] = $this->register();

        $this->assertSame(401, $this->client->get("/users/{$id}")->status());

        $authed = $this->client->withHeaders(['Authorization' => "Bearer {$token}"]);
        $this->assertSame('ada@example.com', $authed->get("/users/{$id}")->json('data.email'));
        $this->assertCount(1, $authed->get('/users')->json('data'));
        $this->assertSame(404, $authed->get('/users/999')->status());
    }

    public function test_request_id_on_every_response_including_errors(): void
    {
        $this->assertNotNull($this->client->get('/health')->header('X-Request-Id'));
        $this->assertNotNull($this->client->get('/nope')->header('X-Request-Id'));
    }

    public function test_pages_render_html(): void
    {
        $welcome = $this->client->get('/');
        $this->assertSame(200, $welcome->status());
        $this->assertStringContainsString('Your pype app is running.', $welcome->body());

        [, $id] = $this->register();
        $profile = $this->client->get("/users/{$id}/profile");
        $this->assertStringContainsString('<h1>Ada Lovelace</h1>', $profile->body());
        $this->assertStringContainsString('<div class="avatar" aria-hidden="true">AL</div>', $profile->body());

        $missing = $this->client->get('/users/999/profile');
        $this->assertSame(404, $missing->status());
        $this->assertStringContainsString('User 999 not found', $missing->body());
    }

    public function test_cors_and_method_handling(): void
    {
        $this->assertSame('*', $this->client->get('/health', ['Origin' => 'https://any.test'])->header('Access-Control-Allow-Origin'));

        $res = $this->client->delete('/health');
        $this->assertSame(405, $res->status());
        $this->assertSame(['GET'], $res->json('error.allowed'));
    }

    public function test_registering_sends_a_welcome_email(): void
    {
        $this->client->post('/auth/register', ['name' => 'Grace', 'email' => 'grace@mail.test', 'password' => 'correct-horse']);

        $sent = $this->app->container->get(\Pype\Mail\Transport::class)->messages();
        $this->assertCount(1, $sent);
        $this->assertSame([['grace@mail.test', 'Grace']], $sent[0]->recipients());
        $this->assertSame('Welcome, Grace', $sent[0]->getSubject());
        $this->assertStringContainsString('Welcome, Grace!', (string) $sent[0]->getHtml());
        $this->assertStringContainsString('http://localhost:8000/', (string) $sent[0]->getText(), 'absolute link in the text part');
    }

    public function test_factories_make_users_for_setup(): void
    {
        // When a test only needs users to exist, a factory is quicker than registering.
        $db = $this->app->container->get(\Pype\Database\Connection::class);
        \Database\Factories\UserFactory::new($db)->count(3)->create();
        $ada = \Database\Factories\UserFactory::new($db)->create(['email' => 'ada@example.com']);

        $login = $this->client->post('/auth/login', ['email' => 'ada@example.com', 'password' => 'password']);
        $this->assertSame(200, $login->status(), $login->body());
        $users = $this->client->withHeaders(['Authorization' => 'Bearer ' . $login->json('data.token.token')])->get('/users');
        $this->assertCount(4, $users->json('data'));
        $this->assertSame($ada->id, $users->json('data.3.id'));
    }
}
