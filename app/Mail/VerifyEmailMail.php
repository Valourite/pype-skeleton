<?php

declare(strict_types=1);

namespace App\Mail;

use App\Data\User;
use Pype\Auth\Auth;
use Pype\Mail\Mail;
use Pype\Mail\Message;

/** The "confirm your email" link on its own (resent from the account page). */
final class VerifyEmailMail extends Mail
{
    public function __construct(private readonly User $user)
    {
    }

    /** Signed, expires in a day, and tied to the address: a changed email makes old links useless. */
    public static function link(User $user): string
    {
        return signed_url('verification.verify', ['id' => $user->id, 'hash' => Auth::emailHash($user->email)], expiresIn: 86400);
    }

    public function build(): Message
    {
        return Message::to($this->user->email, $this->user->name)
            ->subject('Confirm your email address')
            ->view('emails/verify-email', ['user' => $this->user, 'link' => self::link($this->user)]);
    }
}
