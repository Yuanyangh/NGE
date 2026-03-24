<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompensationPlanController extends Controller
{
    public function index(): View
    {
        return view('admin.compensation-plans.index');
    }

    public function create(): View
    {
        $companies = Company::orderBy('name')->pluck('name', 'id');

        return view('admin.compensation-plans.create', compact('companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:255'],
            'config' => ['required', 'json'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date'],
            'is_active' => ['nullable'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['config'] = json_decode($validated['config'], true);

        CompensationPlan::create($validated);

        return redirect()->route('admin.compensation-plans.index')
            ->with('success', 'Compensation plan created successfully.');
    }

    public function edit(int $id): View
    {
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)->findOrFail($id);
        $companies = Company::orderBy('name')->pluck('name', 'id');

        return view('admin.compensation-plans.edit', compact('plan', 'companies'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)->findOrFail($id);

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:255'],
            'config' => ['required', 'json'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date'],
            'is_active' => ['nullable'],
        ]);

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['config'] = json_decode($validated['config'], true);

        $plan->update($validated);

        return redirect()->route('admin.compensation-plans.index')
            ->with('success', 'Compensation plan updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)->findOrFail($id);
        $plan->delete();

        return redirect()->route('admin.compensation-plans.index')
            ->with('success', 'Compensation plan deleted successfully.');
    }
}
