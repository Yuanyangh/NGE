<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

class GenealogyController extends Controller
{
    public function index(Company $company): View
    {
        return view('admin.genealogy.index', [
            'company' => $company,
        ]);
    }
}
