<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminStaffMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isAdminStaff() || $user->status !== 'active') {
            if ($user && $user->status !== 'active') {
                $user->currentAccessToken()?->delete();
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Administrative access only.',
                ], 403);
            }
            return redirect()->route('admin.login')->with('error', 'Administrative login required.');
        }

        return $next($request);
    }
}

