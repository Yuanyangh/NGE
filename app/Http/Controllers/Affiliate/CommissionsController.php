<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;

class CommissionsController extends Controller
{
    public function index()
    {
        return view('affiliate.commissions');
    }
}
