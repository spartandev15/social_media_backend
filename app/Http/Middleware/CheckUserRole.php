<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        // Check if the user is authenticated
        if (Auth::check()) {
            // Check if the user role matches any of the allowed roles
            if (in_array(Auth::user()->role, $roles)) {
                return $next($request);
            }
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
