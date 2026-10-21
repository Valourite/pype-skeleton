<?php

declare(strict_types=1);

namespace App\Stages;

use App\Data\User;
use Closure;
use Pype\Database\Connection;
use Pype\Http\Request;
use Pype\Pipeline\Stage;

/**
 * A page of users: ?page=2 → {"data": [...], "meta": {"pagination": {page, per_page, total, last_page}}}.
 * The last stage of its route returns the result; the outlet writes it.
 */
final class ListUsers implements Stage
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        return $this->db->table('users')->orderBy('id')->as(User::class)->paginate(25, $request);
    }
}
