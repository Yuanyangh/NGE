<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionRunResource\Pages;
use App\Models\CommissionRun;
use App\Models\Company;
use App\Services\Commission\CommissionRunOrchestrator;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionRunResource extends Resource
{
    protected static ?string $model = CommissionRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';

    protected static ?string $navigationGroup = 'Compensation';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->disabled(),
            Forms\Components\TextInput::make('run_date')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\TextInput::make('total_affiliate_commission')->disabled(),
            Forms\Components\TextInput::make('total_viral_commission')->disabled(),
            Forms\Components\TextInput::make('total_company_volume')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('company.name')->sortable(),
                Tables\Columns\TextColumn::make('compensationPlan.name')->label('Plan'),
                Tables\Columns\TextColumn::make('run_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'running' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_affiliate_commission')
                    ->numeric(decimalPlaces: 4)
                    ->label('Affiliate'),
                Tables\Columns\TextColumn::make('total_viral_commission')
                    ->numeric(decimalPlaces: 4)
                    ->label('Viral'),
                Tables\Columns\IconColumn::make('viral_cap_triggered')->boolean(),
                Tables\Columns\TextColumn::make('started_at')->dateTime()->sortable(),
            ])
            ->defaultSort('run_date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'running' => 'Running',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('trigger_run')
                    ->label('Trigger Commission Run')
                    ->icon('heroicon-o-play')
                    ->form([
                        Forms\Components\Select::make('company_id')
                            ->options(Company::pluck('name', 'id'))
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->default(now())
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $company = Company::findOrFail($data['company_id']);
                        $orchestrator = app(CommissionRunOrchestrator::class);
                        $run = $orchestrator->run($company, Carbon::parse($data['date']));

                        Notification::make()
                            ->title('Commission run completed')
                            ->body(sprintf(
                                'Affiliate: $%s | Viral: $%s',
                                $run->total_affiliate_commission,
                                $run->total_viral_commission
                            ))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionRuns::route('/'),
            'view' => Pages\ViewCommissionRun::route('/{record}'),
        ];
    }
}
