<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Row shape of the users table. QueryBuilder::as(User::class) fills it by column name.
 */
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $created_at = null,
    ) {
    }
}
