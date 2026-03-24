<?php

namespace App\Filament\Widgets;

use App\Models\CommissionRun;
use App\Scopes\CompanyScope;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentCommissionRunsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Commission Runs';

    protected int|string|array $columnSpan = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CommissionRun::query()
                    ->withoutGlobalScope(CompanyScope::class)
                    ->with('company')
                    ->latest('run_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable(),

                TextColumn::make('run_date')
                    ->label('Run Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'running' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('total_affiliate_commission')
                    ->label('Affiliate')
                    ->money('USD')
                    ->alignEnd(),

                TextColumn::make('total_viral_commission')
                    ->label('Viral')
                    ->money('USD')
                    ->alignEnd(),

                IconColumn::make('viral_cap_triggered')
                    ->label('Cap')
                    ->boolean(),
            ])
            ->defaultSort('run_date', 'desc')
            ->paginated(false);
    }
}
