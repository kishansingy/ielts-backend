<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckBandLevel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $requiredBandLevel
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $requiredBandLevel = null)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check if user is active
        if (!$user->isActive()) {
            return response()->json(['error' => 'Account is inactive'], 403);
        }

        // Admins can access everything
        if ($user->isAdmin()) {
            return $next($request);
        }

        // If no specific band level required, just check if user has any band level
        if (!$requiredBandLevel) {
            if (!$user->getBandLevel()) {
                return response()->json(['error' => 'No band level assigned'], 403);
            }
            return $next($request);
        }

        // Check if user can access the required band level
        if (!$user->canAccessBandLevel($requiredBandLevel)) {
            return response()->json([
                'error' => 'Access denied. Required band level: ' . $requiredBandLevel . ', Your level: ' . $user->getBandLevel()
            ], 403);
        }

        return $next($request);
    }
}