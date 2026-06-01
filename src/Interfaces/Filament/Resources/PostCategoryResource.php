<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\PostCategoryResource\Pages;
use Commero\Models\PostCategory;
use Commero\Models\PostCategoryTranslation;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PostCategoryResource extends AdminResource
{
    protected static ?string $model = PostCategory::class;

    protected static ?int $navigationSort = 2;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.post_category.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.content');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.post_category.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.post_category.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            Tabs::make('Post Category Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make(__('commero::admin.content.tabs.main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            ...static::mainTranslationSections(),
                            Select::make('parent_id')
                                ->label(__('commero::admin.common.parent_category'))
                                ->relationship(
                                    'parent',
                                    'id',
                                    fn (Builder $query): Builder => $query->withTranslationsFor(app()->getLocale()),
                                )
                                ->getOptionLabelFromRecordUsing(fn (PostCategory $record): string => $record->translation(app()->getLocale())?->name ?? (string) $record->id)
                                ->searchable()
                                ->preload(),
                            TextInput::make('depth')->label(__('commero::admin.common.depth'))->numeric()->default(0)->required(),
                            TextInput::make('sort')->label(__('commero::admin.common.sort'))->numeric()->default(0)->required(),
                        ])
                        ->columns(2),
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
            ->columns([
                TextColumn::make('id')->label(__('commero::admin.common.id'))->sortable(),
                TextColumn::make('path')->label(__('commero::admin.common.path'))->searchable(),
                TextColumn::make('translation_name')
                    ->label(__('commero::admin.common.name'))
                    ->state(fn (PostCategory $record): ?string => $record->translation(app()->getLocale())?->name),
                TextColumn::make('depth')->label(__('commero::admin.common.depth'))->sortable(),
                TextColumn::make('updated_at')->label(__('commero::admin.common.updated_at'))->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('viewCategory')
                    ->label(__('commero::admin.category.actions.view_on_site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (PostCategory $record): ?string => static::getFrontendCategoryUrl($record))
                    ->openUrlInNewTab()
                    ->iconButton()
                    ->hidden(fn (PostCategory $record): bool => blank(static::getFrontendCategoryUrl($record))),
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                DeleteBulkAction::make(),
            ])
            ->reorderable('sort')
            ->defaultSort('sort');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTranslationsFor(app()->getLocale());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostCategories::route('/'),
            'create' => Pages\CreatePostCategory::route('/create'),
            'edit' => Pages\EditPostCategory::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, Grid>
     */
    protected static function mainTranslationSections(): array
    {
        return array_map(fn (string $locale): Grid => Grid::make(2)
            ->schema([
                TextInput::make("translations.{$locale}.name")
                    ->label(__('commero::admin.common.name'))
                    ->required($locale === Locales::default())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $old, $get, $set, ?PostCategory $record) use ($locale): void {
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
            ])
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
                    ->label(__('commero::admin.common.slug'))
                    ->live(onBlur: true)
                    ->afterStateHydrated(function ($state, $get, $set, ?PostCategory $record) use ($locale): void {
                        if (filled($state)) {
                            return;
                        }

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSlug((string) $get("translations.{$locale}.name"), $locale, $record),
                        );
                    })
                    ->afterStateUpdated(function ($state, $get, $set, ?PostCategory $record) use ($locale): void {
                        $source = filled($state)
                            ? (string) $state
                            : (string) $get("translations.{$locale}.name");

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSlug($source, $locale, $record),
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

    public static function generateUniqueSlug(?string $value, string $locale, ?PostCategory $record = null): ?string
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

    protected static function localizedSlugExists(string $slug, string $locale, ?PostCategory $record = null): bool
    {
        $postCategoryId = $record?->getKey();

        return PostCategoryTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->when($postCategoryId, fn (Builder $query): Builder => $query->where('post_category_id', '!=', $postCategoryId))
            ->exists();
    }

    public static function getFrontendCategoryUrl(PostCategory $category, ?string $locale = null): ?string
    {
        $resolvedLocale = Locales::resolve($locale ?? app()->getLocale());
        $slug = $category->localizedSlug($resolvedLocale, $category->path);

        if (! filled($slug)) {
            return null;
        }

        return Locales::isDefault($resolvedLocale)
            ? route('blog.category', ['slug' => $slug])
            : route('localized.blog.category', ['locale' => $resolvedLocale, 'slug' => $slug]);
    }
}
