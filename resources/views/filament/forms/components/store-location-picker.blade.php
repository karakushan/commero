@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="commeroStoreLocationPicker({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: @js($statePath),
            defaultCenter: { lat: 50.4501, lng: 30.5234 },
            apiKeyInputName: 'data.google_maps_api_key',
            searchPlaceholder: @js(__('commero::admin.site_setting.address_location_search_placeholder')),
        })"
        x-init="init()"
        class="space-y-4"
    >
        <div class="flex flex-wrap items-end gap-3">
            <label class="flex-1 space-y-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('commero::admin.site_setting.address_location_search') }}
                </span>
                <input
                    x-ref="search"
                    type="text"
                    x-model="searchQuery"
                    x-bind:placeholder="searchPlaceholder"
                    class="fi-input block w-full rounded-lg border-none bg-white px-3 py-2 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
                >
            </label>

            <button
                type="button"
                x-on:click="geocodeSearch()"
                class="fi-btn fi-btn-size-md rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white"
            >
                {{ __('commero::admin.site_setting.address_location_find_action') }}
            </button>
        </div>

        <label class="block space-y-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ __('commero::admin.site_setting.address_value') }}
            </span>
            <textarea
                x-model="state.address"
                x-on:change="geocodeCurrentAddress()"
                rows="3"
                class="fi-textarea block w-full rounded-lg border-none bg-white px-3 py-2 text-sm text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/20"
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
                class="fi-input block w-full rounded-lg border-none bg-gray-50 px-3 py-2 text-sm text-gray-700 shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-gray-200 dark:ring-white/20"
            >
        </label>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5">
            <div x-ref="map" class="h-[320px] w-full"></div>
        </div>

        <p x-show="statusMessage" x-text="statusMessage" class="text-sm text-gray-600 dark:text-gray-400"></p>
    </div>

    <script>
        if (!window.commeroStoreLocationPicker) {
            window.commeroStoreLocationPicker = function (config) {
                return {
                    state: config.state ?? {},
                    statePath: config.statePath,
                    defaultCenter: config.defaultCenter,
                    apiKeyInputName: config.apiKeyInputName,
                    searchPlaceholder: config.searchPlaceholder,
                    searchQuery: '',
                    statusMessage: '',
                    map: null,
                    marker: null,
                    geocoder: null,
                    autocomplete: null,

                    init() {
                        this.state = this.normalizeState(this.state);
                        this.searchQuery = this.state.address || '';
                        this.loadMapsLibrary();
                    },

                    normalizeState(state) {
                        if (!state || typeof state !== 'object') {
                            return { address: '', coordinates: '' };
                        }

                        return {
                            address: state.address || '',
                            coordinates: state.coordinates || '',
                        };
                    },

                    getApiKey() {
                        return (document.querySelector(`input[name="${this.apiKeyInputName}"]`)?.value || '').trim();
                    },

                    async loadMapsLibrary() {
                        const apiKey = this.getApiKey();

                        if (!apiKey) {
                            this.statusMessage = @js(__('commero::admin.site_setting.address_location_missing_key'));
                            return;
                        }

                        this.statusMessage = '';

                        if (!window.__commeroGoogleMapsLoader) {
                            window.__commeroGoogleMapsLoader = {};
                        }

                        if (window.__commeroGoogleMapsLoader.apiKey !== apiKey) {
                            document.querySelectorAll('script[data-commero-google-maps]').forEach((script) => script.remove());
                            window.__commeroGoogleMapsLoader.promise = null;
                            window.__commeroGoogleMapsLoader.apiKey = apiKey;
                        }

                        if (!window.__commeroGoogleMapsLoader.promise) {
                            window.__commeroGoogleMapsLoader.promise = new Promise((resolve, reject) => {
                                if (window.google?.maps?.places) {
                                    resolve(window.google.maps);
                                    return;
                                }

                                const script = document.createElement('script');
                                script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`;
                                script.async = true;
                                script.defer = true;
                                script.dataset.commeroGoogleMaps = 'true';
                                script.onload = () => resolve(window.google.maps);
                                script.onerror = () => reject(new Error('google-maps-load-failed'));
                                document.head.appendChild(script);
                            });
                        }

                        try {
                            await window.__commeroGoogleMapsLoader.promise;
                            this.initMap();
                        } catch (error) {
                            this.statusMessage = @js(__('commero::admin.site_setting.address_location_load_failed'));
                        }
                    },

                    initMap() {
                        if (!window.google?.maps) {
                            return;
                        }

                        const center = this.parseCoordinates(this.state.coordinates) ?? this.defaultCenter;

                        this.map = new google.maps.Map(this.$refs.map, {
                            center,
                            zoom: this.parseCoordinates(this.state.coordinates) ? 16 : 12,
                            mapTypeControl: false,
                            streetViewControl: false,
                            fullscreenControl: false,
                        });

                        this.marker = new google.maps.Marker({
                            map: this.map,
                            position: center,
                            draggable: true,
                        });

                        this.geocoder = new google.maps.Geocoder();

                        this.marker.addListener('dragend', (event) => {
                            const location = {
                                lat: event.latLng.lat(),
                                lng: event.latLng.lng(),
                            };

                            this.updateCoordinates(location);
                            this.reverseGeocode(location);
                        });

                        this.map.addListener('click', (event) => {
                            const location = {
                                lat: event.latLng.lat(),
                                lng: event.latLng.lng(),
                            };

                            this.setMarkerLocation(location);
                            this.updateCoordinates(location);
                            this.reverseGeocode(location);
                        });

                        this.autocomplete = new google.maps.places.Autocomplete(this.$refs.search, {
                            fields: ['formatted_address', 'geometry', 'name'],
                        });

                        this.autocomplete.addListener('place_changed', () => {
                            const place = this.autocomplete.getPlace();
                            const lat = place?.geometry?.location?.lat?.();
                            const lng = place?.geometry?.location?.lng?.();

                            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                                this.statusMessage = @js(__('commero::admin.site_setting.address_location_not_found'));
                                return;
                            }

                            const address = place.formatted_address || place.name || this.searchQuery;
                            const location = { lat, lng };
                            this.state = {
                                ...this.state,
                                address,
                            };
                            this.searchQuery = address;
                            this.setMarkerLocation(location);
                            this.updateCoordinates(location);
                            this.statusMessage = '';
                        });

                        if (this.state.address && !this.parseCoordinates(this.state.coordinates)) {
                            this.geocodeAddress(this.state.address);
                        } else if (this.state.address) {
                            this.searchQuery = this.state.address;
                            this.setMarkerLocation(center);
                        }
                    },

                    parseCoordinates(value) {
                        const match = String(value || '').trim().match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/);

                        if (!match) {
                            return null;
                        }

                        return {
                            lat: Number.parseFloat(match[1]),
                            lng: Number.parseFloat(match[2]),
                        };
                    },

                    formatCoordinates(location) {
                        return `${location.lat.toFixed(6)},${location.lng.toFixed(6)}`;
                    },

                    setMarkerLocation(location) {
                        if (!this.map || !this.marker) {
                            return;
                        }

                        this.marker.setPosition(location);
                        this.map.setCenter(location);
                        this.map.setZoom(16);
                    },

                    updateCoordinates(location) {
                        this.state = {
                            ...this.state,
                            coordinates: this.formatCoordinates(location),
                        };
                    },

                    geocodeSearch() {
                        if (!this.searchQuery.trim()) {
                            return;
                        }

                        this.geocodeAddress(this.searchQuery.trim());
                    },

                    geocodeCurrentAddress() {
                        if (!this.state.address.trim()) {
                            return;
                        }

                        this.searchQuery = this.state.address;
                        this.geocodeAddress(this.state.address.trim());
                    },

                    geocodeAddress(address) {
                        if (!this.geocoder) {
                            return;
                        }

                        this.geocoder.geocode({ address }, (results, status) => {
                            if (status !== 'OK' || !results?.[0]?.geometry?.location) {
                                this.statusMessage = @js(__('commero::admin.site_setting.address_location_not_found'));
                                return;
                            }

                            const result = results[0];
                            const location = {
                                lat: result.geometry.location.lat(),
                                lng: result.geometry.location.lng(),
                            };

                            this.state = {
                                ...this.state,
                                address: result.formatted_address || address,
                            };
                            this.searchQuery = this.state.address;
                            this.setMarkerLocation(location);
                            this.updateCoordinates(location);
                            this.statusMessage = '';
                        });
                    },

                    reverseGeocode(location) {
                        if (!this.geocoder) {
                            return;
                        }

                        this.geocoder.geocode({ location }, (results, status) => {
                            if (status !== 'OK' || !results?.[0]) {
                                return;
                            }

                            this.state = {
                                ...this.state,
                                address: results[0].formatted_address || this.state.address,
                            };
                            this.searchQuery = this.state.address;
                            this.statusMessage = '';
                        });
                    },
                };
            };
        }
    </script>
</x-dynamic-component>
