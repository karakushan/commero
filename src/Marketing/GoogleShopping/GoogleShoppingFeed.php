<?php

namespace Commero\Marketing\GoogleShopping;

use Commero\Marketing\Tracking;
use Commero\Models\Category;
use Commero\Models\Currency;
use Commero\Models\Product;
use Commero\Models\ProductAttributeValue;
use Commero\Services\MediaUrlResolver;
use Commero\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use XMLWriter;

/**
 * Google Merchant Center product feed (RSS 2.0 with the g: namespace).
 *
 * Products are skipped when excluded (product or category, with sub-categories), not in stock or without an image.
 * Extend the class and bind it in the container to change the selection or the item data.
 */
class GoogleShoppingFeed
{
    public const NAMESPACE = 'http://base.google.com/ns/1.0';

    /** @var array<int, true>|null */
    private ?array $excludedCategoryIds = null;

    private ?string $currency = null;

    public static function enabled(): bool
    {
        if (! config('commero.google_shopping.enabled', true)) {
            return false;
        }

        $setting = Tracking::setting();

        return $setting === null || $setting->google_shopping_enabled !== false;
    }

    public static function url(?string $locale = null): string
    {
        $locale = Locales::resolve($locale ?? Locales::default());
        $route = Locales::isDefault($locale) ? 'google-shopping.feed' : 'localized.google-shopping.feed';

        if (! Route::has($route)) {
            return url(Locales::path('/'.trim((string) config('commero.google_shopping.path', 'feed/googleshopping'), '/'), $locale));
        }

        return route($route, Locales::isDefault($locale) ? [] : ['locale' => $locale]);
    }

    public function render(string $locale): string
    {
        $ttl = (int) config('commero.google_shopping.cache_ttl', 0);

        if ($ttl > 0) {
            return Cache::remember('commero.google_shopping.'.$locale, $ttl, fn (): string => $this->build($locale));
        }

        return $this->build($locale);
    }

