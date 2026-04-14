<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\EnforcesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

class KpiDashboardController extends Controller
{
    use EnforcesCompanyAccess;

    public function index(Company $company): View
    {
        $this->authorizeCompanyAccess($company);

        $endDate   = now()->toDateString();
        $startDate = now()->subDays(29)->toDateString();

        return view('admin.dashboard.kpi', [
            'company'   => $company,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]);
    }
}
