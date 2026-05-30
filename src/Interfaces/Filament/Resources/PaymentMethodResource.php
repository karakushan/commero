<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\PaymentMethodResource\Pages;
use Commero\Models\PaymentMethod;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
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

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.payment_method.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.navigation.orders');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.payment_method.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.payment_method.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            TextInput::make('code')->label(__('admin.common.code'))->required()->unique(ignoreRecord: true),
            ...static::mainTranslationSections(),
            TextInput::make('sort')->label(__('admin.common.sort'))->numeric()->default(0)->required(),
            FileUpload::make('icon')
                ->label(__('admin.common.icon'))
                ->disk('public')
                ->directory('payment-methods')
                ->visibility('public')
                ->image(),
            Toggle::make('is_active')->label(__('admin.common.is_active'))->default(true),
            ...static::descriptionTranslationSections(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('icon')
                    ->label(__('admin.common.icon'))
                    ->disk('public')
                    ->square(),
                TextColumn::make('code')->label(__('admin.common.code'))->searchable(),
                TextColumn::make('translation_name')
                    ->label(__('admin.common.name'))
                    ->state(fn (PaymentMethod $record): ?string => $record->translation(app()->getLocale())?->name ?? $record->getRawOriginal('name'))
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
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
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
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, TextInput>
     */
    protected static function mainTranslationSections(): array
    {
        return array_map(fn (string $locale): TextInput => TextInput::make("translations.{$locale}.name")
            ->label(__('admin.common.name'))
            ->required($locale === Locales::default())
            ->dehydratedWhenHidden()
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    /**
     * @return array<int, Textarea>
     */
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
