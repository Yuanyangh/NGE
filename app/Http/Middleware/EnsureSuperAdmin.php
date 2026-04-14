<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('admin.login');
        }

        if (auth()->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized. Super admin access required.');
        }

        return $next($request);
    }
}
