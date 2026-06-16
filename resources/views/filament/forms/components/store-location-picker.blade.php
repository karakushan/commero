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
            <label class="block space-y-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('commero::admin.site_setting.address_location_search') }}
                </span>
                <input
                    x-model="searchQuery"
                    x-on:keydown.arrow-down.prevent="highlightNextSuggestion()"
                    x-on:keydown.arrow-up.prevent="highlightPreviousSuggestion()"
                    x-on:keydown.enter.prevent="chooseHighlightedSuggestion()"
                    x-on:focus="if (searchQuery.length >= 3) fetchPredictions(searchQuery)"
                    x-bind:placeholder="searchPlaceholder"
                    type="text"
                    class="fi-input block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/15 dark:bg-gray-950 dark:text-white"
                >
            </label>

            <div
                x-cloak
                x-show="suggestions.length > 0"
                class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-white/10 dark:bg-gray-900"
            >
                <template x-for="(suggestion, index) in suggestions" :key="suggestion.placeId">
                    <button
                        type="button"
                        x-on:click="chooseSuggestion(suggestion)"
                        x-bind:class="index === highlightedSuggestionIndex ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-200' : 'bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100'"
                        class="block w-full border-b border-gray-100 px-4 py-3 text-left text-sm transition last:border-b-0 hover:bg-primary-50 hover:text-primary-700 dark:border-white/10 dark:hover:bg-primary-500/10 dark:hover:text-primary-200"
                    >
                        <span x-text="suggestion.label"></span>
                    </button>
                </template>
            </div>
        </div>

        <label class="block space-y-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('commero::admin.site_setting.address_value') }}
            </span>
            <textarea
                x-model="state.address"
                x-on:change="geocodeAddress(state.address)"
                rows="3"
                class="fi-textarea block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-950 shadow-sm outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-white/15 dark:bg-gray-950 dark:text-white"
            ></textarea>
        </label>

        <label class="block space-y-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('commero::admin.site_setting.address_coordinates') }}
            </span>
            <input
                type="text"
                x-model="state.coordinates"
                readonly
                class="fi-input block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 shadow-sm outline-none dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
            >
        </label>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div x-ref="map" class="h-[360px] w-full" wire:ignore></div>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('commero::admin.site_setting.address_location_search_hint') }}
        </p>

        <p x-show="statusMessage" x-text="statusMessage" class="text-sm text-danger-600"></p>
    </div>
</x-dynamic-component>
