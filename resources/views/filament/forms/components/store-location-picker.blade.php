@php
    $statePath = $getStatePath();
    $entangledState = $applyStateBindingModifiers("\$entangle('{$statePath}')");
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="window.commeroStoreLocationPicker({
            state: $wire.{{ $entangledState }},
            defaultCenter: { lat: 50.4501, lng: 30.5234 },
            apiKeyInputId: 'form.google_maps_api_key',
            apiKeyInputName: 'data.google_maps_api_key',
            searchPlaceholder: @js(__('commero::admin.site_setting.address_location_search_placeholder')),
            missingKeyMessage: @js(__('commero::admin.site_setting.address_location_missing_key')),
            loadFailedMessage: @js(__('commero::admin.site_setting.address_location_load_failed')),
            notFoundMessage: @js(__('commero::admin.site_setting.address_location_not_found')),
        })"
        x-init="init()"
        class="space-y-4"
    >
        <div class="relative">
            <label class="fi-fo-field-label mb-2 block">
                {{ __('commero::admin.site_setting.address_location_search') }}
            </label>

            <x-filament::input.wrapper>
                <x-filament::input
                    x-model="searchQuery"
                    x-on:keydown.arrow-down.prevent="highlightNextSuggestion()"
                    x-on:keydown.arrow-up.prevent="highlightPreviousSuggestion()"
                    x-on:keydown.enter.prevent="chooseHighlightedSuggestion()"
                    x-on:focus="if (searchQuery.length >= 3) fetchPredictions(searchQuery)"
                    x-bind:placeholder="searchPlaceholder"
                    type="text"
                    autocomplete="off"
                />
            </x-filament::input.wrapper>

            <div
                x-cloak
                x-show="suggestions.length > 0"
                x-transition.opacity.duration.150ms
                class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg ring-1 ring-gray-950/5 dark:border-white/10 dark:bg-gray-900 dark:ring-white/10"
            >
                <template x-for="(suggestion, index) in suggestions" :key="suggestion.placeId">
                    <button
                        type="button"
                        x-on:click="chooseSuggestion(suggestion)"
                        x-bind:class="index === highlightedSuggestionIndex ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-200' : 'text-gray-950 dark:text-white'"
                        class="block w-full border-b border-gray-100 px-4 py-3 text-left text-sm transition last:border-b-0 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
                    >
                        <span x-text="suggestion.label"></span>
                    </button>
                </template>
            </div>
        </div>

        <div>
            <label class="fi-fo-field-label mb-2 block">
                {{ __('commero::admin.site_setting.address_value') }}
            </label>

            <x-filament::input.wrapper>
                <textarea
                    x-model="state.address"
                    x-on:change="geocodeAddress(state.address)"
                    rows="3"
                    class="block w-full resize-y bg-transparent px-3 py-2 text-sm text-gray-950 outline-none placeholder:text-gray-400 focus:outline-none dark:text-white dark:placeholder:text-gray-500"
                ></textarea>
            </x-filament::input.wrapper>
        </div>

        <div>
            <label class="fi-fo-field-label mb-2 block">
                {{ __('commero::admin.site_setting.address_coordinates') }}
            </label>

            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    x-model="state.coordinates"
                    readonly
                    class="bg-transparent text-gray-600 dark:text-gray-300"
                />
            </x-filament::input.wrapper>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-950">
            <div x-ref="map" class="h-[360px] w-full" wire:ignore></div>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('commero::admin.site_setting.address_location_search_hint') }}
        </p>

        <p x-show="statusMessage" x-text="statusMessage" class="text-sm text-danger-600"></p>
    </div>
</x-dynamic-component>
