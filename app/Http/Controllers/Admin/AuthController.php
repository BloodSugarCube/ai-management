<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if ($request->session()->get(config('admin.session_key'))) {
            return redirect()->route('employees.index');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string', 'max:500'],
        ]);

        $expectedUser = (string) config('admin.username');
        $hash = config('admin.password_hash');
        $plain = config('admin.password');

        $ok = false;
        if (is_string($hash) && $hash !== '') {
            $ok = $credentials['username'] === $expectedUser
                && Hash::check($credentials['password'], $hash);
        } elseif ($plain !== null && $plain !== '') {
            $ok = $credentials['username'] === $expectedUser
                && hash_equals((string) $plain, $credentials['password']);
        }

        if (! $ok) {
            return back()->withErrors(['username' => 'Неверный логин или пароль.'])->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put(config('admin.session_key'), true);

        return redirect()->intended(route('employees.index'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget(config('admin.session_key'));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
