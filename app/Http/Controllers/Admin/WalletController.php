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
        $walletAccount = WalletAccount::withoutGlobalScope(CompanyScope::class)
            ->with(['user', 'company'])
            ->findOrFail($id);

        return view('admin.wallets.show', compact('walletAccount'));
    }
}
