<script>
    window.commeroStoreLocationPicker = function (config) {
        return {
            state: config.state ?? {},
            apiKey: config.apiKey ?? '',
            apiKeyInputId: config.apiKeyInputId ?? 'form.google_maps_api_key',
            apiKeyInputName: config.apiKeyInputName ?? 'data.google_maps_api_key',
            defaultCenter: config.defaultCenter ?? { lat: 50.4501, lng: 30.5234 },
            searchQuery: '',
            suggestions: [],
            statusMessage: '',
            searchTimer: null,
            latestPredictionRequest: 0,
            map: null,
            marker: null,
            geocoder: null,
            autocompleteSuggestion: null,
            AutocompleteSessionToken: null,
            autocompleteSessionToken: null,
            mapsReady: false,
            highlightedSuggestionIndex: -1,

            async init() {
                this.state = this.normalizeState(this.state)
                this.searchQuery = this.state.address

                this.$watch('searchQuery', (value) => {
                    window.clearTimeout(this.searchTimer)

                    const query = String(value || '').trim()

                    if (query.length < 3) {
                        this.latestPredictionRequest++
                        this.suggestions = []
                        this.highlightedSuggestionIndex = -1
                        this.statusMessage = ''

                        return
                    }

                    if (!this.mapsReady) {
                        return
                    }

                    this.searchTimer = window.setTimeout(() => {
                        void this.fetchPredictions(query)
                    }, 250)
                })

                await this.loadMapsLibrary()

                if (this.searchQuery.trim().length >= 3) {
                    await this.fetchPredictions(this.searchQuery.trim())
                }
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
                if (typeof this.apiKey === 'string' && this.apiKey.trim() !== '') {
                    return this.apiKey.trim()
                }

                const input = (
                    (this.apiKeyInputId ? document.getElementById(this.apiKeyInputId) : null)
                    ?? (this.apiKeyInputName ? document.querySelector(`input[name="${this.apiKeyInputName}"]`) : null)
                    ?? document.querySelector('input[id$="google_maps_api_key"]')
                )

                return (input?.value || '').trim()
            },

            async loadMapsLibrary() {
                const apiKey = this.readApiKey()

                if (!apiKey) {
                    this.statusMessage = config.missingKeyMessage

                    return
                }

                this.statusMessage = ''

                try {
                    const loader = window.__commeroGoogleMapsLoader ??= { apiKey: null, promise: null }

                    if (loader.apiKey !== apiKey) {
                        if (loader.apiKey) {
                            document.querySelectorAll('script[data-commero-google-maps]').forEach((script) => script.remove())

                            if (window.google?.maps) {
                                window.google = undefined
                            }
                        }

                        loader.apiKey = apiKey
                        loader.promise = null
                    }

                    if (!loader.promise) {
                        loader.promise = new Promise((resolve, reject) => {
                            if (window.google?.maps?.importLibrary) {
                                resolve()

                                return
                            }

                            const script = document.createElement('script')
                            script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&v=weekly&loading=async`
                            script.async = true
                            script.defer = true
                            script.dataset.commeroGoogleMaps = 'true'
                            script.onload = () => window.google?.maps?.importLibrary
                                ? resolve()
                                : reject(new Error('google-maps-loader-unavailable'))
                            script.onerror = () => reject(new Error('google-maps-load-failed'))
                            document.head.appendChild(script)
                        })
                    }

                    await loader.promise

                    const [mapsLibrary, markerLibrary, geocodingLibrary, placesLibrary] = await Promise.all([
                        google.maps.importLibrary('maps'),
                        google.maps.importLibrary('marker'),
                        google.maps.importLibrary('geocoding'),
                        google.maps.importLibrary('places'),
                    ])

                    this.Map = mapsLibrary.Map
                    this.Marker = markerLibrary.Marker
                    this.Geocoder = geocodingLibrary.Geocoder
                    this.autocompleteSuggestion = placesLibrary.AutocompleteSuggestion
                    this.AutocompleteSessionToken = placesLibrary.AutocompleteSessionToken
                    this.autocompleteSessionToken = new this.AutocompleteSessionToken()

                    this.initMap()
                } catch (error) {
                    this.statusMessage = config.loadFailedMessage
                }
            },

            initMap() {
                if (!this.Map || this.mapsReady) {
                    return
                }

                const coordinates = this.parseCoordinates(this.state.coordinates)
                const location = coordinates ?? this.defaultCenter

                this.map = new this.Map(this.$refs.map, {
                    center: location,
                    zoom: coordinates ? 16 : 12,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                })

                this.marker = new this.Marker({
                    map: this.map,
                    position: location,
                    draggable: true,
                    visible: true,
                })

                this.geocoder = new this.Geocoder()
                this.mapsReady = true
                this.refreshMap()

                this.marker.addListener('dragend', (event) => {
                    const markerLocation = {
                        lat: event.latLng.lat(),
                        lng: event.latLng.lng(),
                    }

                    this.setMarkerLocation(markerLocation)
                    this.updateCoordinates(markerLocation)
                    void this.reverseGeocode(markerLocation)
                })

                this.map.addListener('click', (event) => {
                    const markerLocation = {
                        lat: event.latLng.lat(),
                        lng: event.latLng.lng(),
                    }

                    this.setMarkerLocation(markerLocation)
                    this.updateCoordinates(markerLocation)
                    void this.reverseGeocode(markerLocation)
                })

                if (this.state.address && !coordinates) {
                    void this.geocodeAddress(this.state.address)
                } else {
                    this.setMarkerLocation(location)
                }
            },

            refreshMap() {
                if (!this.map || !window.google?.maps) {
                    return
                }

                window.setTimeout(() => {
                    google.maps.event.trigger(this.map, 'resize')

                    const location = this.parseCoordinates(this.state.coordinates) ?? this.defaultCenter
                    this.map.setCenter(location)
                    this.marker?.setPosition(location)
                    this.marker?.setVisible(true)
                }, 50)
            },

            async fetchPredictions(query) {
                if (!this.autocompleteSuggestion) {
                    return
                }

                const requestId = ++this.latestPredictionRequest

                try {
                    const { suggestions } = await this.autocompleteSuggestion.fetchAutocompleteSuggestions({
                        input: query,
                        sessionToken: this.autocompleteSessionToken,
                        language: document.documentElement.lang || 'uk',
                        region: 'ua',
                        includedRegionCodes: ['ua'],
                    })

                    if (requestId !== this.latestPredictionRequest) {
                        return
                    }

                    this.suggestions = (suggestions ?? [])
                        .map((suggestion) => suggestion.placePrediction)
                        .filter(Boolean)
                        .map((prediction, index) => ({
                            placeId: prediction.placeId || `${prediction.text.toString()}-${index}`,
                            label: prediction.text.toString(),
                            prediction,
                        }))
                    this.highlightedSuggestionIndex = this.suggestions.length > 0 ? 0 : -1
                    this.statusMessage = this.suggestions.length > 0 ? '' : config.notFoundMessage
                } catch (error) {
                    if (requestId === this.latestPredictionRequest) {
                        this.suggestions = []
                        this.highlightedSuggestionIndex = -1
                        this.statusMessage = config.loadFailedMessage
                    }
                }
            },

            async chooseSuggestion(suggestion) {
                if (!suggestion?.prediction) {
                    return
                }

                try {
                    const place = suggestion.prediction.toPlace()
                    await place.fetchFields({ fields: ['formattedAddress', 'location', 'displayName'] })

                    if (!place.location) {
                        this.statusMessage = config.notFoundMessage

                        return
                    }

                    const location = {
                        lat: place.location.lat(),
                        lng: place.location.lng(),
                    }
                    const address = place.formattedAddress || place.displayName || suggestion.label

                    this.state = {
                        ...this.state,
                        address,
                        coordinates: this.formatCoordinates(location),
                    }
                    this.searchQuery = address
                    this.suggestions = []
                    this.highlightedSuggestionIndex = -1
                    this.setMarkerLocation(location)
                    this.autocompleteSessionToken = new this.AutocompleteSessionToken()
                    this.statusMessage = ''
                } catch (error) {
                    this.statusMessage = config.notFoundMessage
                }
            },

            chooseHighlightedSuggestion() {
                const suggestion = this.suggestions[this.highlightedSuggestionIndex]

                if (suggestion) {
                    void this.chooseSuggestion(suggestion)
                }
            },

            highlightNextSuggestion() {
                if (this.suggestions.length) {
                    this.highlightedSuggestionIndex = (this.highlightedSuggestionIndex + 1) % this.suggestions.length
                }
            },

            highlightPreviousSuggestion() {
                if (this.suggestions.length) {
                    this.highlightedSuggestionIndex = this.highlightedSuggestionIndex <= 0
                        ? this.suggestions.length - 1
                        : this.highlightedSuggestionIndex - 1
                }
            },

            parseCoordinates(value) {
                const match = String(value || '').trim().match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/)

                return match
                    ? { lat: Number.parseFloat(match[1]), lng: Number.parseFloat(match[2]) }
                    : null
            },

            formatCoordinates(location) {
                return `${location.lat.toFixed(6)},${location.lng.toFixed(6)}`
            },

            setMarkerLocation(location) {
                if (!this.map || !this.marker) {
                    return
                }

                this.marker.setVisible(true)
                this.marker.setPosition(location)
                this.map.setCenter(location)
                this.map.setZoom(16)
            },

            async geocodeAddress(address) {
                if (!this.geocoder || !String(address || '').trim()) {
                    return
                }

                try {
                    const { results } = await this.geocoder.geocode({ address })
                    const result = results?.[0]

                    if (!result?.geometry?.location) {
                        this.statusMessage = config.notFoundMessage

                        return
                    }

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
                } catch (error) {
                    this.statusMessage = config.notFoundMessage
                }
            },

            async reverseGeocode(location) {
                if (!this.geocoder) {
                    return
                }

                try {
                    const { results } = await this.geocoder.geocode({ location })

                    if (results?.[0]) {
                        this.state = {
                            ...this.state,
                            address: results[0].formatted_address || this.state.address,
                        }
                        this.searchQuery = this.state.address
                        this.statusMessage = ''
                    }
                } catch (error) {
                    this.statusMessage = config.notFoundMessage
                }
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
