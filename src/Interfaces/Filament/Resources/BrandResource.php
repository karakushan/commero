<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\BrandResource\Pages;
use Commero\Models\Brand;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandResource extends AdminResource
{
    protected static ?string $model = Brand::class;

    protected static ?int $navigationSort = 5;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bookmark';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.brand.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.brand.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.brand.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('commero::admin.common.code'))->required()->unique(ignoreRecord: true),
            TextInput::make('name')->label(__('commero::admin.common.name'))->required(),
            TextInput::make('slug')->label(__('commero::admin.common.slug'))->required()->unique(ignoreRecord: true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('commero::admin.common.code'))->searchable(),
                TextColumn::make('name')->label(__('commero::admin.common.name'))->searchable(),
                TextColumn::make('slug')->label(__('commero::admin.common.slug'))->searchable(),
                TextColumn::make('updated_at')->label(__('commero::admin.common.updated_at'))->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'edit' => Pages\EditBrand::route('/{record}/edit'),
        ];
    }
}
