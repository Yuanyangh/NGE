<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\View\View;

class ComplianceController extends Controller
{
    public function index(Company $company): View
    {
        return view('admin.compliance.index', [
            'company' => $company,
            'scanDate' => now()->toDateString(),
        ]);
    }
}
