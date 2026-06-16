@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            defaultCenter: { lat: 50.4501, lng: 30.5234 },
            searchQuery: '',
            suggestions: [],
            searchPlaceholder: @js(__('commero::admin.site_setting.address_location_search_placeholder')),
            statusMessage: '',
            searchTimer: null,
            map: null,
            marker: null,
            geocoder: null,
            autocompleteService: null,
            placesService: null,
            mapsReady: false,
            highlightedSuggestionIndex: -1,

            init() {
                this.state = this.normalizeState(this.state)
                this.searchQuery = this.state.address || ''

                this.$watch('searchQuery', (value) => {
                    window.clearTimeout(this.searchTimer)

                    if (! this.mapsReady) {
                        return
                    }

                    const trimmed = String(value || '').trim()

                    if (trimmed.length < 3) {
                        this.suggestions = []
                        this.highlightedSuggestionIndex = -1
                        return
                    }

                    this.searchTimer = window.setTimeout(() => this.fetchPredictions(trimmed), 250)
                })

                this.loadMapsLibrary()
            },

            normalizeState(state) {
                if (! state || typeof state !== 'object') {
                    return { address: '', coordinates: '' }
                }

                return {
                    address: state.address || '',
                    coordinates: state.coordinates || '',
                }
            },

            readApiKey() {
                return (document.querySelector('input[name=&quot;data.google_maps_api_key&quot;]')?.value || '').trim()
            },

            async loadMapsLibrary() {
                const apiKey = this.readApiKey()

                if (! apiKey) {
                    this.statusMessage = @js(__('commero::admin.site_setting.address_location_missing_key'))
                    return
                }

                this.statusMessage = ''

                if (! window.__commeroGoogleMapsLoader) {
                    window.__commeroGoogleMapsLoader = {}
                }

                if (window.__commeroGoogleMapsLoader.apiKey !== apiKey) {
                    document.querySelectorAll('script[data-commero-google-maps]').forEach((script) => script.remove())
                    window.__commeroGoogleMapsLoader.promise = null
                    window.__commeroGoogleMapsLoader.apiKey = apiKey
                }

                if (! window.__commeroGoogleMapsLoader.promise) {
                    window.__commeroGoogleMapsLoader.promise = new Promise((resolve, reject) => {
                        if (window.google?.maps?.places) {
                            resolve(window.google.maps)
                            return
                        }

                        const script = document.createElement('script')
                        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`
                        script.async = true
                        script.defer = true
                        script.dataset.commeroGoogleMaps = 'true'
                        script.onload = () => resolve(window.google.maps)
                        script.onerror = () => reject(new Error('google-maps-load-failed'))
                        document.head.appendChild(script)
                    })
                }

                try {
                    await window.__commeroGoogleMapsLoader.promise
                    this.initMap()
                } catch (error) {
                    this.statusMessage = @js(__('commero::admin.site_setting.address_location_load_failed'))
                }
            },

            initMap() {
                if (! window.google?.maps || this.mapsReady) {
                    return
                }

                const center = this.parseCoordinates(this.state.coordinates) ?? this.defaultCenter

                this.map = new google.maps.Map(this.$refs.map, {
                    center,
                    zoom: this.parseCoordinates(this.state.coordinates) ? 16 : 12,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                })

                this.marker = new google.maps.Marker({
                    map: this.map,
                    position: center,
                    draggable: true,
                })

                this.geocoder = new google.maps.Geocoder()
                this.autocompleteService = new google.maps.places.AutocompleteService()
                this.placesService = new google.maps.places.PlacesService(document.createElement('div'))
                this.mapsReady = true

                this.marker.addListener('dragend', (event) => {
                    const location = {
                        lat: event.latLng.lat(),
                        lng: event.latLng.lng(),
                    }

                    this.setMarkerLocation(location)
                    this.updateCoordinates(location)
                    this.reverseGeocode(location)
                })

                this.map.addListener('click', (event) => {
                    const location = {
                        lat: event.latLng.lat(),
                        lng: event.latLng.lng(),
                    }

                    this.setMarkerLocation(location)
                    this.updateCoordinates(location)
                    this.reverseGeocode(location)
                })

                if (this.state.address && ! this.parseCoordinates(this.state.coordinates)) {
                    this.geocodeAddress(this.state.address)
                } else if (this.state.address) {
                    this.setMarkerLocation(center)
                }
            },

            fetchPredictions(query) {
                if (! this.autocompleteService) {
                    return
                }

                this.autocompleteService.getPlacePredictions(
                    {
                        input: query,
                        types: ['geocode'],
                    },
                    (predictions, status) => {
                        if (status !== google.maps.places.PlacesServiceStatus.OK || ! Array.isArray(predictions)) {
                            this.suggestions = []
                            this.highlightedSuggestionIndex = -1
                            return
                        }

                        this.suggestions = predictions.map((prediction) => ({
                            placeId: prediction.place_id,
                            label: prediction.description,
                        }))
                        this.highlightedSuggestionIndex = this.suggestions.length > 0 ? 0 : -1
                    },
                )
            },

            chooseSuggestion(suggestion) {
                if (! suggestion?.placeId || ! this.placesService) {
                    return
                }

                this.placesService.getDetails(
                    {
                        placeId: suggestion.placeId,
                        fields: ['formatted_address', 'geometry', 'name'],
                    },
                    (place, status) => {
                        if (status !== google.maps.places.PlacesServiceStatus.OK || ! place?.geometry?.location) {
                            this.statusMessage = @js(__('commero::admin.site_setting.address_location_not_found'))
                            return
                        }

                        const location = {
                            lat: place.geometry.location.lat(),
                            lng: place.geometry.location.lng(),
                        }
                        const address = place.formatted_address || place.name || suggestion.label

                        this.state = {
                            ...this.state,
                            address,
                            coordinates: this.formatCoordinates(location),
                        }
                        this.searchQuery = address
                        this.suggestions = []
                        this.highlightedSuggestionIndex = -1
                        this.setMarkerLocation(location)
                        this.statusMessage = ''
                    },
                )
            },

            chooseHighlightedSuggestion() {
                if (this.highlightedSuggestionIndex < 0 || ! this.suggestions[this.highlightedSuggestionIndex]) {
                    return
                }

                this.chooseSuggestion(this.suggestions[this.highlightedSuggestionIndex])
            },

            highlightNextSuggestion() {
                if (! this.suggestions.length) {
                    return
                }

                this.highlightedSuggestionIndex = (this.highlightedSuggestionIndex + 1) % this.suggestions.length
            },

            highlightPreviousSuggestion() {
                if (! this.suggestions.length) {
                    return
                }

                this.highlightedSuggestionIndex = this.highlightedSuggestionIndex <= 0
                    ? this.suggestions.length - 1
                    : this.highlightedSuggestionIndex - 1
            },

            parseCoordinates(value) {
                const match = String(value || '').trim().match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/)

                if (! match) {
                    return null
                }

                return {
                    lat: Number.parseFloat(match[1]),
                    lng: Number.parseFloat(match[2]),
                }
            },

            formatCoordinates(location) {
                return `${location.lat.toFixed(6)},${location.lng.toFixed(6)}`
            },

            setMarkerLocation(location) {
                if (! this.map || ! this.marker) {
                    return
                }

                this.marker.setPosition(location)
                this.map.setCenter(location)
                this.map.setZoom(16)
            },

            geocodeAddress(address) {
                if (! this.geocoder) {
                    return
                }

                this.geocoder.geocode({ address }, (results, status) => {
                    if (status !== 'OK' || ! results?.[0]?.geometry?.location) {
                        this.statusMessage = @js(__('commero::admin.site_setting.address_location_not_found'))
                        return
                    }

                    const result = results[0]
                    const location = {
                        lat: result.geometry.location.lat(),
                        lng: result.geometry.location.lng(),
                    }

                    this.state = {
                        ...this.state,
                        address: result.formatted_address || address,
                        coordinates: this.formatCoordinates(location),
                    }
                    this.searchQuery = this.state.address
                    this.setMarkerLocation(location)
                    this.statusMessage = ''
                })
            },

            reverseGeocode(location) {
                if (! this.geocoder) {
                    return
                }

                this.geocoder.geocode({ location }, (results, status) => {
                    if (status !== 'OK' || ! results?.[0]) {
                        return
                    }

                    this.state = {
                        ...this.state,
                        address: results[0].formatted_address || this.state.address,
                    }
                    this.searchQuery = this.state.address
                    this.statusMessage = ''
                })
            },

            updateCoordinates(location) {
                this.state = {
                    ...this.state,
                    coordinates: this.formatCoordinates(location),
                }
            },
        }"
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
