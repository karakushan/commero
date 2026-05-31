<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\OrderStatusResource\Pages;
use Commero\Models\OrderStatus;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderStatusResource extends Resource
{
    protected static ?string $model = OrderStatus::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.order_status.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.orders');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.order_status.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.order_status.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            TextInput::make('code')->label(__('commero::admin.common.code'))->required()->unique(ignoreRecord: true)->maxLength(50),
            ...static::mainTranslationSections(),
            ColorPicker::make('color')->label(__('commero::admin.resources.order_status.badge_background_color'))->default('#6b7280'),
            ColorPicker::make('text_color')->label(__('commero::admin.resources.order_status.text_color'))->default('#ffffff'),
            FileUpload::make('icon')
                ->label(__('commero::admin.common.icon'))
                ->disk('public')
                ->directory('order-statuses')
                ->visibility('public')
                ->image(),
            TextInput::make('sort')->label(__('commero::admin.common.sort'))->numeric()->default(0)->required(),
            Toggle::make('is_active')->label(__('commero::admin.common.is_active'))->default(true),
            Toggle::make('is_default_for_new_order')
                ->label(__('commero::admin.resources.order_status.is_default_for_new_order'))
                ->default(false)
                ->helperText(__('commero::admin.resources.order_status.is_default_for_new_order_hint')),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('code')->label(__('commero::admin.common.code'))->searchable()->sortable(),
                TextColumn::make('translation_name')
                    ->label(__('commero::admin.common.name'))
                    ->state(fn (OrderStatus $record): ?string => $record->translation(app()->getLocale())?->name ?? $record->getRawOriginal('name'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $nestedQuery) use ($search): void {
                            $nestedQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhereHas('translations', fn (Builder $translationsQuery): Builder => $translationsQuery->where('name', 'like', "%{$search}%"));
                        });
                    }),
                ColorColumn::make('color')->label(__('commero::admin.resources.order_status.badge_background_color')),
                IconColumn::make('is_active')->label(__('commero::admin.common.is_active'))->boolean(),
                IconColumn::make('is_default_for_new_order')->label(__('commero::admin.resources.order_status.is_default_for_new_order'))->boolean(),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton()
                    ->modalHeading(__('commero::admin.resources.order_status.delete_confirm')),
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
            'index' => Pages\ListOrderStatuses::route('/'),
            'create' => Pages\CreateOrderStatus::route('/create'),
            'edit' => Pages\EditOrderStatus::route('/{record}/edit'),
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
            ->maxLength(255)
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }
}
