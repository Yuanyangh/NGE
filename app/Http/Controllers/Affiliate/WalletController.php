<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;

class WalletController extends Controller
{
    public function index()
    {
        return view('affiliate.wallet');
    }
}
