<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;

class TeamController extends Controller
{
    public function index()
    {
        return view('affiliate.team');
    }
}
