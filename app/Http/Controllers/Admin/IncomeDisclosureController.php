<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\EnforcesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

class IncomeDisclosureController extends Controller
{
    use EnforcesCompanyAccess;

    public function index(Company $company): View
    {
        $this->authorizeCompanyAccess($company);

        $startDate = now()->subYear()->toDateString();
        $endDate   = now()->toDateString();

        return view('admin.reports.income-disclosure', compact('company', 'startDate', 'endDate'));
    }
}
