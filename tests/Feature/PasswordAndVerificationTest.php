<?php

declare(strict_types=1);

namespace Tests\Feature;

use Pype\Mail\Message;
use Pype\Mail\Transport;
use Pype\Testing\TestClient;
use Tests\TestCase;

/** Forgotten passwords and email verification, clicking the links in the emails like a user would. */
final class PasswordAndVerificationTest extends TestCase
{
    /** @return list<Message> */
    private function mails(): array
    {
        return $this->app->container->get(Transport::class)->messages();
    }

    /** The path of the first link in the email (they're absolute, to APP_URL). */
    private function linkIn(Message $message): string
    {
        preg_match('#href="(https?://[^"]+)"#', (string) $message->getHtml(), $m);

        return (string) preg_replace('#^https?://[^/]+#', '', html_entity_decode($m[1]));
    }

    private function register(string $email = 'ada@example.com'): void
    {
        $this->client->submit('/register', '/register', ['name' => 'Ada', 'email' => $email, 'password' => 'correct-horse']);
    }

    public function test_forgot_password_then_reset_with_the_emailed_link(): void
    {
        $this->register();
        $this->client->submit('/account', '/logout');

        $res = $this->client->submit('/password/forgot', '/password/forgot', ['email' => 'ada@example.com']);
        $this->assertSame('/password/forgot', $res->header('Location'));
        $this->assertStringContainsString('If that email has an account', $this->client->get('/password/forgot')->body());

        $mails = $this->mails();
        $mail = $mails[count($mails) - 1];
        $this->assertSame('Reset your password', $mail->getSubject());
        $link = $this->linkIn($mail);
        $this->assertMatchesRegularExpression('#^/password/reset/[0-9a-f]{64}$#', $link);

        $form = $this->client->get($link)->body();
        $this->assertStringContainsString('Change password', $form);
        $token = substr($link, strlen('/password/reset/'));

        $mismatch = $this->client->submit($link, '/password/reset', ['token' => $token, 'password' => 'brand-new-pass', 'password_confirmation' => 'typo']);
        $this->assertSame(303, $mismatch->status(), 'back to the form with an error');

        $done = $this->client->submit($link, '/password/reset', ['token' => $token, 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass']);
        $this->assertSame('/login', $done->header('Location'));
        $this->assertStringContainsString('Your password has been changed', $this->client->get('/login')->body());

        // The new password works, the old one doesn't, and the link is spent.
        $this->assertSame('/account', $this->client->submit('/login', '/login', ['email' => 'ada@example.com', 'password' => 'brand-new-pass'])->header('Location'));
        $this->assertStringContainsString('invalid or has expired', $this->client->get($link)->body());
    }

    public function test_unknown_emails_get_the_same_answer_and_no_mail(): void
    {
        $res = $this->client->submit('/password/forgot', '/password/forgot', ['email' => 'nobody@example.com']);

        $this->assertSame('/password/forgot', $res->header('Location'));
        $this->assertStringContainsString('If that email has an account', $this->client->get('/password/forgot')->body());
        $this->assertSame([], $this->mails());
    }

    public function test_bad_reset_tokens_are_refused(): void
    {
        $this->assertStringContainsString('invalid or has expired', $this->client->get('/password/reset/' . str_repeat('a', 64))->body());

        $res = $this->client->submit('/password/forgot', '/password/reset', ['token' => str_repeat('a', 64), 'password' => 'brand-new-pass', 'password_confirmation' => 'brand-new-pass']);
        $this->assertSame(303, $res->status(), 'back to the form with an error');
    }

    public function test_the_welcome_email_link_verifies_the_address(): void
    {
        $this->register();
        $this->assertStringContainsString('Please confirm your email address', $this->client->get('/account')->body());

        [$welcome] = $this->mails();
        $link = $this->linkIn($welcome);
        $this->assertStringContainsString('/email/verify/', $link);
        $this->assertStringContainsString('signature=', $link);

        // A different browser (not signed in) can use it too; a tampered one can't.
        $this->assertSame(403, (new TestClient($this->app))->get(str_replace('signature=', 'signature=0', $link))->status());
        $this->assertSame(403, (new TestClient($this->app))->get(preg_replace('#/verify/\d+/#', '/verify/999/', $link))->status());

        $res = $this->client->get($link);
        $this->assertSame('/account', $res->header('Location'));
        $account = $this->client->get('/account')->body();
        $this->assertStringContainsString('your email address is verified', $account);
        $this->assertStringNotContainsString('Please confirm your email address', $account);
    }

    public function test_resending_the_verification_email(): void
    {
        $this->register();
        $this->client->submit('/account', '/email/verification-notification', []);

        $mails = $this->mails();
        $this->assertCount(2, $mails);
        $this->assertSame('Confirm your email address', $mails[1]->getSubject());
        $this->assertStringContainsString('/email/verify/', $this->linkIn($mails[1]));
    }
}
