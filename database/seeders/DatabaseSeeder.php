<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Factories\UserFactory;
use Pype\Database\Connection;
use Pype\Database\Seeder;

/**
 * Demo data for development: `php bin/pype db:seed` (or `migrate:fresh --seed`).
 * Sign in as demo@example.com / password.
 */
final class DatabaseSeeder extends Seeder
{
    public function run(Connection $db): void
    {
        if (!$db->table('users')->where('email', 'demo@example.com')->exists()) {
            UserFactory::new($db)->verified()->create(['name' => 'Demo User', 'email' => 'demo@example.com']);
        }

        UserFactory::new($db)->count(10)->create();
    }
}
