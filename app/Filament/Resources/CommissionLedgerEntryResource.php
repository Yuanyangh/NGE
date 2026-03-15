<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionLedgerEntryResource\Pages;
use App\Models\CommissionLedgerEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionLedgerEntryResource extends Resource
{
    protected static ?string $model = CommissionLedgerEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Compensation';

    protected static ?string $navigationLabel = 'Commission Ledger';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('company.name')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('commissionRun.run_date')
                    ->date()
                    ->label('Run Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'affiliate_commission' => 'success',
                        'viral_commission' => 'info',
                        'cap_adjustment' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('tier_achieved'),
                Tables\Columns\TextColumn::make('description')->limit(50),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'affiliate_commission' => 'Affiliate Commission',
                        'viral_commission' => 'Viral Commission',
                        'cap_adjustment' => 'Cap Adjustment',
                    ]),
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionLedgerEntries::route('/'),
        ];
    }
}
