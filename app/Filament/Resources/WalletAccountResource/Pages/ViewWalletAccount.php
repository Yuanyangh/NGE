<?php

namespace App\Filament\Resources\WalletAccountResource\Pages;

use App\Filament\Resources\WalletAccountResource;
use App\Models\WalletMovement;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewWalletAccount extends ViewRecord
{
    protected static string $resource = WalletAccountResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\TextEntry::make('user.name')->label('User'),
            Infolists\Components\TextEntry::make('user.email')->label('Email'),
            Infolists\Components\TextEntry::make('currency'),
            Infolists\Components\TextEntry::make('balance')
                ->getStateUsing(fn ($record) => $record->balance())
                ->label('Current Balance'),
        ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            WalletAccountResource\Widgets\WalletMovementsWidget::class,
        ];
    }
}
