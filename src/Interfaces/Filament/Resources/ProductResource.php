<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\ProductResource\Pages;
use Commero\Models\AttributeOption;
use Commero\Models\Category;
use Commero\Models\Currency;
use Commero\Models\PageTranslation;
use Commero\Models\PostCategoryTranslation;
use Commero\Models\PostTranslation;
use Commero\Models\Product;
use Commero\Models\ProductAttribute;
use Commero\Models\ProductTranslation;
use Commero\Models\CategoryTranslation;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Filament\RichEditorDocumentNormalizer;
use Commero\Support\Filament\RichEditorCustomBlockAction;
use Commero\Support\Filament\RichContentCustomBlocks\VideoEmbedBlock;
use Commero\Support\Locales;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.product.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('admin.navigation.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.product.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.product.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            Tabs::make('Product Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make(__('admin.product.tabs.main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            ...static::mainTranslationSections(),
                            Select::make('brand_id')
                                ->label(__('admin.product.brand_id'))
                                ->relationship('brand', 'name')
                                ->searchable()
                                ->preload()
                                ->columnSpan(1),
                            Select::make('status')
                                ->options([
                                    'draft' => __('admin.product.status.draft'),
                                    'published' => __('admin.product.status.published'),
                                ])
                                ->label(__('admin.common.status'))
                                ->required()
                                ->default('draft')
                                ->columnSpan(1),
                            Select::make('type')
                                ->options([
                                    'simple' => __('admin.product.type.simple'),
                                    'variant' => __('admin.product.type.variant'),
                                ])
                                ->label(__('admin.common.type'))
                                ->required()
                                ->default('simple')
                                ->live()
                                ->columnSpan(1),
                            TextInput::make('sku')
                                ->label(__('admin.common.sku'))
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->visible(fn (callable $get): bool => $get('type') === 'simple')
                                ->columnSpan(1),
                            TextInput::make('price')
                                ->label(__('admin.common.price'))
                                ->numeric()
                                ->inputMode('decimal')
                                ->minValue(0)
                                ->required()
                                ->visible(fn (callable $get): bool => $get('type') === 'simple')
                                ->columnSpan(1),
                            TextInput::make('old_price')
                                ->label(__('admin.product.variants.old_price'))
                                ->numeric()
                                ->inputMode('decimal')
                                ->minValue(0)
                                ->visible(fn (callable $get): bool => $get('type') === 'simple')
                                ->columnSpan(1),
                            Select::make('stock_status')
                                ->label(__('admin.common.availability'))
                                ->options([
                                    'in_stock' => __('admin.product.stock_status.in_stock'),
                                    'out_of_stock' => __('admin.product.stock_status.out_of_stock'),
                                    'preorder' => __('admin.product.stock_status.preorder'),
                                ])
                                ->default('in_stock')
                                ->required()
                                ->columnSpan(1),
                            Select::make('category_ids')
                                ->label(__('admin.product.category_ids'))
                                ->relationship(
                                    'categories',
                                    'id',
                                    fn (Builder $query): Builder => $query->withTranslationsFor(app()->getLocale()),
                                )
                                ->getOptionLabelFromRecordUsing(
                                    fn (Category $record): string => $record->translation(app()->getLocale())?->name ?? (string) $record->id,
                                )
                                ->multiple()
                                ->preload()
                                ->searchable()
                                ->columnSpanFull(),
                        ])->columns(3),
                    Tabs\Tab::make(__('admin.product.tabs.gallery'))
                        ->icon('heroicon-o-photo')
                        ->schema([
                            FileUpload::make('gallery_uploads')
                                ->label(__('admin.product.gallery.bulk_upload'))
                                ->helperText(__('admin.product.gallery.bulk_upload_hint'))
                                ->disk('public')
                                ->directory('catalog/products/manual')
                                ->visibility('public')
                                ->image()
                                ->multiple()
                                ->reorderable()
                                ->appendFiles()
                                ->columnSpanFull(),
                            Repeater::make('images')->label('Зображення')
                                ->schema([
                                    FileUpload::make('path')
                                        ->label('Файл')
                                        ->disk('public')
                                        ->directory('catalog/products/manual')
                                        ->visibility('public')
                                        ->image()
                                        ->required(),
                                    TextInput::make('alt')->label('Alt текст'),
                                    TextInput::make('sort')->label('Сортування')->numeric()->default(10)->required(),
                                    Checkbox::make('is_primary')->label('Головне зображення'),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                    Tabs\Tab::make(__('admin.product.tabs.characteristics'))
                        ->icon('heroicon-o-list-bullet')
                        ->schema([
                            Section::make(__('admin.product.characteristics.section_title'))
                                ->schema([
                                    Repeater::make('attribute_values')
                                        ->label(__('admin.product.characteristics.label'))
                                        ->schema([
                                            Select::make('attribute_id')
                                                ->label(__('admin.product.characteristics.attribute'))
                                                ->options(fn (): array => static::getAttributeSelectOptions())
                                                ->searchable()
                                                ->preload()
                                                ->live()
                                                ->required()
                                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                ->columnSpan(1),
                                            Checkbox::make('is_priority')
                                                ->label(__('admin.product.characteristics.is_priority'))
                                                ->columnSpan(1),
                                            Select::make('value_option_id')
                                                ->label(__('admin.common.value'))
                                                ->options(fn (callable $get): array => static::getAttributeOptionSelectOptions($get('attribute_id')))
                                                ->searchable()
                                                ->preload()
                                                ->required(fn (callable $get): bool => in_array(static::getAttributeValueType($get('attribute_id')), ['select', 'option'], true))
                                                ->visible(fn (callable $get): bool => in_array(static::getAttributeValueType($get('attribute_id')), ['select', 'option'], true))
                                                ->columnSpan(1),
                                            Textarea::make('value_string')
                                                ->label(__('admin.common.value'))
                                                ->rows(3)
                                                ->required(fn (callable $get): bool => in_array(static::getAttributeValueType($get('attribute_id')), ['string', 'text'], true))
                                                ->visible(fn (callable $get): bool => in_array(static::getAttributeValueType($get('attribute_id')), ['string', 'text'], true))
                                                ->columnSpan(1),
                                            TextInput::make('value_integer')
                                                ->label(__('admin.common.value'))
                                                ->numeric()
                                                ->required(fn (callable $get): bool => static::getAttributeValueType($get('attribute_id')) === 'integer')
                                                ->visible(fn (callable $get): bool => static::getAttributeValueType($get('attribute_id')) === 'integer')
                                                ->columnSpan(1),
                                            TextInput::make('value_numeric')
                                                ->label(__('admin.common.value'))
                                                ->numeric()
                                                ->inputMode('decimal')
                                                ->required(fn (callable $get): bool => static::getAttributeValueType($get('attribute_id')) === 'numeric')
                                                ->visible(fn (callable $get): bool => static::getAttributeValueType($get('attribute_id')) === 'numeric')
                                                ->columnSpan(1),
                                            Toggle::make('value_boolean')
                                                ->label(__('admin.common.value'))
                                                ->visible(fn (callable $get): bool => static::getAttributeValueType($get('attribute_id')) === 'boolean')
                                                ->columnSpan(1),
                                            Hidden::make('sort'),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->reorderable()
                                        ->itemLabel(fn (array $state): ?string => static::getAttributeItemLabel($state)),
                                ])
                                ->columnSpanFull(),
                        ]),
                    Tabs\Tab::make(__('admin.product.tabs.variants'))
                        ->icon('heroicon-o-queue-list')
                        ->visible(fn (callable $get): bool => $get('type') === 'variant')
                        ->schema([
                            Section::make(__('admin.product.variants.section_title'))
                                ->schema([
                                    Repeater::make('variants')
                                        ->label(__('admin.product.variants.label'))
                                        ->schema([
                                            TextInput::make('name')
                                                ->label(__('admin.common.name'))
                                                ->required(),
                                            TextInput::make('sku')
                                                ->label(__('admin.common.sku'))
                                                ->required(),
                                            TextInput::make('price')
                                                ->label(__('admin.common.price'))
                                                ->numeric()
                                                ->inputMode('decimal')
                                                ->minValue(0)
                                                ->required(),
                                            TextInput::make('old_price')
                                                ->label(__('admin.product.variants.old_price'))
                                                ->numeric()
                                                ->inputMode('decimal')
                                                ->minValue(0),
                                            Select::make('status')
                                                ->label(__('admin.common.availability'))
                                                ->options([
                                                    'in_stock' => __('admin.product.stock_status.in_stock'),
                                                    'out_of_stock' => __('admin.product.stock_status.out_of_stock'),
                                                    'preorder' => __('admin.product.stock_status.preorder'),
                                                ])
                                                ->default('in_stock')
                                                ->required(),
                                            Select::make('attribute_option_ids')
                                                ->label(__('admin.product.variants.attributes'))
                                                ->multiple()
                                                ->searchable()
                                                ->preload()
                                                ->options(function (): array {
                                                    $locale = app()->getLocale();

                                                    return ProductAttribute::where('is_variant_axis', true)
                                                        ->with(['options.translations'])
                                                        ->get()
                                                        ->mapWithKeys(function (ProductAttribute $attribute) use ($locale): array {
                                                            $attrName = $attribute->translation($locale)?->name ?? $attribute->code;
                                                            $options = $attribute->options->mapWithKeys(function (AttributeOption $option) use ($locale, $attrName): array {
                                                                $label = $option->translation($locale)?->label ?? $option->value;

                                                                return [$option->id => "{$attrName}: {$label}"];
                                                            });

                                                            return $options->all();
                                                        })
                                                        ->all();
                                                }),
                                            Hidden::make('sort')
                                                ->default(0),
                                            Hidden::make('id'),
                                        ])
                                        ->columns(3)
                                        ->collapsible()
                                        ->reorderable()
                                        ->orderColumn('sort')
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
                                ])
                                ->visible(fn (callable $get): bool => $get('type') === 'variant')
                                ->columnSpanFull(),
                        ]),
                    Tabs\Tab::make(__('admin.product.tabs.faq'))
                        ->icon('heroicon-o-question-mark-circle')
                        ->schema([
                            Section::make(__('admin.product.faq.section_title'))
                                ->schema([
                                    Repeater::make('faqs')
                                        ->label(__('admin.product.faq.label'))
                                        ->schema([
                                            Select::make('locale')
                                                ->label(__('admin.common.locale'))
                                                ->options(collect(Locales::supported())->mapWithKeys(fn (string $locale): array => [$locale => __('admin.locale_names.'.$locale)])->all())
                                                ->required(),
                                            TextInput::make('question')
                                                ->label(__('admin.product.faq.question'))
                                                ->required()
                                                ->columnSpanFull(),
                                            Textarea::make('answer')
                                                ->label(__('admin.product.faq.answer'))
                                                ->rows(4)
                                                ->required()
                                                ->columnSpanFull(),
                                            TextInput::make('sort')
                                                ->label(__('admin.common.sort'))
                                                ->numeric()
                                                ->default(0)
                                                ->required(),
                                        ])
                                        ->columns(2)
                                        ->collapsible()
                                        ->reorderable(false)
                                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null),
                                ])
                                ->columnSpanFull(),
                        ]),
                    Tabs\Tab::make(__('admin.product.tabs.relations'))
                        ->icon('heroicon-o-link')
                        ->schema([
                            Section::make(__('admin.product.relations.section_title'))
                                ->schema([
                                    Select::make('color_related_product_ids')
                                        ->label(__('admin.product.relations.color_related_product_ids'))
                                        ->helperText(__('admin.product.relations.color_related_product_ids_hint'))
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->options(fn (?Product $record = null): array => static::getProductRelationOptions($record))
                                        ->columnSpanFull(),
                                    Select::make('bought_together_product_ids')
                                        ->label(__('admin.product.relations.bought_together_product_ids'))
                                        ->helperText(__('admin.product.relations.bought_together_product_ids_hint'))
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->options(fn (?Product $record = null): array => static::getProductRelationOptions($record))
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ]),
                    Tabs\Tab::make(__('admin.product.tabs.seo'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            ...static::seoTranslationSections(),
                        ]),
                    Tabs\Tab::make(__('admin.product.tabs.additional'))
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Toggle::make('is_hit_sales')
                                ->label(__('admin.product.badges.is_hit_sales'))
                                ->default(false)
                                ->inline(false),
                            Toggle::make('is_on_sale')
                                ->label(__('admin.product.badges.is_on_sale'))
                                ->default(false)
                                ->inline(false),
                            Toggle::make('is_new')
                                ->label(__('admin.product.badges.is_new'))
                                ->default(false)
                                ->inline(false),
                        ])
                        ->columns(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.common.id'))->searchable()->sortable(),
                ImageColumn::make('primaryImage.path')
                    ->label('')
                    ->disk('public')
                    ->imageSize(56)
                    ->square()
                    ->defaultImageUrl(fn (): string => asset('images/shophats/placeholders/image-placeholder.svg')),
                TextColumn::make('translation_name')
                    ->label(__('admin.common.name'))
                    ->state(fn (Product $record): ?string => $record->translation(app()->getLocale())?->name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = trim($search);

                        return $query->where(function (Builder $builder) use ($search): void {
                            $builder
                                ->where('products.sku', 'like', "%{$search}%")
                                ->orWhere('products.search_text', 'like', "%{$search}%")
                                ->orWhereHas('translations', function (Builder $translations) use ($search): void {
                                    $translations
                                        ->where('name', 'like', "%{$search}%")
                                        ->orWhere('slug', 'like', "%{$search}%");
                                });
                        });
                    }),
                TextColumn::make('sku')->label(__('admin.common.sku'))->searchable(),
                TextColumn::make('min_price')->label(__('admin.common.price'))
                    ->state(fn (Product $record): ?string => $record->variants->min('price'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' '.Currency::getBaseSymbol())
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('variants_min_price', $direction)),
                TextColumn::make('stock_status')->label(__('admin.common.availability'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => __('admin.product.stock_status.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'out_of_stock' => 'danger',
                        'preorder' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'in_stock' => 'heroicon-o-check-circle',
                        'out_of_stock' => 'heroicon-o-x-circle',
                        'preorder' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    }),
                TextColumn::make('categories')
                    ->label(__('admin.common.categories'))
                    ->html()
                    ->state(fn (Product $record): string => $record->categories
                        ->map(fn ($category): ?string => $category->translation(app()->getLocale())?->name)
                        ->filter()
                        ->implode('<br>')),
            ])
            ->filters([
                SelectFilter::make('stock_status')
                    ->label(__('admin.common.availability'))
                    ->options([
                        'in_stock' => __('admin.product.stock_status.in_stock'),
                        'out_of_stock' => __('admin.product.stock_status.out_of_stock'),
                        'preorder' => __('admin.product.stock_status.preorder'),
                    ]),
                SelectFilter::make('categories')
                    ->label(__('admin.common.categories'))
                    ->relationship(
                        'categories',
                        'id',
                        fn (Builder $query): Builder => $query->withTranslationsFor(app()->getLocale()),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->translation(app()->getLocale())?->name ?? (string) $record->id),
            ])
            ->recordActions([
                Action::make('reviews')
                    ->label(__('admin.product_review.actions.view_reviews'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn (Product $record): string => ProductReviewResource::getUrl('index', [
                        'filters' => [
                            'product_id' => ['value' => (string) $record->id],
                        ],
                    ]))
                    ->iconButton(),
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
            ->withTranslationsFor(app()->getLocale())
            ->with(['categories.translations', 'primaryImage', 'variants']);
    }

    private static function getProductRelationOptions(?Product $record = null): array
    {
        $locale = app()->getLocale();

        return Product::query()
            ->when($record?->exists, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
            ->withTranslationsFor($locale)
            ->orderByDesc('products.id')
            ->limit(250)
            ->get()
            ->mapWithKeys(function (Product $product) use ($locale): array {
                $name = $product->translation($locale)?->name ?? $product->sku ?? (string) $product->id;
                $sku = filled($product->sku) ? " ({$product->sku})" : '';

                return [$product->id => "#{$product->id} {$name}{$sku}"];
            })
            ->all();
    }

    public static function getAttributeSelectOptions(): array
    {
        $locale = app()->getLocale();

        return ProductAttribute::query()
            ->with(['group', 'translations'])
            ->orderBy('sort')
            ->orderBy('code')
            ->get()
            ->mapWithKeys(function (ProductAttribute $attribute) use ($locale): array {
                $name = $attribute->translation($locale)?->name ?? $attribute->code;
                $groupName = $attribute->group?->name;
                $group = filled($groupName) && $groupName !== $name ? "{$groupName}: " : '';

                return [$attribute->id => "{$group}{$name}"];
            })
            ->all();
    }

    public static function getAttributeOptionSelectOptions(mixed $attributeId): array
    {
        $locale = app()->getLocale();
        $attributeId = (int) $attributeId;

        if ($attributeId <= 0) {
            return [];
        }

        return AttributeOption::query()
            ->where('attribute_id', $attributeId)
            ->with('translations')
            ->orderBy('sort')
            ->orderBy('value')
            ->get()
            ->mapWithKeys(fn (AttributeOption $option): array => [
                $option->id => $option->translation($locale)?->label ?? $option->value,
            ])
            ->all();
    }

    public static function getAttributeValueType(mixed $attributeId): ?string
    {
        $attributeId = (int) $attributeId;

        if ($attributeId <= 0) {
            return null;
        }

        return ProductAttribute::query()->whereKey($attributeId)->value('value_type');
    }

    public static function getAttributeItemLabel(array $state): ?string
    {
        $attributeId = (int) ($state['attribute_id'] ?? 0);

        if ($attributeId <= 0) {
            return null;
        }

        $locale = app()->getLocale();
        $attribute = ProductAttribute::query()->with('translations')->find($attributeId);

        return $attribute?->translation($locale)?->name ?? $attribute?->code;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
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
                    ->label(__('admin.common.name'))
                    ->required($locale === Locales::default())
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $old, $get, $set, ?Product $record) use ($locale): void {
                        $slugPath = "translations.{$locale}.slug";
                        $currentSlug = $get($slugPath);
                        $previousGeneratedSlug = static::generateUniqueSiteSlug((string) $old, $record);

                        if (filled($currentSlug) && $currentSlug !== $previousGeneratedSlug) {
                            return;
                        }

                        $set($slugPath, static::generateUniqueSiteSlug((string) $state, $record));
                    })
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.description")
                    ->label(__('admin.common.description'))
                    ->rows(4)
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
                RichEditor::make("translations.{$locale}.full_description")
                    ->label(__('admin.common.full_description'))
                    ->json()
                    ->registerActions([
                        RichEditorCustomBlockAction::make(),
                    ])
                    ->afterStateHydrated(function (RichEditor $component, $state): void {
                        $component->state(RichEditorDocumentNormalizer::ensureTrailingParagraph($state));
                    })
                    ->customBlocks([
                        VideoEmbedBlock::class,
                    ])
                    ->tools([
                        RichEditorTool::make('videoEmbed')
                            ->label(__('admin.resources.post.editor.video_embed.label'))
                            ->action(
                                action: 'customBlock',
                                arguments: "{ id: 'video-embed', mode: 'insert' }",
                            )
                            ->icon(Heroicon::OutlinedPlayCircle),
                    ])
                    ->enableToolbarButtons([
                        'videoEmbed',
                    ])
                    ->disableToolbarButtons([
                        'customBlocks',
                    ])
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'hat-shop-rich-editor',
                        'style' => '--hat-shop-rich-editor-min-height: 24rem; min-height: var(--hat-shop-rich-editor-min-height);',
                    ])
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
                    ->label(__('admin.common.slug'))
                    ->live(onBlur: true)
                    ->afterStateHydrated(function ($state, $get, $set, ?Product $record) use ($locale): void {
                        if (filled($state)) {
                            return;
                        }

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSiteSlug((string) $get("translations.{$locale}.name"), $record),
                        );
                    })
                    ->afterStateUpdated(function ($state, $get, $set, ?Product $record) use ($locale): void {
                        $source = filled($state)
                            ? (string) $state
                            : (string) $get("translations.{$locale}.name");

                        $set(
                            "translations.{$locale}.slug",
                            static::generateUniqueSiteSlug($source, $record),
                        );
                    })
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
                Select::make("translations.{$locale}.robots")
                    ->label(__('admin.product.seo.robots'))
                    ->options([
                        'index, follow' => __('admin.product.seo.robots_options.index_follow'),
                        'noindex, follow' => __('admin.product.seo.robots_options.noindex_follow'),
                        'index, nofollow' => __('admin.product.seo.robots_options.index_nofollow'),
                        'noindex, nofollow' => __('admin.product.seo.robots_options.noindex_nofollow'),
                    ])
                    ->default('index, follow')
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_title")
                    ->label(__('admin.product.seo.meta_title'))
                    ->rows(2)
                    ->maxLength(255)
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_description")
                    ->label(__('admin.product.seo.meta_description'))
                    ->rows(2)
                    ->maxLength(500)
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

    /**
     * @param  array<int, string>  $reservedSlugs
     */
    public static function generateUniqueSiteSlug(?string $value, ?Product $record = null, array $reservedSlugs = []): ?string
    {
        $baseSlug = static::normalizeSlug($value);

        if ($baseSlug === '') {
            return null;
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (in_array($slug, $reservedSlugs, true) || static::siteSlugExists($slug, $record)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected static function siteSlugExists(string $slug, ?Product $record = null): bool
    {
        $productId = $record?->getKey();

        return ProductTranslation::query()
            ->where('slug', $slug)
            ->when($productId, fn (Builder $query): Builder => $query->where('product_id', '!=', $productId))
            ->exists()
            || CategoryTranslation::query()->where('slug', $slug)->exists()
            || PostCategoryTranslation::query()->where('slug', $slug)->exists()
            || PostTranslation::query()->where('slug', $slug)->exists()
            || PageTranslation::query()->where('slug', $slug)->exists();
    }
}
