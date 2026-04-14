<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $user = auth()->user();

        // Company admins go directly to their own company's KPI dashboard.
        if ($user->isCompanyAdmin()) {
            $company = $user->company;

            if ($company) {
                return redirect()->route('admin.companies.dashboard', ['company' => $company->slug]);
            }
        }

        // Super admins see the global overview dashboard.
        return view('admin.dashboard');
    }
}
