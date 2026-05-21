<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access. Admin only area.');
        }
        
        if (!auth()->user()->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Your account has been blocked. Contact admin.');
        }
        
        return $next($request);
    }
}