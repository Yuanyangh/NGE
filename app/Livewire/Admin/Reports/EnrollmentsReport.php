<?php

namespace App\Livewire\Admin\Reports;

use App\Models\User;
use App\Scopes\CompanyScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class EnrollmentsReport extends ReportBase
{
    #[Computed]
    public function enrollments(): Collection
    {
        $startDt = $this->start()->toDateTimeString();
        $endDt   = $this->end()->toDateTimeString();

        return User::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->companyId)
            ->where('role', 'affiliate')
            ->whereBetween('enrolled_at', [$startDt, $endDt])
            ->select('id', 'name', 'status', 'enrolled_at')
            ->orderByDesc('enrolled_at')
            ->get()
            ->map(fn ($u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'status'      => $u->status,
                'enrolled_at' => $u->enrolled_at,
            ]);
    }

    #[Computed]
    public function dailyTrend(): Collection
    {
        $startDt = $this->start()->toDateTimeString();
        $endDt   = $this->end()->toDateTimeString();

        return DB::table('users')
            ->where('company_id', $this->companyId)
            ->where('role', 'affiliate')
            ->whereBetween('enrolled_at', [$startDt, $endDt])
            ->groupBy(DB::raw('DATE(enrolled_at)'))
            ->orderBy(DB::raw('DATE(enrolled_at)'))
            ->select(DB::raw('DATE(enrolled_at) as date'), DB::raw('COUNT(*) as count'))
            ->get()
            ->map(fn ($r) => ['date' => $r->date, 'count' => (int) $r->count]);
    }

    public function render()
    {
        return view('livewire.admin.reports.enrollments-report');
    }
}
