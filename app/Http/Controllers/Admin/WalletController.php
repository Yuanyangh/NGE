<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletAccount;
use App\Scopes\CompanyScope;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(): View
    {
        return view('admin.wallets.index');
    }

    public function show(int $id): View
    {
        $query = WalletAccount::withoutGlobalScope(CompanyScope::class)
            ->with(['user', 'company']);

        // Company admins may only view wallets belonging to their own company.
        $user = auth()->user();
        if ($user->isCompanyAdmin()) {
            $query->where('company_id', $user->company_id);
        }

        $walletAccount = $query->findOrFail($id);

        return view('admin.wallets.show', compact('walletAccount'));
    }
}
