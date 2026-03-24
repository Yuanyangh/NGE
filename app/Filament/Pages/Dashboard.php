<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CommissionTrendChart;
use App\Filament\Widgets\PlatformStatsOverview;
use App\Filament\Widgets\RecentCommissionRunsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationGroup = 'Overview';

    public function getWidgets(): array
    {
        return [
            PlatformStatsOverview::class,
            RecentCommissionRunsWidget::class,
            CommissionTrendChart::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