    public function build(string $locale): string
    {
        $previousLocale = app()->getLocale();
        app()->setLocale($locale);

        try {
            $xml = new XMLWriter;
            $xml->openMemory();
            $xml->setIndent(true);
            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElement('rss');
            $xml->writeAttribute('version', '2.0');
            $xml->writeAttribute('xmlns:g', self::NAMESPACE);
            $xml->startElement('channel');
            $xml->writeElement('title', $this->channelTitle($locale));
            $xml->writeElement('description', $this->channelDescription($locale));
            $xml->writeElement('link', url(Locales::path('/', $locale)));

            $this->query()->chunkById(200, function (Collection $products) use ($xml, $locale): void {
                foreach ($products as $product) {
                    if (! $this->shouldInclude($product)) {
                        continue;
                    }

                    $item = $this->item($product, $locale);

                    if ($item !== null) {
                        $this->writeItem($xml, $item);
                    }
                }
            });

            $xml->endElement();
            $xml->endElement();
            $xml->endDocument();

            return $xml->outputMemory();
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    /**
     * @return Builder<Product>
     */
    protected function query(): Builder
    {
        return Product::query()
            ->where('status', 'published')
            ->where('google_shopping_exclude', false)
            ->with([
                'translations',
                'primaryImage.media',
                'images.media',
                'variants',
                'brand.translations',
                'categories.translations',
                'attributeValues.attribute.translations',
                'attributeValues.option.translations',
            ])
            ->orderByDesc('id');
    }

    protected function shouldInclude(Product $product): bool
    {
        if (! $this->isAvailable($product)) {
            return false;
        }

        $excluded = $this->excludedCategoryIds();

        return ! $product->categories->contains(fn (Category $category): bool => isset($excluded[$category->id]));
    }

    /**
     * Only products that can be ordered are exported (g:availability = in_stock).
     */
    protected function isAvailable(Product $product): bool
    {
        return $product->effectiveStockStatus() === 'in_stock';
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function item(Product $product, string $locale): ?array
    {
        $translation = $product->translation($locale);
        $slug = $product->localizedSlug($locale);
        $image = $this->imageUrl($product);

        if ($translation === null || ! filled($slug) || $image === null) {
            return null;
        }

        $variant = $product->variants->first();
        $categories = $product->categories;

        return [
            'g:id' => (string) $product->id,
            'title' => $this->text($translation->name),
            'description' => Str::limit($this->text($translation->description), 5000, ''),
            'link' => $this->productUrl($slug, $locale),
            'g:image_link' => $image,
            'g:additional_image_link' => $this->additionalImages($product, $image),
            'g:price' => $this->price((float) ($variant?->price ?? 0)),
            'g:mpn' => (string) ($product->sku ?? ''),
            'g:availability' => 'in_stock',
            'g:condition' => in_array($product->google_shopping_condition, ['new', 'used', 'refurbished'], true) ? $product->google_shopping_condition : 'new',
            'g:brand' => $this->brand($product, $locale),
            'g:google_product_category' => $categories->first(fn (Category $category): bool => (int) $category->google_product_category_id > 0)?->google_product_category_id
                ?? Tracking::setting()?->google_shopping_category_id,
            'g:product_type' => collect([
                __('commero::google_shopping.home'),
                __('commero::google_shopping.products'),
                ...$categories->map(fn (Category $category): string => $this->text($category->translation($locale)?->name))->filter(),
            ])->join(' > '),
            'g:product_detail' => $this->details($product, $locale),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function writeItem(XMLWriter $xml, array $item): void
    {
        $xml->startElement('item');

        foreach ($item as $name => $value) {
            if ($name === 'description') {
                $xml->startElement('description');
                $xml->writeCdata(str_replace(']]>', ']]]]><![CDATA[>', (string) $value));
                $xml->endElement();

                continue;
            }

            if ($name === 'g:product_detail') {
                foreach ((array) $value as $detail) {
                    $xml->startElement('g:product_detail');
                    $xml->writeElement('g:section_name', __('commero::google_shopping.general_information'));
                    $xml->writeElement('g:attribute_name', $detail['name']);
                    $xml->writeElement('g:attribute_value', $detail['value']);
                    $xml->endElement();
                }

                continue;
            }

            if (is_array($value)) {
                foreach ($value as $entry) {
                    $xml->writeElement($name, (string) $entry);
                }

                continue;
            }

            if ($value === null || ($value === '' && $name !== 'g:mpn')) {
                continue;
            }

            $xml->writeElement($name, (string) $value);
        }

        $xml->endElement();
    }

    protected function channelTitle(string $locale): string
    {
        return (string) (Tracking::setting()?->getSiteNameForLocale($locale) ?? config('app.name'));
    }

    protected function channelDescription(string $locale): string
    {
        return (string) config('commero.google_shopping.description', '');
    }

    protected function productUrl(string $slug, string $locale): string
    {
        $route = Locales::isDefault($locale) ? 'product.show' : 'localized.product.show';
        $parameters = Locales::isDefault($locale) ? ['slug' => $slug] : ['locale' => $locale, 'slug' => $slug];

        return Route::has($route) ? route($route, $parameters) : url(Locales::path('/product/'.$slug, $locale));
    }

    protected function imageUrl(Product $product): ?string
    {
        $image = $product->primaryImage ?? $product->images->first();

        return $image ? $this->absolute(MediaUrlResolver::url($image, 'product_gallery')) : null;
    }

    /**
     * @return array<int, string>
     */
    protected function additionalImages(Product $product, string $mainImage): array
    {
        return $product->images
            ->map(fn ($image): ?string => $this->absolute(MediaUrlResolver::url($image, 'product_gallery')))
            ->filter(fn (?string $url): bool => $url !== null && $url !== $mainImage)
            ->unique()
            ->take(10)
            ->values()
            ->all();
    }

    protected function price(float $amount): string
    {
        $this->currency ??= (string) (config('commero.google_shopping.currency') ?: Currency::getBaseCode());

        return number_format($amount, 2, '.', '').' '.$this->currency;
    }

    protected function brand(Product $product, string $locale): ?string
    {
        $brand = $product->brand;

        if ($brand === null) {
            return null;
        }

        $name = $brand->translation($locale)?->name ?? $brand->getAttribute('name');

        return filled($name) ? $this->text($name) : null;
    }

    /**
     * @return array<int, array{name: string, value: string}>
     */
    protected function details(Product $product, string $locale): array
    {
        return $product->attributeValues
            ->filter(fn (ProductAttributeValue $value): bool => $value->attribute !== null)
            ->groupBy('attribute_id')
            ->map(function (Collection $values) use ($locale): ?array {
                $name = $this->text($values->first()->attribute->translation($locale)?->name);
                $value = $values
                    ->map(fn (ProductAttributeValue $value): string => $this->text($value->option?->translation($locale)?->label
                        ?? $value->option?->value
                        ?? $value->value_string
                        ?? $value->value_numeric
                        ?? $value->value_integer))
                    ->filter()
                    ->unique()
                    ->join(', ');

                return $name !== '' && $value !== '' ? ['name' => $name, 'value' => $value] : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function text(mixed $value): string
    {
        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Ids of the excluded categories and all their sub-categories.
     *
     * @return array<int, true>
     */
    protected function excludedCategoryIds(): array
    {
        if ($this->excludedCategoryIds !== null) {
            return $this->excludedCategoryIds;
        }

        $categories = Category::query()->get(['id', 'parent_id', 'google_shopping_exclude']);
        $children = $categories->groupBy('parent_id');
        $excluded = [];
        $queue = $categories->where('google_shopping_exclude', true)->pluck('id')->all();

        while ($queue !== []) {
            $id = (int) array_shift($queue);

            if (isset($excluded[$id])) {
                continue;
            }

            $excluded[$id] = true;

            foreach ($children->get($id, collect()) as $child) {
                $queue[] = $child->id;
            }
        }

        return $this->excludedCategoryIds = $excluded;
    }

    private function absolute(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }
}
