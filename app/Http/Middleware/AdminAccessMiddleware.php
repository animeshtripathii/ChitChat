<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || Auth::user()->email !== 'tripathianimesh38@gmail.com') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden. Only the system administrator can access this resource.'], 403);
            }
            abort(403, 'Forbidden. Only the system administrator can access this page.');
        }

        return $next($request);
    }
}
