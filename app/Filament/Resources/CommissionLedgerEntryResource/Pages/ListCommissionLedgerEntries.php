<?php

namespace App\Filament\Resources\CommissionLedgerEntryResource\Pages;

use App\Filament\Resources\CommissionLedgerEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListCommissionLedgerEntries extends ListRecords
{
    protected static string $resource = CommissionLedgerEntryResource::class;
}
