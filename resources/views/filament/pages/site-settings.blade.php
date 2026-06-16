<script>
    window.commeroStoreLocationPicker = window.commeroStoreLocationPicker || function (config) {
        return {
                state: config.state ?? {},
                defaultCenter: config.defaultCenter ?? { lat: 50.4501, lng: 30.5234 },
                apiKeyInputName: config.apiKeyInputName ?? 'data.google_maps_api_key',
                searchPlaceholder: config.searchPlaceholder ?? '',
                searchQuery: '',
                suggestions: [],
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

                        if (!this.mapsReady) {
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
                    if (!state || typeof state !== 'object') {
                        return { address: '', coordinates: '' }
                    }

                    return {
                        address: state.address || '',
                        coordinates: state.coordinates || '',
                    }
                },

                readApiKey() {
                    return (document.querySelector(`input[name="${this.apiKeyInputName}"]`)?.value || '').trim()
                },

                async loadMapsLibrary() {
                    const apiKey = this.readApiKey()

                    if (!apiKey) {
                        this.statusMessage = config.missingKeyMessage
                        return
                    }

                    this.statusMessage = ''

                    if (!window.__commeroGoogleMapsLoader) {
                        window.__commeroGoogleMapsLoader = {}
                    }

                    if (window.__commeroGoogleMapsLoader.apiKey !== apiKey) {
                        document.querySelectorAll('script[data-commero-google-maps]').forEach((script) => script.remove())
                        window.__commeroGoogleMapsLoader.promise = null
                        window.__commeroGoogleMapsLoader.apiKey = apiKey
                        if (window.google?.maps) {
                            window.google = undefined
                        }
                    }

                    if (!window.__commeroGoogleMapsLoader.promise) {
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
                        this.statusMessage = config.loadFailedMessage
                    }
                },

                initMap() {
                    if (!window.google?.maps || this.mapsReady) {
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

                    if (this.state.address && !this.parseCoordinates(this.state.coordinates)) {
                        this.geocodeAddress(this.state.address)
                    } else if (this.state.address) {
                        this.setMarkerLocation(center)
                    }
                },

                fetchPredictions(query) {
                    if (!this.autocompleteService) {
                        return
                    }

                    this.autocompleteService.getPlacePredictions(
                        {
                            input: query,
                            types: ['geocode'],
                        },
                        (predictions, status) => {
                            if (status !== google.maps.places.PlacesServiceStatus.OK || !Array.isArray(predictions)) {
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
                    if (!suggestion?.placeId || !this.placesService) {
                        return
                    }

                    this.placesService.getDetails(
                        {
                            placeId: suggestion.placeId,
                            fields: ['formatted_address', 'geometry', 'name'],
                        },
                        (place, status) => {
                            if (status !== google.maps.places.PlacesServiceStatus.OK || !place?.geometry?.location) {
                                this.statusMessage = config.notFoundMessage
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
                    if (this.highlightedSuggestionIndex < 0 || !this.suggestions[this.highlightedSuggestionIndex]) {
                        return
                    }

                    this.chooseSuggestion(this.suggestions[this.highlightedSuggestionIndex])
                },

                highlightNextSuggestion() {
                    if (!this.suggestions.length) {
                        return
                    }

                    this.highlightedSuggestionIndex = (this.highlightedSuggestionIndex + 1) % this.suggestions.length
                },

                highlightPreviousSuggestion() {
                    if (!this.suggestions.length) {
                        return
                    }

                    this.highlightedSuggestionIndex = this.highlightedSuggestionIndex <= 0
                        ? this.suggestions.length - 1
                        : this.highlightedSuggestionIndex - 1
                },

                parseCoordinates(value) {
                    const match = String(value || '').trim().match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/)

                    if (!match) {
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
                    if (!this.map || !this.marker) {
                        return
                    }

                    this.marker.setPosition(location)
                    this.map.setCenter(location)
                    this.map.setZoom(16)
                },

                geocodeAddress(address) {
                    if (!this.geocoder || !String(address || '').trim()) {
                        return
                    }

                    this.geocoder.geocode({ address }, (results, status) => {
                        if (status !== 'OK' || !results?.[0]?.geometry?.location) {
                            this.statusMessage = config.notFoundMessage
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
                    if (!this.geocoder) {
                        return
                    }

                    this.geocoder.geocode({ location }, (results, status) => {
                        if (status !== 'OK' || !results?.[0]) {
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
            }
        }
</script>

<x-filament-panels::page>
    {{ $this->form }}
</x-filament-panels::page>
