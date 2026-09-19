<?php

namespace Commero\Support\Seo;

use Commero\Models\Category;
use Commero\Models\CityCategory;
use Commero\Models\Page;
use Commero\Models\Post;
use Commero\Models\PostCategory;
use Commero\Models\Product;
use Commero\Support\EntityLinkService;
use Commero\Support\Locales;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class SitemapBuilder
{
    private ?array $maps = null;

    public function __construct(
        private readonly EntityLinkService $entityLinkService,
    ) {}

    /**
     * @return array<int, array{loc: string, lastmod: ?string}>
     */
    public function index(): array
    {
        return collect($this->mapPaths())
            ->map(function (string $path, string $map): array {
                $entries = $this->entries($map);

                return [
                    'loc' => $this->absoluteUrl($path),
                    'lastmod' => collect($entries)
                        ->pluck('lastmod')
                        ->filter()
                        ->sortDesc()
                        ->first(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, alternates: array<int, array{locale: string, href: string}>}>
     */
    public function entries(string $map): array
    {
        return $this->allMaps()[$map] ?? [];
    }

    /**
     * @return array<string, string>
     */
    private function mapPaths(): array
    {
        return array_map(
            static fn (mixed $path): string => ltrim((string) $path, '/'),
            (array) config('commero.sitemap.maps', []),
        );
    }

    /**
     * @return array<string, array<int, array{loc: string, lastmod: ?string, alternates: array<int, array{locale: string, href: string}>}>>
     */
    private function allMaps(): array
    {
        if ($this->maps !== null) {
            return $this->maps;
        }

        $maps = [];
        $seen = [];

        foreach (array_keys($this->mapPaths()) as $map) {
            $maps[$map] = [];

            foreach ($this->buildMap($map) as $entry) {
                $dedupeKey = rtrim($entry['loc'], '/');

                if (isset($seen[$dedupeKey])) {
                    continue;
                }

                $seen[$dedupeKey] = true;
                $maps[$map][] = $entry;
            }
        }

        return $this->maps = $maps;
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, alternates: array<int, array{locale: string, href: string}>}>
     */
    private function buildMap(string $map): array
    {
        return match ($map) {
            'static' => $this->buildStaticMap(),
            'categories' => $this->buildCategoriesMap(),
            'products' => $this->buildProductsMap(),
            'blog' => $this->buildBlogMap(),
            'pages' => $this->buildPagesMap(),
            default => [],
        };
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, alternates: array<int, array{locale: string, href: string}>}>
     */
    private function buildStaticMap(): array
    {
        $entries = [];

        foreach ((array) config('commero.sitemap.static_routes', []) as $routeName) {
            $routeName = (string) $routeName;
            $pageSlug = [
                'home' => 'home',
                'contacts.index' => 'contacts',
                'privacy.policy' => 'politika-konfidencijnosti',
            ][$routeName] ?? null;
            $page = $pageSlug === null
                ? null
                : Page::query()
                    ->published()
                    ->with('translations')
                    ->whereHas('translations', fn ($query) => $query->where('slug', $pageSlug))
                    ->first();
            $urls = [];

            foreach (Locales::supported() as $locale) {
                if ($pageSlug !== null && ($page === null || ! $this->translationIsIndexable($page, $locale))) {
                    continue;
                }

                $url = $this->routeUrl($routeName, $locale);

                if (filled($url)) {
                    $urls[$locale] = $url;
                }
            }

            $entries = [...$entries, ...$this->entriesForUrls($urls)];
        }

        return $entries;
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, alternates: array<int, array{locale: string, href: string}>}>
     */
    private function buildCategoriesMap(): array
    {
        $entries = [];

        foreach (Category::query()->with('translations')->get() as $category) {
            $urls = [];

            foreach (Locales::supported() as $locale) {
                if (! $this->translationIsIndexable($category, $locale)) {
                    continue;
                }

                $url = $this->entityLinkService->categoryUrl($category, $locale);

                if (filled($url)) {
                    $urls[$locale] = $this->absoluteUrl($url, true);
                }
            }

            $entries = [...$entries, ...$this->entriesForUrls($urls, $category->updated_at)];
        }

        foreach (CityCategory::query()->with('translations')->get() as $cityCategory) {
            $urls = [];

            foreach (Locales::supported() as $locale) {
                if (! $this->translationIsIndexable($cityCategory, $locale)) {
                    continue;
                }

                $url = $this->entityLinkService->cityCategoryUrl($cityCategory, $locale);

                if (filled($url)) {
                    $urls[$locale] = $this->absoluteUrl($url, true);
                }
            }

            $entries = [...$entries, ...$this->entriesForUrls($urls, $cityCategory->updated_at)];
        }

        return $entries;
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, alternates: array<int, array{locale: string, href: string}>}>
     */
    private function buildProductsMap(): array
    {
        $entries = [];

        foreach (Product::query()->where('status', 'published')->with('translations')->get() as $product) {
            $urls = [];

            foreach (Locales::supported() as $locale) {
                if (! $this->translationIsIndexable($product, $locale)) {
                    continue;
                }

                $slug = $product->localizedSlug($locale);

                if (! filled($slug)) {
                    continue;
                }

                $url = $this->routeUrl('product.show', $locale, ['slug' => $slug]);

                if (filled($url)) {
                    $urls[$locale] = $url;
                }
            }

            $entries = [...$entries, ...$this->entriesForUrls($urls, $product->updated_at)];
        }

        return $entries;
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, alternates: array<int, array{locale: string, href: string}>}>
     */
    private function buildBlogMap(): array
    {
        $entries = [];

        foreach (Post::query()->published()->with('translations')->get() as $post) {
            $urls = [];

            foreach (Locales::supported() as $locale) {
                if (! $this->translationIsIndexable($post, $locale)) {
                    continue;
                }

                $slug = $post->localizedSlug($locale);

                if (! filled($slug)) {
                    continue;
                }

                $url = $this->routeUrl('post.show', $locale, ['slug' => $slug]);

                if (filled($url)) {
                    $urls[$locale] = $url;
                }
            }

            $entries = [...$entries, ...$this->entriesForUrls($urls, $post->updated_at)];
        }

        foreach (PostCategory::query()
            ->whereHas('posts', fn ($query) => $query->published())
            ->with('translations')
            ->get() as $category) {
            $urls = [];

            foreach (Locales::supported() as $locale) {
                if (! $this->translationIsIndexable($category, $locale)) {
                    continue;
                }

                $slug = $category->localizedSlug($locale, $category->path);

                if (! filled($slug)) {
                    continue;
                }

                $url = $this->routeUrl('blog.category', $locale, ['slug' => $slug]);

                if (filled($url)) {
                    $urls[$locale] = $url;
                }
            }

            $entries = [...$entries, ...$this->entriesForUrls($urls, $category->updated_at)];
        }

        return $entries;
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, alternates: array<int, array{locale: string, href: string}>}>
     */
    private function buildPagesMap(): array
    {
        $entries = [];
        $reservedSlugs = (array) config('commero.routing.reserved_root_slugs', []);

        foreach (Page::query()->published()->with('translations')->get() as $page) {
            $urls = [];

            foreach (Locales::supported() as $locale) {
                if (! $this->translationIsIndexable($page, $locale)) {
                    continue;
                }

                $link = $this->entityLinkService->pageUrl($page, $locale);

                if (! filled($link)) {
                    continue;
                }

                $pathSegments = array_values(array_filter(
                    explode('/', trim((string) parse_url($link, PHP_URL_PATH), '/')),
                    static fn (string $segment): bool => $segment !== '',
                ));

                if (isset($pathSegments[0]) && in_array($pathSegments[0], Locales::supported(), true)) {
                    array_shift($pathSegments);
                }

                $path = $pathSegments[0] ?? '';

                if ($path === '' || in_array($path, $reservedSlugs, true)) {
                    continue;
                }

                $urls[$locale] = $this->absoluteUrl($link, true);
            }

            $entries = [...$entries, ...$this->entriesForUrls($urls, $page->updated_at)];
        }

        return $entries;
    }

    /**
     * @param  array<string, string>  $urls
     * @return array<int, array{loc: string, lastmod: ?string, alternates: array<int, array{locale: string, href: string}>}>
     */
    private function entriesForUrls(array $urls, ?DateTimeInterface $lastmod = null): array
    {
        if ($urls === []) {
            return [];
        }

        $defaultUrl = $urls[Locales::default()] ?? reset($urls);
        $alternates = collect($urls)
            ->map(fn (string $href, string $locale): array => [
                'locale' => $locale,
                'href' => $href,
            ])
            ->values()
            ->all();

        $alternates[] = [
            'locale' => 'x-default',
            'href' => $defaultUrl,
        ];

        return collect($urls)
            ->map(fn (string $href): array => [
                'loc' => $href,
                'lastmod' => $this->formatLastmod($lastmod),
                'alternates' => $alternates,
            ])
            ->values()
            ->all();
    }

    private function routeUrl(string $baseRoute, string $locale, array $parameters = []): ?string
    {
        $routeName = Locales::isDefault($locale) ? $baseRoute : 'localized.'.$baseRoute;

        if (! Route::has($routeName)) {
            return null;
        }

        try {
            $routeParameters = Locales::isDefault($locale)
                ? $parameters
                : ['locale' => $locale, ...$parameters];

            return $this->absoluteUrl(route($routeName, $routeParameters), true);
        } catch (Throwable) {
            return null;
        }
    }

    private function translationIsIndexable(Model $model, string $locale): bool
    {
        $translation = method_exists($model, 'translation')
            ? $model->translation($locale)
            : null;
        $robots = strtolower((string) ($translation?->robots ?? 'index, follow'));

        return ! Str::contains($robots, 'noindex');
    }

    private function absoluteUrl(string $value, bool $trailingSlash = false): string
    {
        $url = preg_match('/^https?:\/\//i', $value)
            ? $value
            : url('/'.ltrim($value, '/'));

        if (! $trailingSlash || $url === '') {
            return $url;
        }

        return rtrim($url, '/').'/';
    }

    private function formatLastmod(?DateTimeInterface $lastmod): ?string
    {
        return $lastmod?->format(DATE_ATOM);
    }
}
