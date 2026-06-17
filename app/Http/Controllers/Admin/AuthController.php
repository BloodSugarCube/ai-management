<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ThrottleLoginAttempts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('employees.index');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'string', 'max:500'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit(
                ThrottleLoginAttempts::rateLimitKey($request),
                ThrottleLoginAttempts::DECAY_SECONDS
            );

            return back()->withErrors(['email' => 'Неверный логин или пароль.'])->onlyInput('email');
        }

        RateLimiter::clear(ThrottleLoginAttempts::rateLimitKey($request));

        $request->session()->regenerate();

        return redirect()->intended(route('employees.index'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
