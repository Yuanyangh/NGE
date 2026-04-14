<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\EnforcesCompanyAccess;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\View\View;

class ReportsController extends Controller
{
    use EnforcesCompanyAccess;

    /**
     * All available report definitions. Used to render the hub cards
     * and to validate the {report} segment in show().
     */
    public const REPORTS = [
        'commission-summary' => [
            'title'       => 'Commission Summary',
            'description' => 'Total commissions paid per run, by type, with period comparison.',
            'icon'        => 'currency',
            'color'       => 'indigo',
        ],
        'top-earners' => [
            'title'       => 'Top Earners',
            'description' => 'Ranked list of the highest-earning affiliates for the selected period.',
            'icon'        => 'trophy',
            'color'       => 'amber',
        ],
        'volume' => [
            'title'       => 'Volume Report',
            'description' => 'Company-wide and per-affiliate XP volume trends over time.',
            'icon'        => 'chart-bar',
            'color'       => 'emerald',
        ],
        'affiliate-activity' => [
            'title'       => 'Affiliate Activity',
            'description' => 'Active vs. inactive affiliates, ordering frequency, and engagement signals.',
            'icon'        => 'users',
            'color'       => 'sky',
        ],
        'enrollments' => [
            'title'       => 'Enrollments',
            'description' => 'New affiliate registrations over time, with sponsor attribution.',
            'icon'        => 'user-plus',
            'color'       => 'violet',
        ],
        'bonus-payout' => [
            'title'       => 'Bonus Payout',
            'description' => 'Breakdown of all bonus types paid, amounts, and trigger counts.',
            'icon'        => 'gift',
            'color'       => 'rose',
        ],
        'cap-impact' => [
            'title'       => 'Cap Impact',
            'description' => 'How much commission was reduced by viral and global caps per run.',
            'icon'        => 'shield',
            'color'       => 'orange',
        ],
        'breakage' => [
            'title'       => 'Breakage Analysis',
            'description' => 'Wasted volume, cap reductions, and clawbacks — money not paid out.',
            'icon'        => 'exclamation',
            'color'       => 'red',
        ],
        'wallet-movement' => [
            'title'       => 'Wallet Movements',
            'description' => 'All ledger credits, releases, clawbacks, and withdrawals by affiliate.',
            'icon'        => 'wallet',
            'color'       => 'teal',
        ],
        'churn-risk' => [
            'title'       => 'Churn Risk',
            'description' => 'Affiliates flagged as inactive, at-risk, declining, or stagnant leaders.',
            'icon'        => 'warning',
            'color'       => 'rose',
        ],
    ];

    public function index(Company $company): View
    {
        $this->authorizeCompanyAccess($company);

        return view('admin.reports.index', [
            'company' => $company,
            'reports' => self::REPORTS,
        ]);
    }

    public function show(Company $company, string $report): View
    {
        $this->authorizeCompanyAccess($company);

        abort_unless(array_key_exists($report, self::REPORTS), 404);

        $endDate   = now()->toDateString();
        $startDate = now()->subDays(29)->toDateString();

        return view('admin.reports.show', [
            'company'    => $company,
            'report'     => $report,
            'reportMeta' => self::REPORTS[$report],
            'startDate'  => $startDate,
            'endDate'    => $endDate,
        ]);
    }
}
