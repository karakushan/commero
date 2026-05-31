<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\CityCategoryResource\Pages;
use Commero\Models\Category;
use Commero\Models\CityCategory;
use Commero\Models\Link;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Filament\PageContentBlocks;
use Commero\Support\Locales;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder as FormBuilder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CityCategoryResource extends Resource
{
    protected static ?string $model = CityCategory::class;

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.city_category.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.city_category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.city_category.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            Tabs::make('City Category Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make(__('commero::admin.category.tabs.main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            ...static::mainTranslationSections(),
                            Select::make('parent_id')
                                ->label(__('commero::admin.common.parent_category'))
                                ->relationship('parent', 'id')
                                ->getOptionLabelFromRecordUsing(fn (CityCategory $record) => $record->translation(app()->getLocale())?->name ?? $record->id)
                                ->searchable()
                                ->preload(),
                            TextInput::make('depth')->label(__('commero::admin.common.depth'))->numeric()->default(0)->required(),
                            TextInput::make('sort')->label(__('commero::admin.common.sort'))->numeric()->default(0)->required(),
                            Select::make('display_category_ids')
                                ->label(__('commero::admin.city_category.display_category_ids'))
                                ->options(fn (): array => Category::query()
                                    ->withTranslationsFor(app()->getLocale())
                                    ->orderBy('sort')
                                    ->orderBy('path')
                                    ->get()
                                    ->mapWithKeys(fn (Category $record): array => [
                                        $record->getKey() => $record->translation(app()->getLocale())?->name ?? (string) $record->getKey(),
                                    ])
                                    ->all())
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->columnSpanFull(),
                            FileUpload::make('icon_path')
                                ->label(__('commero::admin.common.icon'))
                                ->disk('public')
                                ->directory('city-categories/icons')
                                ->visibility('public')
                                ->image(),
                            FileUpload::make('thumbnail_path')
                                ->label(__('commero::admin.common.thumbnail'))
                                ->disk('public')
                                ->directory('city-categories/thumbnails')
                                ->visibility('public')
                                ->image()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Tabs\Tab::make(__('commero::admin.category.tabs.content'))
                        ->icon('heroicon-o-rectangle-group')
                        ->schema([
                            ...static::contentTranslationSections(),
                        ]),
                    Tabs\Tab::make(__('commero::admin.category.tabs.seo'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            ...static::seoTranslationSections(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                ImageColumn::make('icon_path')
                    ->label(__('commero::admin.common.icon'))
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(fn (): string => asset('images/shophats/placeholders/image-placeholder.svg')),
                TextColumn::make('translation_name')
                    ->label(__('commero::admin.common.name'))
                    ->html()
                    ->state(function (CityCategory $record): string {
                        $name = e($record->translation(app()->getLocale())?->name ?? '');
                        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', max(0, (int) $record->depth));
                        $prefix = $record->depth > 0 ? '&#8627;&nbsp;' : '';

                        return $indent.$prefix.$name;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = mb_strtolower(trim($search));
                        $likeSearch = "%{$search}%";

                        return $query->where(function (Builder $builder) use ($likeSearch): void {
                            $builder
                                ->whereRaw('LOWER(city_categories.path) LIKE ?', [$likeSearch])
                                ->orWhereHas('translations', function (Builder $translations) use ($likeSearch): void {
                                    $translations
                                        ->whereRaw('LOWER(name) LIKE ?', [$likeSearch])
                                        ->orWhereRaw('LOWER(slug) LIKE ?', [$likeSearch]);
                                })
                                ->orWhereHas('parent.translations', function (Builder $translations) use ($likeSearch): void {
                                    $translations->whereRaw('LOWER(name) LIKE ?', [$likeSearch]);
                                });
                        });
                    }),
                TextColumn::make('parent_name')
                    ->label(__('commero::admin.common.parent_category'))
                    ->state(fn (CityCategory $record): ?string => $record->parent?->translation(app()->getLocale())?->name),
                TextColumn::make('categories_count')
                    ->label(__('commero::admin.common.categories'))
                    ->counts('categories'),
            ])
            ->recordActions([
                Action::make('viewCategory')
                    ->label(__('commero::admin.category.actions.view_on_site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (CityCategory $record): ?string => static::getFrontendCategoryUrl($record))
                    ->openUrlInNewTab()
                    ->iconButton()
                    ->hidden(fn (CityCategory $record): bool => blank(static::getFrontendCategoryUrl($record))),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ])
            ->reorderable('sort')
            ->defaultSort('path');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withTranslationsFor(app()->getLocale())
            ->with(['parent' => fn ($query) => $query->withTranslationsFor(app()->getLocale())]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCityCategories::route('/'),
            'create' => Pages\CreateCityCategory::route('/create'),
            'edit' => Pages\EditCityCategory::route('/{record}/edit'),
        ];
    }

    public static function getFrontendCategoryUrl(CityCategory $category, ?string $locale = null): ?string
    {
        return $category->frontendUrl($locale);
    }

    protected static function mainTranslationSections(): array
    {
        return array_map(fn (string $locale): Grid => Grid::make(2)
            ->schema([
                TextInput::make("translations.{$locale}.name")
                    ->label(__('commero::admin.common.name'))
                    ->required($locale === Locales::default())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $old, $get, $set, ?CityCategory $record) use ($locale): void {
                        $slugPath = "translations.{$locale}.slug";
                        $currentSlug = $get($slugPath);
                        $previousGeneratedSlug = static::generateUniqueSiteSlug((string) $old, $locale, $record);

                        if (filled($currentSlug) && $currentSlug !== $previousGeneratedSlug) {
                            return;
                        }

                        $set($slugPath, static::generateUniqueSiteSlug((string) $state, $locale, $record));
                    })
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
            ])
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    protected static function contentTranslationSections(): array
    {
        return array_map(fn (string $locale): FormBuilder => FormBuilder::make("translations.{$locale}.blocks")
            ->label(__('commero::admin.common.content'))
            ->default([])
            ->blocks(PageContentBlocks::forPages())
            ->blockIcons()
            ->collapsible()
            ->collapsed()
            ->reorderableWithButtons()
            ->addActionLabel(__('commero::admin.content.add_block'))
            ->blockPickerColumns(2)
            ->blockPickerWidth('xl')
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    protected static function seoTranslationSections(): array
    {
        return array_map(fn (string $locale): Grid => Grid::make(3)
            ->schema([
                TextInput::make("translations.{$locale}.slug")
                    ->label(__('commero::admin.common.slug'))
                    ->live(onBlur: true)
                    ->afterStateHydrated(function ($state, $get, $set, ?CityCategory $record) use ($locale): void {
                        if (filled($state)) {
                            return;
                        }

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSiteSlug((string) $get("translations.{$locale}.name"), $locale, $record),
                        );
                    })
                    ->afterStateUpdated(function ($state, $get, $set, ?CityCategory $record) use ($locale): void {
                        $source = filled($state)
                            ? (string) $state
                            : (string) $get("translations.{$locale}.name");

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSiteSlug($source, $locale, $record),
                        );
                    })
                    ->dehydratedWhenHidden(),
                Select::make("translations.{$locale}.robots")
                    ->label(__('commero::admin.content.seo.robots'))
                    ->options([
                        'index, follow' => __('commero::admin.content.seo.robots_options.index_follow'),
                        'noindex, follow' => __('commero::admin.content.seo.robots_options.noindex_follow'),
                        'index, nofollow' => __('commero::admin.content.seo.robots_options.index_nofollow'),
                        'noindex, nofollow' => __('commero::admin.content.seo.robots_options.noindex_nofollow'),
                    ])
                    ->default('index, follow')
                    ->placeholder(__('commero::admin.resources.page.robots_default'))
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_title")
                    ->label(__('commero::admin.content.seo.meta_title'))
                    ->rows(2)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_description")
                    ->label(__('commero::admin.content.seo.meta_description'))
                    ->rows(3)
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
            ])
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    public static function normalizeSlug(?string $value): string
    {
        return Str::slug((string) $value);
    }

    /**
     * @param  array<int, string>  $reservedSlugs
     */
    public static function generateUniqueSiteSlug(
        ?string $value,
        string $locale,
        ?CityCategory $record = null,
        array $reservedSlugs = [],
    ): ?string
    {
        return Link::generateUniqueSlug(
            value: static::normalizeSlug($value),
            locale: $locale,
            entityType: Link::ENTITY_CITY_CATEGORY,
            entityId: $record?->getKey(),
            reservedSlugs: $reservedSlugs,
        );
    }
}
