<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\View\View;

class KpiDashboardController extends Controller
{
    public function index(Company $company): View
    {
        $endDate   = now()->toDateString();
        $startDate = now()->subDays(29)->toDateString();

        return view('admin.dashboard.kpi', [
            'company'   => $company,
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]);
    }
}
