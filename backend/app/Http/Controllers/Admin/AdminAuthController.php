<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->isAdminStaff()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            if ($user->status !== 'active') {
                Auth::logout();
                $statusMsg = match ($user->status) {
                    'suspended' => 'Your administrative account has been suspended. Please contact the system administrator.',
                    'inactive' => 'Your administrative account is inactive. Please contact the system administrator.',
                    'pending_activation' => 'Your account is pending activation. Please verify your credentials first.',
                    default => 'Your account is not active.',
                };
                return back()->withErrors(['email' => $statusMsg]);
            }

            if ($user->isAdminStaff()) {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard'));
            }

            Auth::logout();
            return back()->withErrors(['email' => 'Access denied. Account is not assigned administrative access.']);
        }

        return back()->withErrors(['email' => 'Invalid email or password credentials.']);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

