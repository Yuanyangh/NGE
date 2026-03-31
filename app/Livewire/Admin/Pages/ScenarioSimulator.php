<?php

namespace App\Livewire\Admin\Pages;

use App\DTOs\SimulationConfig;
use App\Jobs\RunSimulationJob;
use App\Models\Company;
use App\Models\CompensationPlan;
use App\Models\SimulationRun;
use App\Scopes\CompanyScope;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.admin-layout', ['title' => 'Scenario Simulator'])]
class ScenarioSimulator extends Component
{
    // Configuration
    public ?int $company_id = null;
    public ?int $compensation_plan_id = null;
    public string $simulation_name = 'Simulation';
    public int $projection_days = 30;
    public int $seed = 42;

    // Starting Network
    public int $starting_affiliates = 10;
    public int $starting_customers = 40;

    // Growth
    public float $new_affiliates_per_day = 1;
    public float $new_customers_per_affiliate_per_month = 2;
    public float $affiliate_to_customer_ratio = 0.15;
    public string $growth_curve = 'linear';

    // Transactions
    public float $average_order_xp = 45;
    public float $orders_per_customer_per_month = 1.5;
    public float $smartship_adoption_rate = 0.30;
    public float $smartship_average_xp = 35;
    public float $refund_rate = 0.05;

    // Retention
    public float $customer_monthly_churn_rate = 0.08;
    public float $affiliate_monthly_churn_rate = 0.05;

    // Tree Shape
    public int $average_legs_per_affiliate = 3;
    public float $leg_balance_ratio = 0.6;
    public string $depth_bias = 'moderate';

    // Results
    public ?array $results = null;
    public ?int $lastSimulationRunId = null;
    public ?int $compare_run_id = null;
    public ?array $compareResults = null;

    // Async job tracking
    public bool $isRunning = false;
    public int $progress = 0;
    public ?int $runningSimulationId = null;

    #[Computed]
    public function companies(): array
    {
        return Company::pluck('name', 'id')->toArray();
    }

