<?php

namespace Commero\Http\Controllers;

use Commero\Models\Post;
use Commero\Models\PostCategory;
use Commero\Support\Locales;
use Commero\Support\Seo\LocalizedSeoResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    private const POST_PLACEHOLDER_IMAGE = 'images/shophats/placeholders/image-placeholder.svg';

    public function index(Request $request, LocalizedSeoResolver $seoResolver)
    {
        $locale = Locales::resolve($request->route('locale'));
        App::setLocale($locale);
        $slug = $request->route('slug');

        // Get category slug from route parameter or query string
        $currentCategory = $slug ?? (string) $request->query('category', '');
        $categories = $this->getCategories($locale);
        $posts = $this->getPosts($locale, $currentCategory);
        $currentCategoryModel = $currentCategory !== ''
            ? $this->resolveCurrentCategoryModel($locale, $currentCategory)
            : null;
        $title = $currentCategoryModel?->translation($locale)?->name ?? __('Blog');
        $seo = $currentCategoryModel
            ? $seoResolver->forTranslatedContent(
                locale: $locale,
                translations: $currentCategoryModel->translations,
                urlForLocale: fn (string $supportedLocale): string => $this->resolveBlogCategoryUrl($currentCategoryModel, $supportedLocale),
                fallback: [
                    'title' => $title,
                    'heading' => $title,
                    'description' => __('Blog about headwear, fashion tips and news from ShopHats'),
                ],
                availableLocales: Locales::supported(),
            )
            : $seoResolver->forCurrentRoute(
                request: $request,
                locale: $locale,
                title: __('Blog'),
                heading: __('Blog'),
                description: __('Blog about headwear, fashion tips and news from ShopHats'),
            );

        return view('shophats::pages.blog', [
            'locale' => $locale,
            'supportedLocales' => Locales::supported(),
            'title' => $title,
            'posts' => $posts,
            'categories' => $categories,
            'currentCategory' => $currentCategory,
            'currentCategoryModel' => $currentCategoryModel,
            'seo' => $seo,
        ]);
    }

    /**
     * Display a single blog post
     */
    public function show(Request $request, LocalizedSeoResolver $seoResolver)
    {
        $locale = Locales::resolve($request->route('locale'));
        App::setLocale($locale);
        $slug = (string) $request->route('slug');

        // Find the post by slug
        $post = Post::query()
            ->with([
                'translations',
                'category.translations',
            ])
            ->published()
            ->whereLocalizedSlug($slug, $locale)
            ->first();

        if (!$post) {
            abort(404);
        }

        // Get related posts (3 latest posts excluding current)
        $relatedPosts = Post::query()
            ->with([
                'translations',
                'category.translations',
            ])
            ->published()
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->orderBy('sort')
            ->orderByDesc('id')
            ->limit(3)
            ->get()
            ->map(function (Post $relatedPost) use ($locale): array {
                $translation = $relatedPost->translation($locale);
                $categoryTranslation = $relatedPost->category?->translation($locale);

                return [
                    'id' => $relatedPost->id,
                    'title' => $translation?->title ?? __('Blog post'),
                    'excerpt' => $translation?->excerpt,
                    'thumbnail_url' => $relatedPost->thumbnail_path
                        ? Storage::disk('public')->url($relatedPost->thumbnail_path)
                        : asset(self::POST_PLACEHOLDER_IMAGE),
                    'category' => $categoryTranslation?->name,
                    'category_slug' => $relatedPost->category?->localizedSlug($locale, $relatedPost->category?->path),
                    'date' => $relatedPost->published_at?->format('d.m.Y') ?? $relatedPost->created_at?->format('d.m.Y'),
                    'url' => $this->resolvePostUrl($relatedPost, $locale),
                ];
            });

        $translation = $post->translation($locale);
        $categoryTranslation = $post->category?->translation($locale);
        $seo = $seoResolver->forTranslatedContent(
            locale: $locale,
            translations: $post->translations,
            urlForLocale: fn (string $supportedLocale): ?string => $this->resolvePostUrl($post, $supportedLocale),
            fallback: [
                'title' => $translation?->title ?? __('Blog post'),
                'heading' => $translation?->title ?? __('Blog post'),
                'description' => $translation?->excerpt ?? __('Blog post'),
            ],
            availableLocales: $post->translations->pluck('locale')->all(),
        );

        return view('shophats::pages.post', [
            'locale' => $locale,
            'supportedLocales' => Locales::supported(),
            'seo' => $seo,
            'post' => [
                'id' => $post->id,
                'title' => $translation?->title ?? __('Blog post'),
                'slug' => $translation?->slug ?? $post->id,
                'content' => $translation?->content ?? '',
                'excerpt' => $translation?->excerpt,
                'thumbnail_url' => $post->thumbnail_path
                    ? Storage::disk('public')->url($post->thumbnail_path)
                    : asset(self::POST_PLACEHOLDER_IMAGE),
                'category' => $categoryTranslation?->name,
                'category_slug' => $post->category?->localizedSlug($locale, $post->category?->path),
                'date' => $post->published_at?->format('d.m.Y') ?? $post->created_at?->format('d.m.Y'),
                'published_at_iso' => $post->published_at?->format('Y-m-d') ?? $post->created_at?->format('Y-m-d'),
                'url' => $this->resolvePostUrl($post, $locale),
            ],
            'relatedPosts' => $relatedPosts,
        ]);
    }

    private function getCategories(string $locale)
    {
        return PostCategory::query()
            ->withTranslationsFor($locale)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(function (PostCategory $category) use ($locale): array {
                $translation = $category->translation($locale);

                return [
                    'slug' => $category->localizedSlug($locale, $category->path),
                    'name' => $translation?->name ?? $category->path,
                ];
            })
            ->filter(fn (array $category): bool => filled($category['name']))
            ->values();
    }

    private function getPosts(string $locale, string $currentCategory): LengthAwarePaginator
    {
        $query = Post::query()
            ->with([
                'translations',
                'category.translations',
            ])
            ->published()
            ->orderByDesc('published_at')
            ->orderBy('sort')
            ->orderByDesc('id');

        if ($currentCategory !== '') {
            $query->whereHas('category', function ($categoryQuery) use ($locale, $currentCategory): void {
                $categoryQuery
                    ->where('path', $currentCategory)
                    ->orWhere(fn ($builder) => $builder->whereLocalizedSlug($currentCategory, $locale));
            });
        }

        return $query
            ->paginate(9)
            ->withQueryString()
            ->through(function (Post $post) use ($locale): array {
                $translation = $post->translation($locale);
                $categoryTranslation = $post->category?->translation($locale);

                return [
                    'id' => $post->id,
                    'title' => $translation?->title ?? __('Blog post'),
                    'excerpt' => $translation?->excerpt,
                    'thumbnail_url' => $post->thumbnail_path
                        ? Storage::disk('public')->url($post->thumbnail_path)
                        : asset(self::POST_PLACEHOLDER_IMAGE),
                    'category' => $categoryTranslation?->name,
                    'category_slug' => $post->category?->localizedSlug($locale, $post->category?->path),
                    'date' => $post->published_at?->format('d.m.Y') ?? $post->created_at?->format('d.m.Y'),
                    'url' => $this->resolvePostUrl($post, $locale),
                ];
            });
    }

    private function resolveCurrentCategoryModel(string $locale, string $slug): ?PostCategory
    {
        return PostCategory::query()
            ->with('translations')
            ->where(function ($query) use ($locale, $slug): void {
                $query
                    ->where('path', $slug)
                    ->orWhere(fn ($builder) => $builder->whereLocalizedSlug($slug, $locale));
            })
            ->first();
    }

    private function resolvePostUrl(Post $post, string $locale): ?string
    {
        $slug = $post->localizedSlug($locale);

        if (! filled($slug)) {
            return null;
        }

        return Locales::isDefault($locale)
            ? route('post.show', ['slug' => $slug])
            : route('localized.post.show', ['locale' => $locale, 'slug' => $slug]);
    }

    private function resolveBlogCategoryUrl(PostCategory $category, string $locale): string
    {
        $slug = $category->localizedSlug($locale, $category->path);

        return Locales::isDefault($locale)
            ? route('blog.category', ['slug' => $slug])
            : route('localized.blog.category', ['locale' => $locale, 'slug' => $slug]);
    }
}
