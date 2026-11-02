<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * TrustProxies is in the global stages with an empty TRUSTED_PROXIES: nobody is trusted, so a
 * client can't dodge the login throttle by sending a different X-Forwarded-For each time.
 */
final class ProxyTest extends TestCase
{
    public function test_a_spoofed_forwarded_for_does_not_reset_the_login_throttle(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $status = $this->client->post('/api/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password'], ['X-Forwarded-For' => "198.51.100.{$i}"])->status();
            $this->assertNotSame(429, $status, "attempt {$i}");
        }

        $this->client->post('/api/auth/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password'], ['X-Forwarded-For' => '198.51.100.99'])
            ->assertStatus(429);
    }
}
