<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->user()->email !== env('ADMIN_EMAIL')) {
            abort(403);
        }

        return $next($request);
    }
}
