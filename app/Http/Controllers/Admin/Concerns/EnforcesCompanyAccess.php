<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Company;

trait EnforcesCompanyAccess
{
    /**
     * Abort with 403 if the authenticated user is a company admin
     * attempting to access a company other than their own.
     */
    protected function authorizeCompanyAccess(Company $company): void
    {
        $user = auth()->user();

        if ($user->isCompanyAdmin() && $user->company_id !== $company->id) {
            abort(403, 'You can only access your own company data.');
        }
    }
}
