<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\CurrencyResource\Pages;
use Commero\Models\Currency;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.currency.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.navigation.system');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.currency.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.currency.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label(__('admin.common.code'))
                ->maxLength(3)
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('name')
                ->label(__('admin.common.name'))
                ->required(),
            TextInput::make('symbol')
                ->label(__('admin.currency.symbol'))
                ->maxLength(10)
                ->required(),
            Toggle::make('is_base')
                ->label(__('admin.currency.is_base'))
                ->default(false),
            TextInput::make('rate')
                ->label(__('admin.currency.rate'))
                ->numeric()
                ->minValue(0)
                ->default(1)
                ->required(),
            TextInput::make('sort')
                ->label(__('admin.common.sort'))
                ->numeric()
                ->default(0)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('admin.common.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('admin.common.name'))->searchable(),
                TextColumn::make('symbol')->label(__('admin.currency.symbol')),
                IconColumn::make('is_base')->label(__('admin.currency.is_base'))->boolean(),
                TextColumn::make('rate')->label(__('admin.currency.rate'))->numeric(decimalPlaces: 6),
                TextColumn::make('sort')->label(__('admin.common.sort'))->sortable(),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ])
            ->reorderable('sort');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencies::route('/'),
            'create' => Pages\CreateCurrency::route('/create'),
            'edit' => Pages\EditCurrency::route('/{record}/edit'),
        ];
    }
}
