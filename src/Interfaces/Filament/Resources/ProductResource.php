Warning: truncated output (original token count: 13685)
Total output lines: 962

<?php

namespace Commero\Interfaces\Filament\Resources;

use Commero\Interfaces\Filament\Resources\ProductResource\Pages;
use Commero\Models\AttributeOption;
use Commero\Models\Category;
use Commero\Models\CategoryTranslation;
use Commero\Models\Currency;
use Commero\Models\PageTranslation;
use Commero\Models\PostCategoryTranslation;
use Commero\Models\PostTranslation;
use Commero\Models\Product;
use Commero\Models\ProductAttribute;
use Commero\Models\ProductTranslation;
use Commero\Models\SiteSetting;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Filament\RichContentCustomBlocks\VideoEmbedBlock;
use Commero\Support\Filament\RichEditorCustomBlockAction;
use Commero\Support\Filament\RichEditorDocumentNormalizer;
use Commero\Support\Filament\RichEditorImageAction;
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

class ProductResource extends AdminResource
{
    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.product.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.catalog');
    }

    public static function getModelLabel(): string
    {
        return __('commero::admin.resources.product.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commero::admin.resources.product.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('active_locale_context')
                ->dehydrated(),
            Tabs::make('Product Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tabs\Tab::make(__('commero::admin.product.tabs.main'))
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            ...static::mainTranslationSections(),
                            Select::make('brand_id')
                                ->label(__('commero::admin.product.brand_id'))
                                ->relationship(
                                    'brand',
                                    'name',
                                    fn (Builder $query): Builder => $query->withTranslationsFor(app()->getLocale()),
                                )
                                ->searchable()
                                ->preload()
                                ->columnSpan(1),
                            Select::make('status')
                                ->options([
                                    'draft' => __('commero::admin.product.status.draft'),
                                    'published' => __('commero::admin.product.status.published'),
                                ])
                                ->label(__('commero::admin.common.status'))
                                ->required()
                                ->default('draft')
                                ->columnSpan(1),
                            Select::make('type')
                                ->options([
                                    'simple' => __('commero::admin.product.type.simple'),
                                    'variant' => __('commero::admin.product.type.variant'),
                                ])
                                ->label(__('commero::admin.common.type'))
                                ->required()
                                ->default('simple')
                                ->live()
                                ->columnSpan(1),
                            TextInput::make('sku')
                                ->label(__('commero::admin.common.sku'))
                                ->nullable()
                                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null)
                                ->required(fn ($livewire): bool => $livewire instanceof Pages\EditProduct)
                                ->unique(ignoreRecord: true)
                                ->visible(fn (callable $get): bool => $get('type') === 'simple')
                                ->columnSpan(1),
                            TextInput::make('price')
                                ->label(__('commero::admin.common.price'))
                                ->numeric()
                                ->inputMode('decimal')
                                ->minValue(0)
                                ->required()
                                ->visible(fn (callable $get): bool => $get('type') === 'simple')
                                ->hidden(fn ($livewire): bool => (bool) data_get($livewire, 'data.multi_currency_code') && data_get($livewire, 'data.multi_currency_code') !== Currency::getBaseCode())
                                ->columnSpan(1),
                            TextInput::make('old_price')
                                ->label(__('commero::admin.product.variants.old_price'))
                                ->numeric()
                                ->inputMode('decimal')
                                ->minValue(0)
                                ->visible(fn (callable $get): bool => $get('type') === 'simple')
                                ->hidden(fn ($livewire): bool => (bool) data_get($livewire, 'data.multi_currency_code') && data_get($livewire, 'data.multi_currency_code') !== Currency::getBaseCode())
                                ->columnSpan(1),
                            Select::make('stock_status')
                                ->label(__('commero::admin.common.availability'))
                                ->options([
                                    'in_stock' => __('commero::admin.product.stock_status.in_stock'),
                                    'out_of_stock' => __('commero::admin.product.stock_status.out_of_stock'),
                                    'preorder' => __('commero::admin.product.stock_status.preorder'),
                                    'always_in_stock' => __('commero::admin.product.stock_status.always_in_stock'),
                                ])
                                ->default('always_in_stock')
                                ->afterStateHydrated(function (Select $component, ?string $state): void {
                                    $component->state($state ?? 'always_in_stock');
                                })
                                ->dehydrateStateUsing(fn (?string $state): ?string => $state === 'always_in_stock' ? null : $state)
                                ->required()
                                ->live()
                                ->columnSpan(1),
                            TextInput::make('stock_qty')
                                ->label(__('commero::admin.common.stock_quantity'))
                                ->numeric()
                                ->integer()
                                ->minValue(1)
                                ->default(1)
                                ->required(fn (callable $get): bool => $get('stock_status') === 'in_stock')
                                ->visible(fn (callable $get): bool => $get('stock_status') === 'in_stock')
                                ->dehydrated(fn (callable $get): bool => $get('stock_status') === 'in_stock')
                                ->columnSpan(1),
                            Select::make('category_ids')
                                ->label(__('commero::admin.product.category_ids'))
                                ->multiple()
                                ->options(fn (): array => static::getLocalizedHierarchySelectOptions(Category::class))
                                ->getOptionLabelFromRecordUsing(fn (Category $record): string => static::formatLocalizedHierarchySelectLabel($record))
                                ->preload()
                                ->optionsLimit(500)
                                ->searchable()
                                ->columnSpanFull(),
                            Select::make('multi_currency_code')
                                ->label(__('commero::admin.product.multi_currency.select_currency'))
                                ->options(function (): array {
                                    return Currency::orderBy('sort')
                                        ->get()
                                        ->mapWithKeys(fn (Currency $currency): array => [$currency->code => "{$currency->code} ({$currency->symbol})"])
                                        ->all();
                                })
                                ->live()
                                ->visible(function (): bool {
                                    return SiteSetting::query()->first()?->isMultiCurrencyEnabled() ?? false;
                                })
                                ->columnSpan(1),
                            TextInput::make('multi_currency_price')
                                ->label(fn ($livewire): string => __('commero::admin.product.multi_currency.price_in', [
                                    'currency' => data_get($livewire, 'data.multi_currency_code', ''),
                                ]))
                                ->numeric()
                                ->inputMode('decimal')
                                ->minValue(0)
                                ->visible(fn (callable $get): bool => filled($get('multi_currency_code')) && $get('multi_currency_code') !== Currency::getBaseCode())
                                ->columnSpan(1),
                            TextInput::make('multi_currency_old_price')
                                ->label(fn ($livewire): string => __('commero::admin.product.multi_currency.old_price_in', [
                                    'currency' => data_get($livewire, 'data.multi_currency_code', ''),
                                ]))
                                ->numeric()
                                ->inputMode('decimal')
                                ->minValue(0)
                                ->visible(fn (callable $get): bool => filled($get('multi_currency_code')) && $get('multi_currency_code') !== Currency::getBaseCode())
                                ->columnSpan(1),
                        ])->columns(3),
                    Tabs\Tab::make(__('commero::admin.product.tabs.gallery'))
                        ->icon('heroicon-o-photo')
                        ->schema([
                            FileUpload::make('gallery_uploads')
                                ->label(__('commero::admin.product.gallery.bulk_upload'))
                                ->helperText(__('commero::admin.product.gallery.bulk_upload_hint'))
                                ->disk('public')
   …7685 tokens truncated…            ? route('product.show', ['slug' => $slug])
            : route('localized.product.show', ['locale' => $locale, 'slug' => $slug]);
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
                    ->label(__('commero::admin.common.name'))
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
                    ->label(__('commero::admin.common.description'))
                    ->rows(4)
                    ->columnSpanFull()
                    ->dehydratedWhenHidden(),
                RichEditor::make("translations.{$locale}.full_description")
                    ->label(__('commero::admin.common.full_description'))
                    ->json()
                    ->registerActions([
                        RichEditorCustomBlockAction::make(),
                    ])
                    ->afterStateHydrated(function (RichEditor $component, $state): void {
                        $component->state(RichEditorDocumentNormalizer::ensureTrailingParagraph($state));
                    })
                    ->toolbarButtons([
                        ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link'],
                        ['h2', 'h3'],
                        ['alignStart', 'alignCenter', 'alignEnd'],
                        ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                        ['table', 'attachFiles', RichEditorImageAction::NAME],
                        ['undo', 'redo'],
                    ])
                    ->customBlocks([
                        VideoEmbedBlock::class,
                    ])
                    ->tools([
                        RichEditorTool::make('videoEmbed')
                            ->label(__('commero::admin.resources.post.editor.video_embed.label'))
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
                    ->label(__('commero::admin.common.slug'))
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
                    ->label(__('commero::admin.product.seo.robots'))
                    ->options(function (callable $get) use ($locale): array {
                        $options = [
                            'index, follow' => __('commero::admin.product.seo.robots_options.index_follow'),
                            'noindex, follow' => __('commero::admin.product.seo.robots_options.noindex_follow'),
                            'index, nofollow' => __('commero::admin.product.seo.robots_options.index_nofollow'),
                            'noindex, nofollow' => __('commero::admin.product.seo.robots_options.noindex_nofollow'),
                        ];
                        $currentValue = $get("translations.{$locale}.robots");

                        if (filled($currentValue) && ! array_key_exists($currentValue, $options)) {
                            $options[$currentValue] = $currentValue;
                        }

                        return $options;
                    })
                    ->nullable()
                    ->default('index, follow')
                    ->afterStateHydrated(function (Select $component, ?string $state): void {
                        if (blank($state)) {
                            $component->state('index, follow');
                        }
                    })
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_title")
                    ->label(__('commero::admin.product.seo.meta_title'))
                    ->rows(2)
                    ->maxLength(255)
                    ->columnSpan(1)
                    ->dehydratedWhenHidden(),
                Textarea::make("translations.{$locale}.meta_description")
                    ->label(__('commero::admin.product.seo.meta_description'))
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
