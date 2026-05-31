@php
    use Commero\Support\Filament\AdminLocales;
    use Commero\Support\Locales;

    $baseUrl = request()->fullUrl();

    if (app('livewire')->isLivewireRequest()) {
        $baseUrl = request()->header('Referer') ?: url()->previous();
    }

    $parsedBaseUrl = parse_url($baseUrl ?: url()->current());
    $baseQuery = [];

    parse_str($parsedBaseUrl['query'] ?? '', $baseQuery);

    $buildLocaleUrl = function (string $locale) use ($parsedBaseUrl, $baseQuery): string {
        $scheme = $parsedBaseUrl['scheme'] ?? request()->getScheme();
        $host = $parsedBaseUrl['host'] ?? request()->getHost();
        $port = isset($parsedBaseUrl['port']) ? ':'.$parsedBaseUrl['port'] : '';
        $path = $parsedBaseUrl['path'] ?? request()->getPathInfo();
        $fragment = isset($parsedBaseUrl['fragment']) ? '#'.$parsedBaseUrl['fragment'] : '';
        $query = array_merge($baseQuery, ['lang' => $locale]);

        $url = $scheme.'://'.$host.$port.$path;

        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return $url.$fragment;
    };

    $activeLocale = Locales::resolve($baseQuery['lang'] ?? request()->query('lang'));
    $locales = AdminLocales::supported();
    $activeLocaleLabel = __('commero::admin.locale_names.' . $activeLocale);
    $activeLocaleFlag = AdminLocales::flag($activeLocale);
@endphp

<x-filament::dropdown placement="bottom-end" teleport>
    <x-slot name="trigger">
        <button
            type="button"
            class="fi-btn fi-color-gray fi-size-sm inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-950/5 transition hover:bg-gray-50 dark:border-white/10 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-white/5"
        >
            <span class="text-base leading-none">{{ $activeLocaleFlag }}</span>
            <span>{{ $activeLocaleLabel }}</span>
            <x-filament::icon
                alias="panels::topbar.global-search.field.suffix"
                icon="heroicon-m-chevron-down"
                class="h-4 w-4 text-gray-400"
            />
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        @foreach ($locales as $locale)
            @php
                $localeLabel = __('commero::admin.locale_names.' . $locale);
                $localeFlag = AdminLocales::flag($locale);
            @endphp

            <x-filament::dropdown.list.item
                :color="$activeLocale === $locale ? 'primary' : 'gray'"
                :href="$buildLocaleUrl($locale)"
                tag="a"
            >
                <span class="flex items-center gap-2">
                    <span class="text-base leading-none">{{ $localeFlag }}</span>
                    <span>{{ $localeLabel }}</span>
                </span>
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
