<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\PostResource\Pages;
use Commero\Models\Post;
use Commero\Models\PostCategory;
use Commero\Models\PostTranslation;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Filament\RichEditorDocumentNormalizer;
use Commero\Support\Filament\RichEditorCustomBlockAction;
use Commero\Support\Filament\RichContentCustomBlocks\AccentQuoteBlock;
use Commero\Support\Filament\RichContentCustomBlocks\VideoEmbedBlock;
use Commero\Support\Locales;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class PostResource extends AdminResource
{
    protected static ?string $model = Post::class;

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.post.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.content');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.post.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.post.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            Tabs::make('Post Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make(__('commero::admin.content.tabs.main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            ...static::mainTranslationSections(),
                            Select::make('post_category_id')
                                ->label(__('commero::admin.resources.post_category.navigation'))
                                ->relationship(
                                    'category',
                                    'id',
                                    fn (Builder $query): Builder => $query->withTranslationsFor(app()->getLocale()),
                                )
                                ->getOptionLabelFromRecordUsing(fn (PostCategory $record): string => $record->translation(app()->getLocale())?->name ?? (string) $record->id)
                                ->searchable()
                                ->preload()
                                ->columnSpan(1),
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
                            FileUpload::make('thumbnail_path')
                                ->label(__('commero::admin.common.thumbnail'))
                                ->disk('public')
                                ->directory('content/posts')
                                ->visibility('public')
                                ->image()
                                ->columnSpanFull(),
                        ])->columns(2),
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
                ImageColumn::make('thumbnail_path')
                    ->label('')
                    ->disk('public')
                    ->height(40)
                    ->width(60)
                    ->defaultImageUrl(fn (): string => asset('images/shophats/placeholders/image-placeholder.svg')),
                TextColumn::make('translation_title')
                    ->label(__('commero::admin.common.title'))
                    ->state(fn (Post $record): ?string => $record->translation(app()->getLocale())?->title)
                    ->searchable(false),
                TextColumn::make('category_name')
                    ->label(__('commero::admin.resources.post_category.singular'))
                    ->state(fn (Post $record): ?string => $record->category?->translation(app()->getLocale())?->name),
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
                SelectFilter::make('post_category_id')
                    ->label(__('commero::admin.resources.post_category.navigation'))
                    ->relationship(
                        'category',
                        'id',
                        fn (Builder $query): Builder => $query->withTranslationsFor(app()->getLocale()),
                    )
                    ->getOptionLabelFromRecordUsing(fn (PostCategory $record): string => $record->translation(app()->getLocale())?->name ?? (string) $record->id),
            ])
            ->recordActions([
                Action::make('viewPost')
                    ->label(__('commero::admin.post.actions.view_on_site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Post $record): ?string => static::getFrontendPostUrl($record, app()->getLocale()))
                    ->openUrlInNewTab()
                    ->iconButton()
                    ->hidden(fn (Post $record): bool => blank(static::getFrontendPostUrl($record, app()->getLocale()))),
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
        return parent::getEloquentQuery()
            ->with(['translations', 'category.translations']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    public static function getFrontendPostUrl(Post $post, ?string $locale = null): ?string
    {
        $post = $post->loadMissing('translations');
        $activeLocale = $locale ?? app()->getLocale();
        $defaultLocale = Locales::default();
        $activeTranslation = $post->translation($activeLocale);

        if (filled($activeTranslation?->slug)) {
            return static::buildFrontendPostUrl($activeLocale, $activeTranslation->slug);
        }

        $defaultTranslation = $post->translation($defaultLocale);

        if (filled($defaultTranslation?->slug)) {
            return static::buildFrontendPostUrl(
                Locales::isDefault($activeLocale) ? $defaultLocale : $activeLocale,
                $defaultTranslation->slug,
            );
        }

        $translationWithSlug = $post->translations
            ->first(fn ($translation): bool => filled($translation->slug));

        if (filled($translationWithSlug?->slug) && filled($translationWithSlug?->locale)) {
            return static::buildFrontendPostUrl(
                Locales::isDefault($activeLocale) ? $translationWithSlug->locale : $activeLocale,
                $translationWithSlug->slug,
            );
        }

        return null;
    }

    private static function buildFrontendPostUrl(string $locale, string $slug): string
    {
        return Locales::isDefault($locale)
            ? route('post.show', ['slug' => $slug])
            : route('localized.post.show', ['locale' => $locale, 'slug' => $slug]);
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
                    ->required($locale === \Commero\Support\Locales::default())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $old, $get, $set, ?Post $record) use ($locale): void {
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
                ToggleButtons::make("translations.{$locale}.content_editor_mode")
                    ->label(__('commero::admin.resources.post.editor.mode.label'))
                    ->options([
                        'visual' => __('commero::admin.resources.post.editor.mode.visual'),
                        'html' => __('commero::admin.resources.post.editor.mode.html'),
                    ])
                    ->default('visual')
                    ->inline()
                    ->grouped()
                    ->live()
                    ->columnSpanFull()
                    ->dehydrated(false),
                RichEditor::make("translations.{$locale}.content")
                    ->label(__('commero::admin.common.content'))
                    ->registerActions([
                        RichEditorCustomBlockAction::make(),
                    ])
                    ->afterStateHydrated(function (RichEditor $component, $state): void {
                        $component->state(RichEditorDocumentNormalizer::ensureTrailingParagraph($state));
                    })
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('content/posts/editor')
                    ->fileAttachmentsVisibility('public')
                    ->fileAttachmentsAcceptedFileTypes([
                        'image/png',
                        'image/jpeg',
                        'image/gif',
                        'image/webp',
                    ])
                    ->customBlocks([
                        AccentQuoteBlock::class,
                        VideoEmbedBlock::class,
                    ])
                    ->tools([
                        RichEditorTool::make('accentQuote')
                            ->label(__('commero::admin.resources.post.editor.accent_quote.label'))
                            ->action(
                                action: 'customBlock',
                                arguments: "{ id: 'accent-quote', mode: 'insert' }",
                            )
                            ->icon(Heroicon::ChatBubbleBottomCenterText),
                        RichEditorTool::make('videoEmbed')
                            ->label(__('commero::admin.resources.post.editor.video_embed.label'))
                            ->action(
                                action: 'customBlock',
                                arguments: "{ id: 'video-embed', mode: 'insert' }",
                            )
                            ->icon(Heroicon::OutlinedPlayCircle),
                    ])
                    ->enableToolbarButtons([
                        'accentQuote',
                        'videoEmbed',
                    ])
                    ->disableToolbarButtons([
                        'customBlocks',
                    ])
                    ->resizableImages()
                    ->afterStateHydrated(function ($state, $set) use ($locale): void {
                        $set("translations.{$locale}.content_html_source", $state);
                    })
                    ->afterStateUpdated(function ($state, $set) use ($locale): void {
                        $set("translations.{$locale}.content_html_source", $state);
                    })
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'hat-shop-rich-editor',
                        'style' => '--hat-shop-rich-editor-min-height: 24rem; min-height: var(--hat-shop-rich-editor-min-height);',
                    ])
                    ->hidden(fn ($get): bool => ($get("translations.{$locale}.content_editor_mode") ?? 'visual') === 'html')
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.content_html_source")
                    ->label(__('commero::admin.resources.post.editor.html_source'))
                    ->rows(20)
                    ->live()
                    ->afterStateHydrated(function ($state, $set, $get) use ($locale): void {
                        $set("translations.{$locale}.content_html_source", $state ?? $get("translations.{$locale}.content"));
                    })
                    ->afterStateUpdated(function ($state, $set) use ($locale): void {
                        $set("translations.{$locale}.content", $state);
                    })
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'font-mono text-xs',
                        'spellcheck' => 'false',
                    ])
                    ->helperText(__('commero::admin.resources.post.editor.html_source_hint'))
                    ->hidden(fn ($get): bool => ($get("translations.{$locale}.content_editor_mode") ?? 'visual') !== 'html')
                    ->dehydrated(false),
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
                    ->afterStateHydrated(function ($state, $get, $set, ?Post $record) use ($locale): void {
                        if (filled($state)) {
                            return;
                        }

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSlug((string) $get("translations.{$locale}.title"), $locale, $record),
                        );
                    })
                    ->afterStateUpdated(function ($state, $get, $set, ?Post $record) use ($locale): void {
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
                    ->default('index, follow')
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_title")
                    ->label(__('commero::admin.content.seo.meta_title'))
                    ->rows(2)
                    ->maxLength(255)
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_description")
                    ->label(__('commero::admin.content.seo.meta_description'))
                    ->rows(2)
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
            ])
            ->columnSpanFull()
            ->hidden(fn ($livewire): bool => data_get($livewire, 'activeLocale') !== $locale), AdminLocales::supported());
    }

    public static function normalizeSlug(?string $value): string
    {
        return Str::slug((string) $value);
    }

    public static function generateUniqueSlug(?string $value, string $locale, ?Post $record = null): ?string
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

    protected static function localizedSlugExists(string $slug, string $locale, ?Post $record = null): bool
    {
        $postId = $record?->getKey();

        return PostTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->when($postId, fn (Builder $query): Builder => $query->where('post_id', '!=', $postId))
            ->exists();
    }
}
