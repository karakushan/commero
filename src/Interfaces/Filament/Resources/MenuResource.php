<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\MenuResource\Pages;
use Commero\Models\Menu;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Filament\Forms\Components\Hidden;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MenuResource extends AdminResource
{
    protected static ?string $model = Menu::class;

    protected static ?int $navigationSort = 4;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3-bottom-left';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.menu.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.system');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.menu.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.menu.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            Tabs::make('Menu Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make(__('commero::admin.menu.tabs.main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('name')
                                        ->label(__('commero::admin.common.name'))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('identifier')
                                        ->label(__('commero::admin.common.identifier'))
                                        ->required()
                                        ->alphaDash()
                                        ->maxLength(100)
                                        ->placeholder('footer-information')
                                        ->helperText(__('commero::admin.menu.identifier_hint'))
                                        ->unique(ignoreRecord: true),
                                    Toggle::make('is_active')
                                        ->label(__('commero::admin.common.is_active'))
                                        ->default(true),
                                ])
                                ->columns(2),
                        ]),
                    Tabs\Tab::make(__('commero::admin.menu.tabs.items'))
                        ->icon('heroicon-o-queue-list')
                        ->schema([
                            Repeater::make('items')
                                ->label(__('commero::admin.menu.items'))
                                ->addActionLabel(__('commero::admin.menu.add_item'))
                                ->schema([
                                    Hidden::make('id'),
                                    TextInput::make('sort')
                                        ->label(__('commero::admin.common.sort'))
                                        ->numeric()
                                        ->default(0)
                                        ->required(),
                                    Toggle::make('is_active')
                                        ->label(__('commero::admin.common.is_active'))
                                        ->default(true),
                                    Toggle::make('open_in_new_tab')
                                        ->label(__('commero::admin.menu.open_in_new_tab'))
                                        ->default(false),
                                    ...static::itemTranslationSections(),
                                ])
                                ->columns(3)
                                ->columnSpanFull()
                                ->collapsible()
                                ->collapsed()
                                ->cloneable()
                                ->itemLabel(function (array $state): ?string {
                                    $translations = collect($state['translations'] ?? []);
                                    $defaultLocale = Locales::default();

                                    return data_get($translations, "{$defaultLocale}.label")
                                        ?? $translations->pluck('label')->filter()->first()
                                        ?? null;
                                }),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('commero::admin.common.name'))
                    ->searchable(),
                TextColumn::make('identifier')
                    ->label(__('commero::admin.common.identifier'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('items_count')
                    ->label(__('commero::admin.menu.items_count'))
                    ->counts('items'),
                IconColumn::make('is_active')
                    ->label(__('commero::admin.common.is_active'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('commero::admin.common.updated_at'))
                    ->dateTime()
                    ->sortable(),
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
        return parent::getEloquentQuery()->withCount('items');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Grid>
     */
    protected static function itemTranslationSections(): array
    {
        return array_map(fn (string $locale): \Filament\Schemas\Components\Grid => \Filament\Schemas\Components\Grid::make(2)
            ->schema([
                TextInput::make("translations.{$locale}.label")
                    ->label(__('commero::admin.common.label'))
                    ->required(Locales::default() === $locale)
                    ->maxLength(255)
                    ->dehydratedWhenHidden(),
                TextInput::make("translations.{$locale}.url")
                    ->label(__('commero::admin.common.url'))
                    ->required(Locales::default() === $locale)
                    ->maxLength(2048)
                    ->placeholder('/delivery-and-payment')
                    ->dehydratedWhenHidden(),
            ])
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }
}
