<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\PageResource\Pages;
use Commero\Models\Link;
use Commero\Models\Page as ContentPage;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Filament\PageContentBlocks;
use Commero\Support\Locales;
use Filament\Actions\Action as TableAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = ContentPage::class;

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.page.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.content');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.page.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.page.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            Tabs::make('Page Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make(__('commero::admin.content.tabs.main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            ...static::mainTranslationSections(),
                            Select::make('status')
                                ->label(__('commero::admin.common.status'))
                                ->options([
                                    'draft' => __('commero::admin.content.status.draft'),
                                    'published' => __('commero::admin.content.status.published'),
                                ])
                                ->required()
                                ->default('draft')
                                ->columnSpan(1),
                            DateTimePicker::make('published_at')
                                ->label(__('commero::admin.common.published_at'))
                                ->seconds(false)
                                ->columnSpan(1),
                            TextInput::make('sort')
                                ->label(__('commero::admin.common.sort'))
                                ->numeric()
                                ->default(0)
                                ->required()
                                ->columnSpan(1),
                        ])->columns(2),
                    Tabs\Tab::make(__('commero::admin.content.tabs.content'))
                        ->icon('heroicon-o-rectangle-group')
                        ->schema([
                            ...static::contentTranslationSections(),
                        ]),
                    Tabs\Tab::make(__('commero::admin.content.tabs.design'))
                        ->icon('heroicon-o-swatch')
                        ->schema([
                            ...static::designTranslationSections(),
                        ]),
                    Tabs\Tab::make(__('commero::admin.content.tabs.seo'))
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
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('id')->label(__('commero::admin.common.id'))->sortable(),
                TextColumn::make('translation_title')
                    ->label(__('commero::admin.common.title'))
                    ->state(fn (ContentPage $record): ?string => $record->translation(app()->getLocale())?->title),
                TextColumn::make('default_locale_slug')
                    ->label(__('commero::admin.common.slug').' ('.strtoupper(Locales::default()).')')
                    ->state(fn (ContentPage $record): ?string => $record->translation(Locales::default())?->slug),
                TextColumn::make('status')
                    ->label(__('commero::admin.common.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('commero::admin.content.status.'.$state))
                    ->color(fn (string $state): string => $state === 'published' ? 'success' : 'gray'),
                TextColumn::make('published_at')->label(__('commero::admin.common.published_at'))->dateTime()->sortable(),
                TextColumn::make('updated_at')->label(__('commero::admin.common.updated_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('commero::admin.common.status'))
                    ->options([
                        'draft' => __('commero::admin.content.status.draft'),
                        'published' => __('commero::admin.content.status.published'),
                    ]),
            ])
            ->recordActions([
                TableAction::make('viewPage')
                    ->label(__('commero::admin.page.actions.view_on_site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ContentPage $record): ?string => static::getDefaultFrontendPageUrl($record))
                    ->openUrlInNewTab()
                    ->iconButton()
                    ->hidden(fn (ContentPage $record): bool => blank(static::getDefaultFrontendPageUrl($record))),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ]);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return parent::getEloquentQuery()->with('translations');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }

    protected static function getDefaultFrontendPageUrl(ContentPage $record): ?string
    {
        return page_url($record, Locales::default());
    }

    /**
     * @return array<int, Grid>
     */
    protected static function mainTranslationSections(): array
    {
        return array_map(fn (string $locale): Grid => Grid::make(2)
            ->schema([
                TextInput::make("translations.{$locale}.title")
                    ->label(__('commero::admin.common.title'))
                    ->required(Locales::default() === $locale)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $old, $get, $set, ?ContentPage $record) use ($locale): void {
                        $slugPath = "translations.{$locale}.slug";
                        $currentSlug = $get($slugPath);
                        $previousGeneratedSlug = static::generateUniqueSlug((string) $old, $locale, $record);

                        if (filled($currentSlug) && $currentSlug !== $previousGeneratedSlug) {
                            return;
                        }

                        $set($slugPath, static::generateUniqueSlug((string) $state, $locale, $record));
                    })
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.excerpt")
                    ->label(__('commero::admin.common.excerpt'))
                    ->rows(3)
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
            ])
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    /**
     * @return array<int, Builder>
     */
    protected static function contentTranslationSections(): array
    {
        return array_map(fn (string $locale): Builder => Builder::make("translations.{$locale}.blocks")
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

    /**
     * @return array<int, Grid>
     */
    protected static function designTranslationSections(): array
    {
        return array_map(fn (string $locale): Grid => Grid::make(2)
            ->schema([
                ColorPicker::make("translations.{$locale}.background_desktop_color")
                    ->label(__('commero::admin.content.page_background_desktop'))
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                ColorPicker::make("translations.{$locale}.background_mobile_color")
                    ->label(__('commero::admin.content.page_background_mobile'))
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                Toggle::make("translations.{$locale}.show_breadcrumbs")
                    ->label(__('commero::admin.content.show_breadcrumbs'))
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                Toggle::make("translations.{$locale}.show_title")
                    ->label(__('commero::admin.content.show_title'))
                    ->default(true)
                    ->inline(false)
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
            ])
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    /**
     * @return array<int, Grid>
     */
    protected static function seoTranslationSections(): array
    {
        return array_map(fn (string $locale): Grid => Grid::make(2)
            ->schema([
                TextInput::make("translations.{$locale}.slug")
                    ->label(__('commero::admin.common.slug'))
                    ->live(onBlur: true)
                    ->afterStateHydrated(function ($state, $get, $set, ?ContentPage $record) use ($locale): void {
                        if (filled($state)) {
                            return;
                        }

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSlug((string) $get("translations.{$locale}.title"), $locale, $record),
                        );
                    })
                    ->afterStateUpdated(function ($state, $get, $set, ?ContentPage $record) use ($locale): void {
                        $source = filled($state)
                            ? (string) $state
                            : (string) $get("translations.{$locale}.title");

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSlug($source, $locale, $record),
                        );
                    })
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
                Select::make("translations.{$locale}.robots")
                    ->label(__('commero::admin.content.seo.robots'))
                    ->options([
                        'index, follow' => __('commero::admin.content.seo.robots_options.index_follow'),
                        'noindex, follow' => __('commero::admin.content.seo.robots_options.noindex_follow'),
                        'index, nofollow' => __('commero::admin.content.seo.robots_options.index_nofollow'),
                        'noindex, nofollow' => __('commero::admin.content.seo.robots_options.noindex_nofollow'),
                    ])
                    ->placeholder(__('commero::admin.resources.page.robots_default'))
                    ->dehydratedWhenHidden()
                    ->columnSpan(1),
                Textarea::make("translations.{$locale}.meta_title")
                    ->label(__('commero::admin.content.seo.meta_title'))
                    ->rows(2)
                    ->maxLength(255)
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_description")
                    ->label(__('commero::admin.content.seo.meta_description'))
                    ->rows(2)
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

    public static function generateUniqueSlug(?string $value, string $locale, ?ContentPage $record = null): ?string
    {
        $baseSlug = static::normalizeSlug($value);

        if ($baseSlug === '') {
            return null;
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (static::localizedSlugExists($slug, $locale, $record)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected static function localizedSlugExists(string $slug, string $locale, ?ContentPage $record = null): bool
    {
        $pageId = $record?->getKey();

        return Link::query()
            ->forLocale($locale)
            ->where('slug', $slug)
            ->when($pageId, fn (EloquentBuilder $query): EloquentBuilder => $query->where(function (EloquentBuilder $builder) use ($pageId): void {
                $builder
                    ->where('entity_type', '!=', Link::ENTITY_PAGE)
                    ->orWhere('entity_id', '!=', $pageId);
            }))
            ->exists();
    }
}
