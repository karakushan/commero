<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\AttributeGroupResource\Pages;
use Commero\Models\AttributeGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttributeGroupResource extends AdminResource
{
    protected static ?string $model = AttributeGroup::class;

    protected static ?int $navigationSort = 4;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.attribute_group.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.attribute_group.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.attribute_group.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('commero::admin.common.code'))->required()->unique(ignoreRecord: true),
            TextInput::make('name')->label(__('commero::admin.common.name'))->required(),
            TextInput::make('sort')->label(__('commero::admin.common.sort'))->numeric()->default(0)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('commero::admin.common.code'))->searchable(),
                TextColumn::make('name')->label(__('commero::admin.common.name'))->searchable(),
                TextColumn::make('sort')->label(__('commero::admin.common.sort'))->sortable(),
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
            'index' => Pages\ListAttributeGroups::route('/'),
            'create' => Pages\CreateAttributeGroup::route('/create'),
            'edit' => Pages\EditAttributeGroup::route('/{record}/edit'),
        ];
    }
}
