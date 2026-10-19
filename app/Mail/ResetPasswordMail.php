<?php

declare(strict_types=1);

namespace App\Mail;

use App\Data\User;
use Pype\Mail\Mail;
use Pype\Mail\Message;

final class ResetPasswordMail extends Mail
{
    public function __construct(private readonly User $user, private readonly string $token)
    {
    }

    public function build(): Message
    {
        return Message::to($this->user->email, $this->user->name)
            ->subject('Reset your password')
            ->view('emails/reset-password', ['user' => $this->user, 'link' => url('password.reset', ['token' => $this->token], absolute: true)]);
    }
}
