<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Scopes\CompanyScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index');
    }

    public function show(int $id): View
    {
        $user = User::withoutGlobalScope(CompanyScope::class)
            ->with('company')
            ->findOrFail($id);

        return view('admin.users.show', compact('user'));
    }

    public function edit(int $id): View
    {
        $user = User::withoutGlobalScope(CompanyScope::class)->findOrFail($id);
        $companies = Company::orderBy('name')->pluck('name', 'id');

        return view('admin.users.edit', compact('user', 'companies'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::withoutGlobalScope(CompanyScope::class)->findOrFail($id);

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:customer,affiliate,admin'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = User::withoutGlobalScope(CompanyScope::class)->findOrFail($id);
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
