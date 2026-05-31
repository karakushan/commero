<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\ProductAttributeResource\Pages;
use Commero\Models\ProductAttribute;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductAttributeResource extends Resource
{
    protected static ?string $model = ProductAttribute::class;

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.product_attribute.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.product_attribute.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.product_attribute.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            Select::make('group_id')->label(__('commero::admin.product_attribute.group_id'))->relationship('group', 'name')->searchable()->preload(),
            TextInput::make('code')->label(__('commero::admin.common.code'))->required()->unique(ignoreRecord: true),
            Select::make('value_type')->options([
                'string' => __('commero::admin.product_attribute.value_types.string'),
                'text' => __('commero::admin.product_attribute.value_types.text'),
                'integer' => __('commero::admin.product_attribute.value_types.integer'),
                'numeric' => __('commero::admin.product_attribute.value_types.numeric'),
                'boolean' => __('commero::admin.product_attribute.value_types.boolean'),
                'select' => __('commero::admin.product_attribute.value_types.select'),
                'option' => __('commero::admin.product_attribute.value_types.option'),
            ])->label(__('commero::admin.product_attribute.value_type'))->required(),
            TextInput::make('sort')->label(__('commero::admin.common.sort'))->numeric()->default(0)->required(),
            Toggle::make('is_filterable')->label(__('commero::admin.product_attribute.is_filterable')),
            Toggle::make('is_required')->label(__('commero::admin.product_attribute.is_required')),
            Toggle::make('is_variant_axis')->label(__('commero::admin.product_attribute.is_variant_axis')),
            ...static::mainTranslationSections(),
            Repeater::make('options')->label(__('commero::admin.common.options'))
                ->default([])
                ->schema([
                    Hidden::make('id')->dehydrated(),
                    TextInput::make('value')->label(__('commero::admin.common.value'))->required(),
                    TextInput::make('sort')->label(__('commero::admin.common.sort'))->numeric()->default(0)->required(),
                    ...static::optionTranslationSections(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('commero::admin.common.code'))->searchable(),
                TextColumn::make('translation_name')->label(__('commero::admin.common.name'))->state(fn (ProductAttribute $record): ?string => $record->translation(app()->getLocale())?->name),
                TextColumn::make('value_type')->label(__('commero::admin.product_attribute.value_type'))->formatStateUsing(fn (string $state): string => __('commero::admin.product_attribute.value_types.'.$state)),
                IconColumn::make('is_filterable')->label(__('commero::admin.product_attribute.is_filterable'))->boolean(),
                IconColumn::make('is_variant_axis')->label(__('commero::admin.product_attribute.is_variant_axis'))->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTranslationsFor(app()->getLocale());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductAttributes::route('/'),
            'create' => Pages\CreateProductAttribute::route('/create'),
            'edit' => Pages\EditProductAttribute::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, TextInput>
     */
    protected static function mainTranslationSections(): array
    {
        return array_map(fn (string $locale): TextInput => TextInput::make("translations.{$locale}.name")
            ->label(__('commero::admin.common.name'))
            ->required($locale === Locales::default())
            ->dehydratedWhenHidden()
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    /**
     * @return array<int, TextInput>
     */
    protected static function optionTranslationSections(): array
    {
        return array_map(fn (string $locale): TextInput => TextInput::make("translations.{$locale}.label")
            ->label(__('commero::admin.common.label'))
            ->required($locale === Locales::default())
            ->dehydratedWhenHidden()
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }
}
