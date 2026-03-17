<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletAccountResource\Pages;
use App\Models\WalletAccount;
use App\Scopes\CompanyScope;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WalletAccountResource extends Resource
{
    protected static ?string $model = WalletAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationGroup = 'Wallets';

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
                Tables\Columns\TextColumn::make('user.email')->searchable(),
                Tables\Columns\TextColumn::make('currency'),
                Tables\Columns\TextColumn::make('balance')
                    ->getStateUsing(fn (WalletAccount $record) => $record->totalNonReversed())
                    ->numeric(decimalPlaces: 4)
                    ->label('Balance'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
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
        return parent::getEloquentQuery()->withoutGlobalScope(CompanyScope::class);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletAccounts::route('/'),
            'view' => Pages\ViewWalletAccount::route('/{record}'),
        ];
    }
}