    #[Computed]
    public function plans(): array
    {
        if (! $this->company_id) {
            return [];
        }

        return CompensationPlan::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company_id)
            ->where('is_active', true)
            ->pluck('name', 'id')
            ->toArray();
    }

    #[Computed]
    public function savedRuns(): array
    {
        if (! $this->company_id) {
            return [];
        }

        return SimulationRun::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $this->company_id)
            ->where('status', 'completed')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->mapWithKeys(fn (SimulationRun $run) => [
                $run->id => "{$run->name} ({$run->created_at->format('M j, Y H:i')})",
            ])
            ->toArray();
    }

    public function updatedCompanyId(): void
    {
        $this->compensation_plan_id = null;
    }

    public function runSimulation(): void
    {
        $this->validate([
            'company_id' => 'required|exists:companies,id',
            'compensation_plan_id' => 'required|exists:compensation_plans,id',
            'simulation_name' => 'required|string|max:255',
            'projection_days' => 'required|integer|in:30,60,90,180,365',
            'seed' => 'required|integer',
            'starting_affiliates' => 'required|integer|min:1',
            'starting_customers' => 'required|integer|min:0',
            'new_affiliates_per_day' => 'required|numeric|min:0',
            'new_customers_per_affiliate_per_month' => 'required|numeric|min:0',
            'affiliate_to_customer_ratio' => 'required|numeric|min:0|max:1',
            'growth_curve' => 'required|in:linear,exponential,logarithmic',
            'average_order_xp' => 'required|numeric|min:0',
            'orders_per_customer_per_month' => 'required|numeric|min:0',
            'smartship_adoption_rate' => 'required|numeric|min:0|max:1',
            'smartship_average_xp' => 'required|numeric|min:0',
            'refund_rate' => 'required|numeric|min:0|max:1',
            'customer_monthly_churn_rate' => 'required|numeric|min:0|max:1',
            'affiliate_monthly_churn_rate' => 'required|numeric|min:0|max:1',
            'average_legs_per_affiliate' => 'required|integer|min:1',
            'leg_balance_ratio' => 'required|numeric|min:0|max:1',
            'depth_bias' => 'required|in:shallow,moderate,deep',
        ]);

        $company = Company::find($this->company_id);
        $plan = CompensationPlan::withoutGlobalScope(CompanyScope::class)->find($this->compensation_plan_id);

        if (! $company || ! $plan) {
            session()->flash('error', 'Company or plan not found.');
            return;
        }

        $config = SimulationConfig::fromArray([
            'projection_days' => $this->projection_days,
            'starting_affiliates' => $this->starting_affiliates,
            'starting_customers' => $this->starting_customers,
            'seed' => $this->seed,
            'growth' => [
                'new_affiliates_per_day' => $this->new_affiliates_per_day,
                'new_customers_per_affiliate_per_month' => $this->new_customers_per_affiliate_per_month,
                'affiliate_to_customer_ratio' => $this->affiliate_to_customer_ratio,
                'growth_curve' => $this->growth_curve,
            ],
            'transactions' => [
                'average_order_xp' => $this->average_order_xp,
                'orders_per_customer_per_month' => $this->orders_per_customer_per_month,
                'smartship_adoption_rate' => $this->smartship_adoption_rate,
                'smartship_average_xp' => $this->smartship_average_xp,
                'refund_rate' => $this->refund_rate,
            ],
            'retention' => [
                'customer_monthly_churn_rate' => $this->customer_monthly_churn_rate,
                'affiliate_monthly_churn_rate' => $this->affiliate_monthly_churn_rate,
            ],
            'tree_shape' => [
                'average_legs_per_affiliate' => $this->average_legs_per_affiliate,
                'leg_balance_ratio' => $this->leg_balance_ratio,
                'depth_bias' => $this->depth_bias,
            ],
        ]);

        // Create the run record as pending, then dispatch the job
        $run = SimulationRun::create([
            'company_id' => $company->id,
            'compensation_plan_id' => $plan->id,
            'name' => $this->simulation_name,
            'config' => $config->toNestedArray(),
            'projection_days' => $config->projection_days,
            'status' => 'pending',
            'progress' => 0,
        ]);

        RunSimulationJob::dispatch($run->id);

        $this->isRunning = true;
        $this->progress = 0;
        $this->runningSimulationId = $run->id;
    }

    public function pollProgress(): void
    {
        if (! $this->runningSimulationId) {
            return;
        }

        $run = SimulationRun::withoutGlobalScope(CompanyScope::class)
            ->find($this->runningSimulationId);

        if (! $run) {
            $this->isRunning = false;
            return;
        }

        $this->progress = $run->progress;

        if ($run->status === 'completed') {
            $this->isRunning = false;
            $this->results = $run->results;
            $this->lastSimulationRunId = $run->id;
            $this->progress = 100;
            $this->runningSimulationId = null;
            $this->dispatch('simulation-complete');
            session()->flash('success', 'Simulation completed successfully.');
        } elseif ($run->status === 'failed') {
            $this->isRunning = false;
            $this->progress = 0;
            $this->runningSimulationId = null;
            session()->flash('error', 'Simulation failed. Please try again.');
        }
    }

    public function exportCsv(): StreamedResponse
    {
        $results = $this->results;

        return response()->streamDownload(function () use ($results) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Day', 'Date', 'Total Affiliates', 'Total Customers', 'Active Customers',
                'Daily Volume', 'Rolling 30d Volume', 'Affiliate Commissions',
                'Viral Commissions', 'Total Payout', 'Payout Ratio %',
                'Viral Cap Applied', 'Global Cap Applied',
            ]);

            foreach ($results['daily_projections'] ?? [] as $dp) {
                fputcsv($handle, [
                    $dp['day'], $dp['date'], $dp['total_affiliates'], $dp['total_customers'],
                    $dp['active_customers'], $dp['daily_volume'], $dp['rolling_30d_volume'],
                    $dp['affiliate_commissions'], $dp['viral_commissions'], $dp['total_payout'],
                    $dp['payout_ratio_percent'], $dp['viral_cap_applied'] ? 'Yes' : 'No',
                    $dp['global_cap_applied'] ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, "simulation-{$this->simulation_name}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function loadPastRun(): void
    {
        if (! $this->compare_run_id) {
            return;
        }

        $run = SimulationRun::withoutGlobalScope(CompanyScope::class)->find($this->compare_run_id);
        if ($run && $run->results) {
            $this->compareResults = $run->results;
            $this->dispatch('simulation-complete');
            session()->flash('success', "Loaded '{$run->name}' for comparison.");
        }
    }

    public function clearComparison(): void
    {
        $this->compareResults = null;
        $this->compare_run_id = null;
        $this->dispatch('simulation-complete');
    }

    public function render()
    {
        return view('livewire.admin.pages.scenario-simulator');
    }
}
