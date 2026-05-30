<?php

namespace Commero\Interfaces\Http\Livewire;

use Commero\Application\Catalog\Queries\CatalogFiltersQuery;
use Commero\Application\Catalog\Queries\CatalogProductListQuery;
use Commero\Support\Locales;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SearchPage extends Component
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
    public string $q = '';
    public array $filters = [];

    protected function queryString(): array
    {
        return [
            'q' => ['except' => ''],
            'sort' => ['except' => 'popular_desc'],
            'perPage' => ['except' => self::DEFAULT_PER_PAGE],
            'filters' => ['except' => []],
        ];
    }

    public function mount(?string $locale = null): void
    {
        $this->locale = Locales::resolve($locale);
        $this->q = (string) request()->input('q', $this->q);
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
            $selectedValues = $selectedValues
                ->reject(fn ($selectedValue) => $selectedValue === $value);
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
        $searchFilters = $this->filters;

        // Add search query to filters
        if ($this->q) {
            $searchFilters['search'] = $this->q;
        }

        $productsList = $products->handle($this->locale, $this->sort, $searchFilters, $this->perPage);
        $filterOptions = $filters->handle($this->locale);

        $searchTitle = $this->q
            ? __('Found products for query ":query"', ['query' => $this->q])
            : __('Product search');

        return view('shophats::pages.search', [
            'products' => $productsList,
            'filterOptions' => $filterOptions,
            'searchQuery' => $this->q,
            'archiveTitle' => $searchTitle,
            'currentSort' => $this->sort,
            'currentFilters' => $this->filters,
        ])->layout('shophats::layouts.base', [
            'title' => $searchTitle,
            'meta_description' => __('Результати пошуку товарів у каталозі ShopHats'),
        ]);
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

        if (isset($normalized['attributes']) && is_array($normalized['attributes'])) {
            foreach ($normalized['attributes'] as $attributeCode => $values) {
                $attributeValues = collect((array) $values)
                    ->filter(fn ($value) => filled($value) || $value === '0' || $value === 0)
                    ->map(fn ($value) => (string) $value)
                    ->unique()
                    ->values()
                    ->all();

                if ($attributeValues === []) {
                    unset($normalized['attributes'][$attributeCode]);
                    continue;
                }

                $normalized['attributes'][$attributeCode] = $attributeValues;
            }

            if (($normalized['attributes'] ?? []) === []) {
                unset($normalized['attributes']);
            }
        }

        return $normalized;
    }

    private function defaultPriceRange(): array
    {
        return app(CatalogFiltersQuery::class)->handle($this->locale)['price'] ?? [
            'min' => 0,
            'max' => 1000,
        ];
    }
}
