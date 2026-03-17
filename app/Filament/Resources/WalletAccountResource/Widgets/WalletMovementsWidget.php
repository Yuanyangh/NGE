<?php

namespace App\Filament\Resources\WalletAccountResource\Widgets;

use App\Models\WalletMovement;
use App\Scopes\CompanyScope;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class WalletMovementsWidget extends BaseWidget
{
    public $record;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Movement History';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WalletMovement::withoutGlobalScope(CompanyScope::class)
                    ->where('wallet_account_id', $this->record->id)
                    ->orderByDesc('effective_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('amount')->numeric(decimalPlaces: 4),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'reversed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')->limit(50),
                Tables\Columns\TextColumn::make('effective_at')->dateTime(),
            ]);
    }
}
