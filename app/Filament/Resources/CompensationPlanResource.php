<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompensationPlanResource\Pages;
use App\Models\CompensationPlan;
use App\Scopes\CompanyScope;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompensationPlanResource extends Resource
{
    protected static ?string $model = CompensationPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Compensation';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('company_id')
                ->relationship('company', 'name')
                ->required(),
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('version')
                ->required()
                ->maxLength(20),
            Forms\Components\Textarea::make('config')
                ->required()
                ->json()
                ->rows(20)
                ->columnSpanFull()
                ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $state)
                ->dehydrateStateUsing(fn ($state) => json_decode($state, true)),
            Forms\Components\DatePicker::make('effective_from')
                ->required(),
            Forms\Components\DatePicker::make('effective_until'),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('company.name')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('version'),
                Tables\Columns\TextColumn::make('effective_from')->date()->sortable(),
                Tables\Columns\TextColumn::make('effective_until')->date(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\SelectFilter::make('company_id')
                    ->relationship('company', 'name')
                    ->label('Company'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope(CompanyScope::class);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompensationPlans::route('/'),
            'create' => Pages\CreateCompensationPlan::route('/create'),
            'edit' => Pages\EditCompensationPlan::route('/{record}/edit'),
        ];
    }
}
