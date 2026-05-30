<?php

namespace Commero\Interfaces\Http\Livewire;

use Commero\Application\Catalog\Queries\CatalogFiltersQuery;
use Commero\Application\Catalog\Queries\CatalogProductListQuery;
use Commero\Models\CityCategory;
use Commero\Support\Locales;
use Commero\Support\Seo\LocalizedSeoResolver;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CityCategoryPage extends Component
{
    use WithPagination;

    private const DEFAULT_PER_PAGE = 12;

    private const LOAD_MORE_STEP = 3;

    private const ALLOWED_SORTS = [
        'popular_desc',
        'date_desc',
        'price_asc',
        'price_desc',
    ];

    public string $locale;

    public string $sort = 'popular_desc';

    public int $perPage = self::DEFAULT_PER_PAGE;

    public ?string $slug = null;

    public array $filters = [];

    protected function queryString(): array
    {
        return [
            'sort' => ['except' => 'popular_desc'],
            'perPage' => ['except' => self::DEFAULT_PER_PAGE],
            'filters' => ['except' => []],
        ];
    }

    public function mount(?string $locale = null, ?string $slug = null): void
    {
        $this->locale = Locales::resolve($locale);
        $this->slug = filled($slug) ? $slug : null;
        $this->sort = $this->normalizeSort((string) request()->input('sort', $this->sort));
        $this->perPage = $this->normalizePerPage((int) request()->input('perPage', $this->perPage));
        $this->filters = $this->normalizeFilters((array) request()->input('filters', $this->filters));
    }

    public function loadMore(): void
    {
        $this->perPage = $this->normalizePerPage($this->perPage + self::LOAD_MORE_STEP);
    }

    public function updatedSort(string $value): void
    {
        $this->sort = $this->normalizeSort($value);
        $this->resetPage();
    }

    public function updatedFilters(): void
    {
        $normalized = $this->normalizeFilters($this->filters);

        if ($normalized !== $this->filters) {
            $this->filters = $normalized;
        }

        $this->resetPage();
    }

    public function setSort(string $sort): void
    {
        $this->sort = $this->normalizeSort($sort);
        $this->resetPage();
    }

    public function applyFilters(array $filters): void
    {
        $this->filters = $this->normalizeFilters($filters);
        $this->resetPage();
    }

    public function applyPriceRange(int|string|null $from = null, int|string|null $to = null): void
    {
        $bounds = $this->defaultPriceRange();
        $min = (int) ($bounds['min'] ?? 0);
        $max = (int) ($bounds['max'] ?? 1000);

        $from = max($min, min($max, (int) ($from ?? $min)));
        $to = max($min, min($max, (int) ($to ?? $max)));

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        if ($from === $min && $to === $max) {
            unset($this->filters['price']);
        } else {
            $this->filters['price'] = [
                'from' => $from,
                'to' => $to,
            ];
        }

        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filters = [];
        $this->resetPage();
    }

    public function removePriceFilter(): void
    {
        unset($this->filters['price']);
        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function removeCategoryFilter(string|int $category): void
    {
        $categories = collect((array) ($this->filters['categories'] ?? []))
            ->reject(fn ($value) => (string) $value === (string) $category)
            ->values()
            ->all();

        if ($categories === []) {
            unset($this->filters['categories']);
        } else {
            $this->filters['categories'] = $categories;
        }

        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function removeCategoryFilterAtIndex(int $index): void
    {
        unset($this->filters['categories'][$index]);
        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function removeAttributeFilter(string $attributeCode, string $value): void
    {
        $selectedValues = collect((array) data_get($this->filters, "attributes.{$attributeCode}", []))
            ->reject(fn ($selectedValue) => (string) $selectedValue === $value)
            ->values()
            ->all();

        if ($selectedValues === []) {
            unset($this->filters['attributes'][$attributeCode]);
        } else {
            $this->filters['attributes'][$attributeCode] = $selectedValues;
        }

        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function removeAttributeFilterAtIndex(string $attributeCode, int $index): void
    {
        unset($this->filters['attributes'][$attributeCode][$index]);
        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function toggleAttributeFilter(string $attributeCode, string $value, bool $checked): void
    {
        $selectedValues = collect((array) data_get($this->filters, "attributes.{$attributeCode}", []))
            ->map(fn ($selectedValue) => (string) $selectedValue);

        if ($checked) {
            $selectedValues->push($value);
        } else {
            $selectedValues = $selectedValues->reject(fn ($selectedValue) => $selectedValue === $value);
        }

        $selectedValues = $selectedValues
            ->unique()
            ->values()
            ->all();

        if ($selectedValues === []) {
            unset($this->filters['attributes'][$attributeCode]);
        } else {
            $this->filters['attributes'][$attributeCode] = $selectedValues;
        }

        $this->filters = $this->normalizeFilters($this->filters);
        $this->resetPage();
    }

    public function render(CatalogProductListQuery $products, CatalogFiltersQuery $filters): View
    {
        $currentCategory = $this->resolveCurrentCategory();

        abort_if(! $currentCategory, 404);

        $selectedCategoryIds = $this->resolveSelectedCategoryIds($currentCategory);
        $effectiveScopeIds = $selectedCategoryIds !== [] ? $selectedCategoryIds : [0];

        $categoryFilters = $this->filters;
        $userSelectedCategories = collect((array) ($categoryFilters['categories'] ?? []))
            ->map(fn ($value) => is_numeric($value) ? (int) $value : (string) $value)
            ->intersect($selectedCategoryIds)
            ->values()
            ->all();

        $categoryFilters['categories'] = $userSelectedCategories !== []
            ? $userSelectedCategories
            : $effectiveScopeIds;

        $categories = $currentCategory->categories->values();

        $productsList = $products->handle($this->locale, $this->sort, $categoryFilters, $this->perPage);
        $filterOptions = $filters->handle($this->locale, $effectiveScopeIds);

        $archiveTitle = $currentCategory->translation($this->locale)?->name ?? __('Всі товари');
        $seo = app(LocalizedSeoResolver::class)->forTranslatedContent(
            locale: $this->locale,
            translations: $currentCategory->translations,
            urlForLocale: fn (string $supportedLocale): string => $this->resolveCategoryUrl($currentCategory, $supportedLocale),
            fallback: [
                'title' => $archiveTitle,
                'heading' => $archiveTitle,
                'description' => __('Каталог головних уборів ShopHats - шапки, кепки, берети, шарфи та рукавички для всієї родини.'),
            ],
            availableLocales: Locales::supported(),
        );

        $categoryBlocks = array_values($currentCategory->translation($this->locale)?->blocks ?? []);

        return view('shophats::pages.catalog', [
            'products' => $productsList,
            'filterOptions' => $filterOptions,
            'categories' => $categories,
            'currentCategory' => $currentCategory,
            'archiveTitle' => $archiveTitle,
            'currentSort' => $this->sort,
            'currentFilters' => $this->filters,
            'sidebarContext' => [
                'category_scope' => $effectiveScopeIds,
            ],
            'categoryBlocks' => $categoryBlocks,
            'seo' => $seo,
        ])->layout('shophats::layouts.base', [
            'seo' => $seo,
        ]);
    }

    private function resolveCurrentCategory(): ?CityCategory
    {
        if (! filled($this->slug)) {
            return null;
        }

        return CityCategory::query()
            ->with('translations')
            ->with([
                'parent.translations',
                'categories' => fn ($query) => $query->withTranslationsFor($this->locale)->orderBy('sort')->orderBy('path'),
            ])
            ->where(function ($query) {
                $query
                    ->where('path', $this->slug)
                    ->orWhere(fn ($builder) => $builder->whereLocalizedSlug($this->slug, $this->locale));
            })
            ->first();
    }

    private function resolveSelectedCategoryIds(CityCategory $cityCategory): array
    {
        return $cityCategory->categories->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    private function resolveCategoryUrl(CityCategory $cityCategory, string $locale): string
    {
        return $cityCategory->frontendUrl($locale) ?? Locales::path('/'.$cityCategory->path, $locale);
    }

    private function normalizeSort(string $sort): string
    {
        return in_array($sort, self::ALLOWED_SORTS, true) ? $sort : 'popular_desc';
    }

    private function normalizePerPage(int $perPage): int
    {
        return max(self::DEFAULT_PER_PAGE, $perPage);
    }

    private function normalizeFilters(array $filters): array
    {
        $normalized = $filters;

        if (isset($normalized['price']) && is_array($normalized['price'])) {
            $price = $normalized['price'];
            $from = data_get($price, 'from');
            $to = data_get($price, 'to');

            if ($from === '' || $from === null) {
                unset($price['from']);
            } else {
                $price['from'] = (int) $from;
            }

            if ($to === '' || $to === null) {
                unset($price['to']);
            } else {
                $price['to'] = (int) $to;
            }

            if ($price === []) {
                unset($normalized['price']);
            } else {
                $normalized['price'] = $price;
            }
        }

        if (isset($normalized['categories'])) {
            $normalized['categories'] = collect((array) $normalized['categories'])
                ->filter(fn ($value) => filled($value) || $value === '0' || $value === 0)
                ->map(fn ($value) => is_numeric($value) ? (int) $value : (string) $value)
                ->unique()
                ->values()
                ->all();

            if ($normalized['categories'] === []) {
                unset($normalized['categories']);
            }
        }

        if (isset($normalized['attributes']) && is_array($normalized['attributes'])) {
            $normalized['attributes'] = collect($normalized['attributes'])
                ->map(function ($values) {
                    return collect((array) $values)
                        ->filter(fn ($value) => filled($value) || $value === '0' || $value === 0)
                        ->map(fn ($value) => (string) $value)
                        ->unique()
                        ->values()
                        ->all();
                })
                ->filter(fn (array $values) => $values !== [])
                ->all();

            if ($normalized['attributes'] === []) {
                unset($normalized['attributes']);
            }
        }

        return $normalized;
    }

    private function defaultPriceRange(): array
    {
        $currentCategory = $this->resolveCurrentCategory();

        if (! $currentCategory) {
            return ['min' => 0, 'max' => 1000];
        }

        $categoryIds = $this->resolveSelectedCategoryIds($currentCategory);

        return app(CatalogFiltersQuery::class)->handle($this->locale, $categoryIds !== [] ? $categoryIds : [0])['price'] ?? ['min' => 0, 'max' => 1000];
    }
}
