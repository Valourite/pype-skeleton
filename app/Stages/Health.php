<?php

declare(strict_types=1);

namespace App\Stages;

use Closure;
use Pype\Http\Request;
use Pype\Pipeline\Stage;

/** For load balancers and uptime checks: answers {"data": {"status": "ok"}}. */
final class Health implements Stage
{
    public function handle(Request $request, Closure $next): mixed
    {
        return ['status' => 'ok'];
    }
}
