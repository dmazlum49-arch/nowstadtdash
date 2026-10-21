<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsBusinessUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('user.login');
        } elseif (in_array($request->user()->type, ['businessuser', 'sub-businessuser'])) {
            return $next($request);
        } else {
            return redirect()->route(get_dashboard_route_name());
        }
    }
}
