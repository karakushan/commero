<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\CategoryResource\Pages;
use Commero\Models\Category;
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

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.category.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.navigation.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.category.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            Tabs::make('Category Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make(__('admin.category.tabs.main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            ...static::mainTranslationSections(),
                            Select::make('parent_id')->label(__('admin.common.parent_category'))->relationship('parent', 'id')->getOptionLabelFromRecordUsing(fn (Category $record) => $record->translation(app()->getLocale())?->name ?? $record->id)->searchable()->preload(),
                            TextInput::make('depth')->label(__('admin.common.depth'))->numeric()->default(0)->required(),
                            TextInput::make('sort')->label(__('admin.common.sort'))->numeric()->default(0)->required(),
                            FileUpload::make('icon_path')
                                ->label(__('admin.common.icon'))
                                ->disk('public')
                                ->directory('categories/icons')
                                ->visibility('public')
                                ->image(),
                            FileUpload::make('thumbnail_path')
                                ->label(__('admin.common.thumbnail'))
                                ->disk('public')
                                ->directory('categories/thumbnails')
                                ->visibility('public')
                                ->image()
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Tabs\Tab::make(__('admin.category.tabs.content'))
                        ->icon('heroicon-o-rectangle-group')
                        ->schema([
                            ...static::contentTranslationSections(),
                        ]),
                    Tabs\Tab::make(__('admin.category.tabs.seo'))
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
                    ->label(__('admin.common.icon'))
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(fn (): string => asset('images/shophats/placeholders/image-placeholder.svg')),
                TextColumn::make('translation_name')
                    ->label(__('admin.common.name'))
                    ->html()
                    ->state(function (Category $record): string {
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
                                ->whereRaw('LOWER(categories.path) LIKE ?', [$likeSearch])
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
                    ->label(__('admin.common.parent_category'))
                    ->state(fn (Category $record): ?string => $record->parent?->translation(app()->getLocale())?->name),
                TextColumn::make('products_count')
                    ->label(__('admin.resources.product.plural'))
                    ->counts('products'),
            ])
            ->recordActions([
                Action::make('viewCategory')
                    ->label(__('admin.category.actions.view_on_site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Category $record): ?string => static::getFrontendCategoryUrl($record))
                    ->openUrlInNewTab()
                    ->iconButton()
                    ->hidden(fn (Category $record): bool => blank(static::getFrontendCategoryUrl($record))),
                Action::make('products')
                    ->label(__('admin.resources.product.plural'))
                    ->icon('heroicon-o-shopping-bag')
                    ->url(fn (Category $record): string => ProductResource::getUrl('index', [
                        'tableFilters' => [
                            'categories' => [
                                'values' => [(string) $record->getKey()],
                            ],
                        ],
                    ]))
                    ->iconButton(),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getFrontendCategoryUrl(Category $category, ?string $locale = null): ?string
    {
        return $category->frontendUrl($locale);
    }

    /**
     * @return array<int, Grid>
     */
    protected static function mainTranslationSections(): array
    {
        return array_map(fn (string $locale): Grid => Grid::make(2)
            ->schema([
                TextInput::make("translations.{$locale}.name")
                    ->label(__('admin.common.name'))
                    ->required($locale === Locales::default())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $old, $get, $set, ?Category $record) use ($locale): void {
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

    /**
     * @return array<int, FormBuilder>
     */
    protected static function contentTranslationSections(): array
    {
        return array_map(fn (string $locale): FormBuilder => FormBuilder::make("translations.{$locale}.blocks")
            ->label(__('admin.common.content'))
            ->default([])
            ->blocks(PageContentBlocks::forPages())
            ->blockIcons()
            ->collapsible()
            ->collapsed()
            ->reorderableWithButtons()
            ->addActionLabel(__('admin.content.add_block'))
            ->blockPickerColumns(2)
            ->blockPickerWidth('xl')
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    /**
     * @return array<int, Grid>
     */
    protected static function seoTranslationSections(): array
    {
        return array_map(fn (string $locale): Grid => Grid::make(3)
            ->schema([
                TextInput::make("translations.{$locale}.slug")
                    ->label(__('admin.common.slug'))
                    ->live(onBlur: true)
                    ->afterStateHydrated(function ($state, $get, $set, ?Category $record) use ($locale): void {
                        if (filled($state)) {
                            return;
                        }

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSiteSlug((string) $get("translations.{$locale}.name"), $locale, $record),
                        );
                    })
                    ->afterStateUpdated(function ($state, $get, $set, ?Category $record) use ($locale): void {
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
                    ->label(__('admin.content.seo.robots'))
                    ->options([
                        'index, follow' => __('admin.content.seo.robots_options.index_follow'),
                        'noindex, follow' => __('admin.content.seo.robots_options.noindex_follow'),
                        'index, nofollow' => __('admin.content.seo.robots_options.index_nofollow'),
                        'noindex, nofollow' => __('admin.content.seo.robots_options.noindex_nofollow'),
                    ])
                    ->default('index, follow')
                    ->placeholder(__('admin.resources.page.robots_default'))
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_title")
                    ->label(__('admin.content.seo.meta_title'))
                    ->rows(2)
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_description")
                    ->label(__('admin.content.seo.meta_description'))
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
        ?Category $record = null,
        array $reservedSlugs = [],
    ): ?string {
        return Link::generateUniqueSlug(
            value: static::normalizeSlug($value),
            locale: $locale,
            entityType: Link::ENTITY_CATEGORY,
            entityId: $record?->getKey(),
            reservedSlugs: $reservedSlugs,
        );
    }
}
