<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->get(config('admin.session_key'))) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
