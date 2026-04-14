<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\EnforcesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

class ComplianceController extends Controller
{
    use EnforcesCompanyAccess;

    public function index(Company $company): View
    {
        $this->authorizeCompanyAccess($company);

        return view('admin.compliance.index', [
            'company'  => $company,
            'scanDate' => now()->toDateString(),
        ]);
    }
}
