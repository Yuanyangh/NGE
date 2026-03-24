<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $companySlug = $request->route('company')
            ?? $request->header('X-Company-Slug');

        if (! $companySlug) {
            throw new NotFoundHttpException('Company not specified.');
        }

        $company = $companySlug instanceof Company
            ? $companySlug
            : Company::whereRaw('LOWER(slug) = ?', [strtolower($companySlug)])->where('is_active', true)->first();

        if (! $company) {
            throw new NotFoundHttpException('Company not found.');
        }

        app()->instance('current_company_id', $company->id);
        app()->instance('current_company', $company);

        return $next($request);
    }
}
