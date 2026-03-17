<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAffiliate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('affiliate.login');
        }

        // Affiliates and admins can access the affiliate dashboard
        if ($user->role !== 'affiliate' && $user->role !== 'admin') {
            abort(403, 'Access restricted to affiliates.');
        }

        // Ensure the user belongs to the resolved company
        if (app()->bound('current_company_id') && $user->company_id !== app('current_company_id')) {
            abort(403, 'You do not belong to this company.');
        }

        return $next($request);
    }
}
