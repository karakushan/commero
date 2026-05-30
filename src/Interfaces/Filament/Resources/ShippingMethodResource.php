<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\ShippingMethodResource\Pages;
use Commero\Models\ShippingMethod;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.shipping_method.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.navigation.orders');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.shipping_method.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.shipping_method.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            ...static::mainTranslationSections(),
            TextInput::make('code')->label(__('admin.common.code'))->required()->unique(ignoreRecord: true),
            TextInput::make('sort')->label(__('admin.common.sort'))->numeric()->default(0)->required(),
            FileUpload::make('icon')
                ->label(__('admin.common.icon'))
                ->disk('public')
                ->directory('shipping-methods')
                ->visibility('public')
                ->image()
                ->columnSpanFull(),
            Toggle::make('is_active')->label(__('admin.common.is_active'))->default(true),
            ...static::descriptionTranslationSections(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('icon')
                    ->label(__('admin.common.icon'))
                    ->disk('public')
                    ->square(),
                TextColumn::make('code')->label(__('admin.common.code'))->searchable(),
                TextColumn::make('translation_name')
                    ->label(__('admin.common.name'))
                    ->state(fn (ShippingMethod $record): ?string => $record->translation(app()->getLocale())?->name ?? $record->getRawOriginal('name'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $nestedQuery) use ($search): void {
                            $nestedQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhereHas('translations', fn (Builder $translationsQuery): Builder => $translationsQuery->where('name', 'like', "%{$search}%"));
                        });
                    }),
                IconColumn::make('is_active')->label(__('admin.common.is_active'))->boolean(),
                TextColumn::make('sort')->label(__('admin.common.sort'))->sortable(),
                TextColumn::make('updated_at')->label(__('admin.common.updated_at'))->dateTime()->sortable(),
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
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }

    protected static function mainTranslationSections(): array
    {
        return array_map(fn (string $locale): TextInput => TextInput::make("translations.{$locale}.name")
            ->label(__('admin.common.name'))
            ->required($locale === Locales::default())
            ->dehydratedWhenHidden()
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    protected static function descriptionTranslationSections(): array
    {
        return array_map(fn (string $locale): Textarea => Textarea::make("translations.{$locale}.description")
            ->label(__('admin.common.description'))
            ->rows(4)
            ->dehydratedWhenHidden()
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }
}
