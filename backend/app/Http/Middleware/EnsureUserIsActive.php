<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request. Ensure authenticated user is in 'active' status.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'active') {
            // Revoke current token immediately
            $user->currentAccessToken()?->delete();

            if ($request->expectsJson() || $request->is('api/*')) {
                $statusMsg = match ($user->status) {
                    'suspended' => 'Your account has been suspended. Please contact customer support.',
                    'inactive' => 'Your account is inactive. Please contact customer support.',
                    'pending_activation' => 'Your account is pending activation. Please verify your credentials first.',
                    default => 'Your account is not active.',
                };

                return response()->json([
                    'success' => false,
                    'message' => $statusMsg,
                ], 403);
            }

            // Web session
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')->withErrors([
                'email' => 'Your account has been suspended or deactivated.',
            ]);
        }

        return $next($request);
    }
}
