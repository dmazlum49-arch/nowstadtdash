<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
{
    public function handle(Request $request, Closure $next, string $type)
    {
        $user = $request->user();

        if (!$user || $user->type !== $type) {
            return ResponseHelper::send(403, 'Unauthorized for this route' );
        }

        return $next($request);
    }
}
