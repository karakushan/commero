<?php

namespace Commero\Livewire;

use Commero\Application\Catalog\Queries\CatalogProductListQuery;
use Commero\Models\Category;
use Commero\Support\Locales;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class HeaderSearchDropdown extends Component
{
    private const MIN_SEARCH_LENGTH = 4;

    private const PRODUCTS_LIMIT = 10;

    public string $locale = '';

    public string $query = '';

    public bool $isOpen = false;

    public string $variant = 'desktop';

    public ?int $selectedCategoryId = null;

    public function mount(string $variant = 'desktop'): void
    {
        $this->locale = app()->getLocale();
        $this->query = trim((string) request()->input('q', ''));
        $this->variant = $variant === 'mobile' ? 'mobile' : 'desktop';
    }

    public function openDropdown(): void
    {
        $this->isOpen = true;
    }

    public function closeDropdown(): void
    {
        $this->isOpen = false;
    }

    public function clear(): void
    {
        $this->query = '';
        $this->isOpen = true;
    }

    public function clearAndClose(): void
    {
        $this->query = '';
        $this->selectedCategoryId = null;
        $this->isOpen = false;
    }

    public function toggleCategory(int $categoryId): void
    {
        $this->selectedCategoryId = $this->selectedCategoryId === $categoryId ? null : $categoryId;
        $this->isOpen = true;
    }

    public function getSelectedCategoryFilterIdsProperty(): array
    {
        return $this->resolveSelectedCategoryFilterIds();
    }

    public function updatedQuery(): void
    {
        $this->query = trim($this->query);
        $this->isOpen = true;
    }

    public function render(CatalogProductListQuery $productsQuery): View
    {
        $products = collect();
        $categories = collect();
        $productsTitle = __('Popular products');
        $viewAllUrl = Locales::path('/catalog', $this->locale);
        $searchQuery = $this->resolvedSearchQuery();

        if ($this->isOpen || $this->query !== '' || $this->selectedCategoryId !== null) {
            $baseFilters = $searchQuery === '' ? [] : ['search' => $searchQuery];
            $productFilters = $baseFilters;

            if ($this->selectedCategoryId !== null) {
                $productFilters['categories'] = $this->selectedCategoryFilterIds;
            }

            $products = collect($productsQuery->handle(
                $this->locale,
                'date_desc',
                $productFilters,
                self::PRODUCTS_LIMIT,
            )->items());

            $categories = $searchQuery === ''
                ? $this->resolvePopularCategories()
                : $this->resolveMatchingCategories($searchQuery, collect($productsQuery->handle(
                    $this->locale,
                    'date_desc',
                    $baseFilters,
                    self::PRODUCTS_LIMIT,
                )->items()));

            if ($searchQuery !== '') {
                $productsTitle = __('Products found');
                $viewAllParams = ['q' => $searchQuery];

                if ($this->selectedCategoryId !== null) {
                    $viewAllParams['filters'] = ['categories' => $this->selectedCategoryFilterIds];
                }

                $viewAllUrl = Locales::path('/search', $this->locale).'?'.http_build_query($viewAllParams);
            } elseif ($this->selectedCategoryId !== null) {
                $viewAllUrl = Locales::path('/catalog', $this->locale).'?'.http_build_query([
                    'filters' => ['categories' => $this->selectedCategoryFilterIds],
                ]);
            }
        }

        return view('livewire.header-search-dropdown', [
            'products' => $products,
            'categories' => $categories,
            'productsTitle' => $productsTitle,
            'viewAllUrl' => $viewAllUrl,
            'variant' => $this->variant,
            'selectedCategoryId' => $this->selectedCategoryId,
        ]);
    }

    private function resolvePopularCategories(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->withTranslationsFor($this->locale)
            ->get()
            ->sortBy(fn (Category $category) => mb_strtolower($category->translation($this->locale)?->name ?? ''))
            ->take(4)
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->translation($this->locale)?->name ?? __('Catalog'),
                'url' => $category->frontendUrl($this->locale) ?? Locales::path('/'.($category->path ?? $category->id), $this->locale),
            ])
            ->values();
    }

    private function resolvedSearchQuery(): string
    {
        $query = trim($this->query);

        return mb_strlen($query) >= self::MIN_SEARCH_LENGTH ? $query : '';
    }

    private function resolveSelectedCategoryFilterIds(): array
    {
        if ($this->selectedCategoryId === null) {
            return [];
        }

        return Category::query()
            ->where('id', $this->selectedCategoryId)
            ->orWhere('parent_id', $this->selectedCategoryId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function resolveMatchingCategories(string $query, Collection $products): Collection
    {
        $categories = $products
            ->flatMap(fn ($product) => collect($product->categories ?? []))
            ->filter(fn (array $category) => filled($category['name'] ?? null))
            ->groupBy('id')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'id' => $first['id'],
                    'name' => $first['name'],
                    'url' => Locales::path('/'.($first['slug'] ?? $first['id']), $this->locale),
                    'matches' => $items->count(),
                ];
            })
            ->sortByDesc('matches')
            ->take(4)
            ->values();

        if ($categories->isNotEmpty()) {
            return $categories->map(fn (array $category) => [
                'id' => $category['id'],
                'name' => $category['name'],
                'url' => $category['url'],
            ]);
        }

        return Category::query()
            ->whereHas('translations', function ($translationQuery) use ($query) {
                $translationQuery
                    ->where('locale', $this->locale)
                    ->where('name', 'like', '%'.$query.'%');
            })
            ->withTranslationsFor($this->locale)
            ->limit(4)
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->translation($this->locale)?->name ?? __('Catalog'),
                'url' => $category->frontendUrl($this->locale) ?? Locales::path('/'.($category->path ?? $category->id), $this->locale),
            ])
            ->values();
    }
}
