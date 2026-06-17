<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ThrottleLoginAttempts
{
    public const MAX_ATTEMPTS = 3;

    public const DECAY_SECONDS = 300;

    public static function rateLimitKey(Request $request): string
    {
        return 'login-attempts:' . $request->ip();
    }

    public function handle(Request $request, Closure $next)
    {
        $key = self::rateLimitKey($request);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = max(1, (int) ceil($seconds / 60));

            return back()
                ->withErrors(['email' => "Слишком много попыток входа. Повторите через {$minutes} мин."])
                ->onlyInput('email');
        }

        return $next($request);
    }
}
