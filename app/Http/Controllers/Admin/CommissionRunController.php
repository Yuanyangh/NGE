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
        $query = CommissionRun::withoutGlobalScope(CompanyScope::class)
            ->with(['company', 'compensationPlan']);

        // Company admins may only view runs for their own company.
        $user = auth()->user();
        if ($user->isCompanyAdmin()) {
            $query->where('company_id', $user->company_id);
        }

        $commissionRun = $query->findOrFail($id);

        return view('admin.commission-runs.show', compact('commissionRun'));
    }

    public function trigger(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'date' => ['required', 'date'],
        ]);

        // Company admins can only trigger runs for their own company.
        if ($user->isCompanyAdmin() && (int) $validated['company_id'] !== (int) $user->company_id) {
            abort(403, 'You can only trigger commission runs for your own company.');
        }

        $company = Company::findOrFail($validated['company_id']);
        $date = Carbon::parse($validated['date']);

        $run = app(CommissionRunOrchestrator::class)->run($company, $date);

        $affiliate = number_format((float) $run->total_affiliate_commission, 2);
        $viral = number_format((float) $run->total_viral_commission, 2);

        return redirect()->back()
            ->with('success', "Commission run completed! Affiliate: \${$affiliate} | Viral: \${$viral}");
    }
}
