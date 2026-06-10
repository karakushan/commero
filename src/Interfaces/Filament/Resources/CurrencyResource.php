<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\CurrencyResource\Pages;
use Commero\Models\Currency;
use Commero\Support\Countries;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrencyResource extends AdminResource
{
    protected static ?string $model = Currency::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.currency.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.system');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.currency.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.currency.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label(__('commero::admin.common.code'))
                ->maxLength(3)
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('name')
                ->label(__('commero::admin.common.name'))
                ->required(),
            TextInput::make('symbol')
                ->label(__('commero::admin.currency.symbol'))
                ->maxLength(10)
                ->required(),
            Select::make('country_codes')
                ->label(__('commero::admin.currency.country_codes'))
                ->options(Countries::list())
                ->multiple()
                ->searchable()
                ->preload()
                ->helperText(__('commero::admin.currency.country_codes_hint')),
            Toggle::make('is_base')
                ->label(__('commero::admin.currency.is_base'))
                ->default(false),
            TextInput::make('rate')
                ->label(__('commero::admin.currency.rate'))
                ->numeric()
                ->minValue(0)
                ->default(1)
                ->required(),
            TextInput::make('sort')
                ->label(__('commero::admin.common.sort'))
                ->numeric()
                ->default(0)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('commero::admin.common.code'))->searchable()->sortable(),
                TextColumn::make('name')->label(__('commero::admin.common.name'))->searchable(),
                TextColumn::make('symbol')->label(__('commero::admin.currency.symbol')),
                TextColumn::make('country_codes')
                    ->label(__('commero::admin.currency.country_codes'))
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : (string) ($state ?? '')),
                IconColumn::make('is_base')->label(__('commero::admin.currency.is_base'))->boolean(),
                TextColumn::make('rate')->label(__('commero::admin.currency.rate'))->numeric(decimalPlaces: 6),
                TextColumn::make('sort')->label(__('commero::admin.common.sort'))->sortable(),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
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
