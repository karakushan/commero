<x-filament-panels::page>
    {{ $this->form }}

    <script>
        (() => {
            if (window.__commeroSiteSettingsLocationPickerBooted) {
                return;
            }

            window.__commeroSiteSettingsLocationPickerBooted = true;

            const selectors = {
                search: 'input[data-location-address-search]',
            };

            const state = {
                googleLoading: false,
                observer: null,
                activeApiKey: null,
            };

            const getApiKeyInput = () => document.querySelector('input[name="data.google_maps_api_key"]');

            const getApiKey = () => {
                const input = getApiKeyInput();

                return (input?.value || '').trim();
            };

            const findRelatedField = (searchInput, suffix) => {
                const name = searchInput.getAttribute('name');

                if (! name) {
                    return null;
                }

                const relatedName = name.replace(/\.location_search$/, `.${suffix}`);

                return document.querySelector(`[name="${relatedName}"]`);
            };

            const setFieldValue = (field, value) => {
                if (! field) {
                    return;
                }

                field.value = value;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.dispatchEvent(new Event('change', { bubbles: true }));
            };

            const bindAutocomplete = (searchInput) => {
                if (searchInput.dataset.locationPickerBound === 'true' || ! window.google?.maps?.places) {
                    return;
                }

                searchInput.dataset.locationPickerBound = 'true';

                const autocomplete = new google.maps.places.Autocomplete(searchInput, {
                    fields: ['formatted_address', 'geometry', 'name'],
                });

                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();
                    const lat = place?.geometry?.location?.lat?.();
                    const lng = place?.geometry?.location?.lng?.();

                    if (! Number.isFinite(lat) || ! Number.isFinite(lng)) {
                        return;
                    }

                    setFieldValue(findRelatedField(searchInput, 'address'), place.formatted_address || place.name || searchInput.value.trim());
                    setFieldValue(findRelatedField(searchInput, 'coordinates'), `${lat.toFixed(6)},${lng.toFixed(6)}`);
                });
            };

            const initLocationPickers = () => {
                document.querySelectorAll(selectors.search).forEach(bindAutocomplete);
            };

            const loadGoogleMaps = () => {
                const apiKey = getApiKey();

                if (! apiKey || window.google?.maps?.places) {
                    initLocationPickers();
                    return;
                }

                if (state.googleLoading) {
                    return;
                }

                state.googleLoading = true;
                state.activeApiKey = apiKey;

                const existingScript = document.querySelector('script[data-commero-google-maps]');

                if (existingScript) {
                    existingScript.addEventListener('load', initLocationPickers, { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`;
                script.async = true;
                script.defer = true;
                script.dataset.commeroGoogleMaps = 'true';
                script.addEventListener('load', initLocationPickers, { once: true });
                document.head.appendChild(script);
            };

            const boot = () => {
                loadGoogleMaps();

                if (state.observer) {
                    return;
                }

                state.observer = new MutationObserver(() => {
                    initLocationPickers();
                });

                state.observer.observe(document.body, {
                    childList: true,
                    subtree: true,
                });
            };

            document.addEventListener('DOMContentLoaded', boot, { once: true });
            document.addEventListener('livewire:navigated', boot);
            document.addEventListener('livewire:initialized', boot);
            document.addEventListener('input', (event) => {
                const target = event.target;

                if (!(target instanceof HTMLInputElement) || target.name !== 'data.google_maps_api_key') {
                    return;
                }

                const apiKey = getApiKey();

                if (! apiKey || apiKey === state.activeApiKey || window.google?.maps?.places) {
                    return;
                }

                loadGoogleMaps();
            });
            boot();
        })();
    </script>
</x-filament-panels::page>
