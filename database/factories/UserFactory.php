<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Data\User;
use Pype\Database\Fake;
use Pype\Database\Factory;

/**
 * Users for tests and seeders. Everyone's password is "password".
 *
 *   $ada = UserFactory::new($db)->create(['name' => 'Ada']);
 *   UserFactory::new($db)->count(10)->verified()->create();
 */
final class UserFactory extends Factory
{
    protected string $table = 'users';

    protected ?string $class = User::class;

    protected function definition(Fake $fake): array
    {
        return [
            'name' => $fake->name(),
            'email' => $fake->email(),
            'password' => self::password(),
        ];
    }

    /** With a verified email address. */
    public function verified(): static
    {
        return $this->state(['email_verified_at' => gmdate('Y-m-d H:i:s')]);
    }
}
