<?php

namespace App\Http\Middleware;

use App\Helpers\ResponseHelper;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function authenticate($request, array $guards)
    {
        // Check if the request has the custom authorization header
        if ($request->hasHeader('VAuthorization')) {
            $token = str_replace('Bearer ', '', $request->header('VAuthorization'));
            $request->headers->set('Authorization', 'Bearer ' . $token);
            parent::authenticate($request, $guards);
            /*if ($token) {
                // Call the parent method
                parent::authenticate($request, $guards);
            }
            return ResponseHelper::send(401, __('api.error_please_login'));*/
        }
    }

//    protected function redirectTo(Request $request): ?string
//    {
//        return $request->expectsJson() ? null : route('login');
//    }
}
