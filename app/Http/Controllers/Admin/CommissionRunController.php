<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionRun;
use App\Models\Company;
use App\Scopes\CompanyScope;
use App\Services\Commission\CommissionRunOrchestrator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionRunController extends Controller
{
    public function index(): View
    {
        return view('admin.commission-runs.index');
    }

    public function show(int $id): View
    {
        $commissionRun = CommissionRun::withoutGlobalScope(CompanyScope::class)
            ->with(['company', 'compensationPlan'])
            ->findOrFail($id);

        return view('admin.commission-runs.show', compact('commissionRun'));
    }

    public function trigger(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'date' => ['required', 'date'],
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $date = Carbon::parse($validated['date']);

        $run = app(CommissionRunOrchestrator::class)->run($company, $date);

        $affiliate = number_format((float) $run->total_affiliate_commission, 2);
        $viral = number_format((float) $run->total_viral_commission, 2);

        return redirect()->back()
            ->with('success', "Commission run completed! Affiliate: \${$affiliate} | Viral: \${$viral}");
    }
}
