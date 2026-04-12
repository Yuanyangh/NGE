<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\View\View;

class IncomeDisclosureController extends Controller
{
    public function index(Company $company): View
    {
        $startDate = now()->subYear()->toDateString();
        $endDate   = now()->toDateString();

        return view('admin.reports.income-disclosure', compact('company', 'startDate', 'endDate'));
    }
}
