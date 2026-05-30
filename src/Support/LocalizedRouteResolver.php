<?php

namespace Commero\Support;

use Commero\Models\Post;
use Commero\Models\PostCategory;
use Commero\Models\Product;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;

class LocalizedRouteResolver
{
    public function __construct(
        private readonly EntityLinkService $entityLinkService,
    ) {}

    public function languageOptions(): array
    {
        $currentLocale = Locales::resolve(request()->route('locale') ?? app()->getLocale());

        return collect(Locales::supported())
            ->map(function (string $locale) use ($currentLocale): array {
                $url = $this->resolveUrl($locale, $currentLocale);

                return [
                    'code' => $locale,
                    'label' => strtoupper($locale),
                    'is_current' => $locale === $currentLocale,
                    'is_available' => filled($url),
                    'url' => $url,
                ];
            })
            ->all();
    }

    private function resolveUrl(string $targetLocale, string $currentLocale): ?string
    {
        $route = request()->route();

        if (! $route instanceof Route) {
            return $this->appendQueryString(Locales::path('/', $targetLocale));
        }

        $routeName = $route->getName();

        if (! is_string($routeName) || $routeName === '') {
            return $this->appendQueryString($this->fallbackPathUrl($targetLocale));
        }

        $routeName = $this->normalizeRouteName($routeName);
        $parameters = Arr::except($route->parameters(), ['locale']);

        $url = match ($routeName) {
            'home',
            'catalog.index',
            'sale.index',
            'special-offers.index',
            'blog.index',
            'contacts.index',
            'errors.404',
            'search',
            'cart.index',
            'wishlist.index',
            'checkout.index',
            'account.index',
            'login',
            'register' => $this->routeUrl($routeName, $targetLocale, $parameters),
            'privacy.policy' => $this->routeUrl($routeName, $targetLocale, Arr::except($parameters, ['slug'])),
            'thank-you.show' => $this->routeUrl($routeName, $targetLocale, Arr::only($parameters, ['orderNumber'])),
            'password.reset' => $this->routeUrl($routeName, $targetLocale, Arr::only($parameters, ['token'])),
            'entity-links.show' => $this->resolveEntityLinkUrl((string) ($parameters['slug'] ?? ''), $currentLocale, $targetLocale),
            'product.show' => $this->resolveProductUrl((string) ($parameters['slug'] ?? ''), $currentLocale, $targetLocale),
            'post.show' => $this->resolvePostUrl((string) ($parameters['slug'] ?? ''), $currentLocale, $targetLocale),
            'blog.category' => $this->resolveBlogCategoryUrl((string) ($parameters['slug'] ?? ''), $currentLocale, $targetLocale),
            default => $this->fallbackPathUrl($targetLocale),
        };

        return filled($url)
            ? $this->appendQueryString($url)
            : null;
    }

    private function normalizeRouteName(string $routeName): string
    {
        return str_starts_with($routeName, 'localized.')
            ? substr($routeName, strlen('localized.'))
            : $routeName;
    }

    private function routeUrl(string $routeName, string $locale, array $parameters = []): string
    {
        return Locales::isDefault($locale)
            ? route($routeName, $parameters)
            : route('localized.'.$routeName, ['locale' => $locale, ...$parameters]);
    }

    private function resolveEntityLinkUrl(string $slug, string $currentLocale, string $targetLocale): ?string
    {
        $link = $this->entityLinkService->resolve($slug, $currentLocale);

        if (! $link) {
            return null;
        }

        return $this->entityLinkService->urlFor($link->entity_type, $link->entity_id, $targetLocale);
    }

    private function resolveBlogCategoryUrl(string $slug, string $currentLocale, string $targetLocale): ?string
    {
        if ($slug === '') {
            return $this->routeUrl('blog.index', $targetLocale);
        }

        $category = PostCategory::query()
            ->with('translations')
            ->where(function ($query) use ($slug, $currentLocale): void {
                $query
                    ->where('path', $slug)
                    ->orWhere(fn ($builder) => $builder->whereLocalizedSlug($slug, $currentLocale));
            })
            ->first();

        if (! $category) {
            return null;
        }

        $targetSlug = $category->localizedSlug($targetLocale, $category->path);

        return $this->routeUrl('blog.category', $targetLocale, ['slug' => $targetSlug]);
    }

    private function resolveProductUrl(string $slug, string $currentLocale, string $targetLocale): ?string
    {
        if ($slug === '') {
            return null;
        }

        $product = Product::query()
            ->with('translations')
            ->whereLocalizedSlug($slug, $currentLocale)
            ->first();

        if (! $product) {
            return null;
        }

        $targetSlug = $product->localizedSlug($targetLocale);

        return filled($targetSlug)
            ? $this->routeUrl('product.show', $targetLocale, ['slug' => $targetSlug])
            : null;
    }

    private function resolvePostUrl(string $slug, string $currentLocale, string $targetLocale): ?string
    {
        if ($slug === '') {
            return null;
        }

        $post = Post::query()
            ->with('translations')
            ->whereLocalizedSlug($slug, $currentLocale)
            ->first();

        if (! $post) {
            return null;
        }

        $targetSlug = $post->localizedSlug($targetLocale);

        return filled($targetSlug)
            ? $this->routeUrl('post.show', $targetLocale, ['slug' => $targetSlug])
            : null;
    }

    private function fallbackPathUrl(string $targetLocale): string
    {
        $segments = request()->segments();

        if ($segments !== [] && in_array($segments[0], Locales::supported(), true)) {
            array_shift($segments);
        }

        $path = $segments === [] ? '/' : '/'.implode('/', $segments);

        return Locales::path($path, $targetLocale);
    }

    private function appendQueryString(string $url): string
    {
        $query = request()->query();

        if ($query === []) {
            return $url;
        }

        return $url.'?'.Arr::query($query);
    }
}
