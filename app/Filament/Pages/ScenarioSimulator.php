<?php

namespace App\Filament\Pages;

use App\DTOs\SimulationConfig;
use App\Models\CompensationPlan;
use App\Models\Company;
use App\Models\SimulationRun;
use App\Services\Simulator\SimulatorOrchestrator;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScenarioSimulator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Scenario Simulator';
    protected static ?string $title = 'Scenario Simulator';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.scenario-simulator';

    // Form state
    public ?int $company_id = null;
    public ?int $compensation_plan_id = null;
    public string $simulation_name = 'Simulation';
    public int $projection_days = 90;
    public int $seed = 42;

    // Growth
    public float $new_affiliates_per_day = 2;
    public float $new_customers_per_affiliate_per_month = 3;
    public float $affiliate_to_customer_ratio = 0.15;
    public string $growth_curve = 'linear';

    // Starting
    public int $starting_affiliates = 50;
    public int $starting_customers = 200;

    // Transactions
    public float $average_order_xp = 45;
    public float $orders_per_customer_per_month = 1.5;
    public float $smartship_adoption_rate = 0.30;
    public float $smartship_average_xp = 35;
    public float $refund_rate = 0.05;

    // Retention
    public float $customer_monthly_churn_rate = 0.08;
    public float $affiliate_monthly_churn_rate = 0.05;

    // Tree shape
    public int $average_legs_per_affiliate = 3;
    public float $leg_balance_ratio = 0.6;
    public string $depth_bias = 'moderate';

    // Results
    public ?array $results = null;
    public ?int $lastSimulationRunId = null;

    // Past runs for comparison
    public ?int $compare_run_id = null;
    public ?array $compareResults = null;

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Configuration')->schema([
                Select::make('company_id')
                    ->label('Company')
                    ->options(Company::pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn () => $this->compensation_plan_id = null),

                Select::make('compensation_plan_id')
                    ->label('Compensation Plan')
                    ->options(function () {
                        if (!$this->company_id) {
                            return [];
                        }
                        return CompensationPlan::withoutGlobalScopes()
                            ->where('company_id', $this->company_id)
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    })
                    ->required(),

                TextInput::make('simulation_name')
                    ->label('Simulation Name')
                    ->required(),

                Select::make('projection_days')
                    ->label('Projection Days')
                    ->options([
                        30 => '30 days',
                        60 => '60 days',
                        90 => '90 days',
                        180 => '180 days',
                        365 => '365 days',
                    ])
                    ->required(),

                TextInput::make('seed')
                    ->label('Random Seed')
                    ->numeric()
                    ->required(),
            ])->columns(3),

            Section::make('Starting Network')->schema([
                TextInput::make('starting_affiliates')->numeric()->required(),
                TextInput::make('starting_customers')->numeric()->required(),
            ])->columns(2),

            Section::make('Growth Assumptions')->schema([
                TextInput::make('new_affiliates_per_day')->numeric()->required()->step(0.1),
                TextInput::make('new_customers_per_affiliate_per_month')->numeric()->required()->step(0.1),
                TextInput::make('affiliate_to_customer_ratio')->numeric()->required()->step(0.01)
                    ->helperText('Fraction of new customers that convert to affiliates'),
                Select::make('growth_curve')
                    ->options(['linear' => 'Linear', 'exponential' => 'Exponential', 'logarithmic' => 'Logarithmic'])
                    ->required(),
            ])->columns(2),

            Section::make('Transaction Assumptions')->schema([
                TextInput::make('average_order_xp')->numeric()->required()->step(0.01),
                TextInput::make('orders_per_customer_per_month')->numeric()->required()->step(0.1),
                TextInput::make('smartship_adoption_rate')->numeric()->required()->step(0.01),
                TextInput::make('smartship_average_xp')->numeric()->required()->step(0.01),
                TextInput::make('refund_rate')->numeric()->required()->step(0.01),
            ])->columns(3),

            Section::make('Retention')->schema([
                TextInput::make('customer_monthly_churn_rate')->numeric()->required()->step(0.01),
                TextInput::make('affiliate_monthly_churn_rate')->numeric()->required()->step(0.01),
            ])->columns(2),

            Section::make('Tree Shape')->schema([
                TextInput::make('average_legs_per_affiliate')->numeric()->required(),
                TextInput::make('leg_balance_ratio')->numeric()->required()->step(0.1)
                    ->helperText('0.0 = mega-leg, 1.0 = perfectly balanced'),
                Select::make('depth_bias')
                    ->options(['shallow' => 'Shallow', 'moderate' => 'Moderate', 'deep' => 'Deep'])
                    ->required(),
            ])->columns(3),
        ]);
    }

    public function runSimulation(): void
    {
        $this->validate();

        $company = Company::find($this->company_id);
        $plan = CompensationPlan::withoutGlobalScopes()->find($this->compensation_plan_id);

        if (!$company || !$plan) {
            Notification::make()->title('Company or plan not found.')->danger()->send();
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

        try {
            $orchestrator = app(SimulatorOrchestrator::class);
            $result = $orchestrator->run($company, $plan, $config, $this->simulation_name);
            $this->results = $result->toStorableArray();

            // Track the simulation run ID for export
            $this->lastSimulationRunId = SimulationRun::withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('name', $this->simulation_name)
                ->latest()
                ->value('id');

            Notification::make()->title('Simulation completed successfully.')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Simulation failed: ' . $e->getMessage())->danger()->send();
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
        if (!$this->compare_run_id) {
            return;
        }

        $run = SimulationRun::withoutGlobalScopes()->find($this->compare_run_id);
        if ($run && $run->results) {
            $this->compareResults = $run->results;
            Notification::make()->title("Loaded '{$run->name}' for comparison.")->success()->send();
        }
    }

    public function clearComparison(): void
    {
        $this->compareResults = null;
        $this->compare_run_id = null;
    }

    public function getSavedRunsProperty(): array
    {
        if (!$this->company_id) {
            return [];
        }

        return SimulationRun::withoutGlobalScopes()
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
}
