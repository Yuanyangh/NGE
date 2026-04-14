<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\EnforcesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

class GenealogyController extends Controller
{
    use EnforcesCompanyAccess;

    public function index(Company $company): View
    {
        $this->authorizeCompanyAccess($company);

        return view('admin.genealogy.index', [
            'company' => $company,
        ]);
    }
}
