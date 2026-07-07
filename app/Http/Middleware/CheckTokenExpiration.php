<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            
            if ($token && $token->expires_at && $token->expires_at->isPast()) {
                $token->delete();
                
                return response()->json([
                    'message' => 'Token has expired. Please login again.'
                ], 401);
            }
        }
        
        return $next($request);
    }
}
