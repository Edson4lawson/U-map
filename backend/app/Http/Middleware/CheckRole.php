<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserRole;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Convert string role to enum
        try {
            $requiredRole = UserRole::from(strtolower($role));
        } catch (\ValueError $e) {
            return response()->json(['error' => 'Invalid role specified'], 500);
        }

        $userRole = $user->role instanceof UserRole
            ? $user->role
            : UserRole::from($user->role ?? UserRole::USER->value);
        
        if ($userRole->level() < $requiredRole->level()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'This action requires ' . $requiredRole->value . ' role or higher'
            ], 403);
        }

        return $next($request);
    }
}
