<?php

declare(strict_types=1);

namespace App\Mail;

use App\Data\User;
use Pype\Mail\Mail;
use Pype\Mail\Message;

/** Sent after registering: $mailer->later(new WelcomeMail($user)). */
final class WelcomeMail extends Mail
{
    public function __construct(private readonly User $user)
    {
    }

    public function build(): Message
    {
        return Message::to($this->user->email, $this->user->name)
            ->subject('Welcome, ' . $this->user->name)
            ->view('emails/welcome', ['user' => $this->user, 'link' => url('account', absolute: true)]);
    }
}
