<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('affiliate.dashboard');
    }
}
