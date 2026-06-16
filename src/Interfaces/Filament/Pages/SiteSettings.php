<?php

namespace Commero\Interfaces\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Commero\Models\SiteSetting;
use Commero\Rules\FlexibleUrl;
use Commero\Support\Filament\AdminLocales;
use Commero\Support\Locales;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class SiteSettings extends Page
{
    use HasPageShield;

    protected const ACTIVE_LOCALE_FIELD = 'active_locale_context';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 1;

    protected string $view = 'commero::filament.pages.site-settings';

    public ?array $data = [];

    public string $activeLocale = '';

    public function mount(): void
    {
        $this->activeLocale = $this->resolveActiveLocale();
        $this->form->fill($this->getFormState());
    }

    public static function getNavigationLabel(): string
    {
        return __('commero::admin.resources.site_setting.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return __('commero::admin.navigation.system');
    }

    public function getTitle(): string
    {
        return __('commero::admin.resources.site_setting.navigation');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Hidden::make(static::ACTIVE_LOCALE_FIELD)
                        ->dehydrated(),
                    Section::make(__('commero::admin.site_setting.general_section'))
                        ->schema([
                            TextInput::make('site_name')->label(__('commero::admin.site_setting.site_name'))->maxLength(255),
                            FileUpload::make('logo_path')
                                ->label(__('commero::admin.site_setting.logo_path'))
                                ->disk('public')
                                ->directory('settings')
                                ->visibility('public')
                                ->image(),
                            FileUpload::make('footer_logo_path')
                                ->label(__('commero::admin.site_setting.footer_logo_path'))
                                ->disk('public')
                                ->directory('settings')
                                ->visibility('public')
                                ->image(),
                            FileUpload::make('favicon_svg_path')
                                ->label(__('commero::admin.site_setting.favicon_svg_path'))
                                ->disk('public')
                                ->directory('settings/favicons')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/svg+xml'])
                                ->helperText(__('commero::admin.site_setting.favicon_svg_path_hint')),
                            FileUpload::make('favicon_png_path')
                                ->label(__('commero::admin.site_setting.favicon_png_path'))
                                ->disk('public')
                                ->directory('settings/favicons')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/png'])
                                ->image()
                                ->helperText(__('commero::admin.site_setting.favicon_png_path_hint')),
                        ])
                        ->columns(2),
                    Section::make(__('commero::admin.site_setting.delivery_section'))
                        ->schema([
                            TextInput::make('nova_poshta_api_key')
                                ->label(__('commero::admin.site_setting.nova_poshta_api_key'))
                                ->password()
                                ->revealable()
                                ->autocomplete('off')
                                ->helperText(__('commero::admin.site_setting.nova_poshta_api_key_hint'))
                                ->maxLength(255),
                        ]),
                    Section::make(__('commero::admin.site_setting.price_currency_section'))
                        ->schema([
                            Toggle::make('multi_currency_enabled')
                                ->label(__('commero::admin.site_setting.multi_currency_enabled'))
                                ->live(),
                            Select::make('country_source')
                                ->label(__('commero::admin.site_setting.country_source'))
                                ->options([
                                    'cookie' => __('commero::admin.site_setting.country_source_cookie'),
                                    'url' => __('commero::admin.site_setting.country_source_url'),
                                ])
                                ->visible(fn (callable $get): bool => (bool) $get('multi_currency_enabled')),
                            Toggle::make('show_price_decimals')
                                ->label(__('commero::admin.site_setting.show_price_decimals')),
                        ])
                        ->columns(2),
                    Section::make(__('commero::admin.site_setting.contacts'))
                        ->schema([
                            Repeater::make('contacts')
                                ->label(__('commero::admin.site_setting.contacts'))
                                ->schema([
                                    TextInput::make('identifier')
                                        ->label(__('commero::admin.common.identifier'))
                                        ->required()
                                        ->alphaDash()
                                        ->maxLength(50)
                                        ->placeholder('phone')
                                        ->helperText(__('commero::admin.site_setting.contact_identifier_hint')),
                                    FileUpload::make('icon')
                                        ->label(__('commero::admin.common.icon'))
                                        ->disk('public')
                                        ->directory('settings/contacts')
                                        ->visibility('public')
                                        ->image(),
                                    TextInput::make('label')
                                        ->label(__('commero::admin.common.label'))
                                        ->required(fn ($livewire): bool => data_get($livewire, 'activeLocale') === Locales::default()),
                                    TextInput::make('value')
                                        ->label(__('commero::admin.common.value'))
                                        ->required(fn ($livewire): bool => data_get($livewire, 'activeLocale') === Locales::default()),
                                ])
                                ->columns(4)
                                ->columnSpanFull(),
                        ]),
                    Section::make(__('commero::admin.site_setting.addresses'))
                        ->description(__('commero::admin.site_setting.addresses_hint'))
                        ->visible(fn (): bool => $this->activeLocale === Locales::default())
                        ->schema([
                            TextInput::make('google_maps_api_key')
                                ->label(__('commero::admin.site_setting.google_maps_api_key'))
                                ->password()
                                ->revealable()
                                ->autocomplete('off')
                                ->live()
                                ->helperText(__('commero::admin.site_setting.google_maps_api_key_hint'))
                                ->maxLength(2048)
                                ->columnSpanFull(),
                            Repeater::make('addresses')
                                ->label(__('commero::admin.site_setting.addresses'))
                                ->visible(fn (callable $get): bool => filled($get('google_maps_api_key')))
                                ->schema([
                                    TextInput::make('label')
                                        ->label(__('commero::admin.common.label'))
                                        ->maxLength(255),
                                    TextInput::make('location_search')
                                        ->label(__('commero::admin.site_setting.address_location_search'))
                                        ->placeholder(__('commero::admin.site_setting.address_location_search_placeholder'))
                                        ->helperText(__('commero::admin.site_setting.address_location_search_hint'))
                                        ->dehydrated(false)
                                        ->extraAttributes([
                                            'data-location-address-search' => 'true',
                                        ])
                                        ->columnSpanFull(),
                                    Textarea::make('address')
                                        ->label(__('commero::admin.site_setting.address_value'))
                                        ->rows(3)
                                        ->extraAttributes([
                                            'data-location-address' => 'true',
                                        ])
                                        ->columnSpanFull(),
                                    TextInput::make('coordinates')
                                        ->label(__('commero::admin.site_setting.address_coordinates'))
                                        ->placeholder('50.450100,30.523400')
                                        ->helperText(__('commero::admin.site_setting.address_coordinates_hint'))
                                        ->extraAttributes([
                                            'data-location-coordinates' => 'true',
                                        ])
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ]),
                    Section::make(__('commero::admin.site_setting.social_links'))
                        ->schema([
                            Repeater::make('social_links')
                                ->label(__('commero::admin.site_setting.social_links'))
                                ->schema([
                                    TextInput::make('identifier')
                                        ->label(__('commero::admin.common.identifier'))
                                        ->required()
                                        ->alphaDash()
                                        ->maxLength(50)
                                        ->placeholder('instagram')
                                        ->helperText(__('commero::admin.site_setting.social_identifier_hint')),
                                    FileUpload::make('icon')
                                        ->label(__('commero::admin.common.icon'))
                                        ->disk('public')
                                        ->directory('settings/social-links')
                                        ->visibility('public')
                                        ->image(),
                                    TextInput::make('label')
                                        ->label(__('commero::admin.common.label'))
                                        ->required(fn ($livewire): bool => data_get($livewire, 'activeLocale') === Locales::default()),
                                    TextInput::make('url')
                                        ->label(__('commero::admin.common.url'))
                                        ->rule(new FlexibleUrl)
                                        ->validationAttribute(__('commero::admin.common.url'))
                                        ->validationMessages([
                                            'required' => __('commero::validation.required', ['attribute' => __('commero::admin.common.url')]),
                                        ])
                                        ->required(fn ($livewire): bool => data_get($livewire, 'activeLocale') === Locales::default()),
                                ])
                                ->columns(4)
                                ->columnSpanFull(),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label(__('commero::admin.common.save'))
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ])
            ->record($this->getRecord())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $activeLocale = $this->resolveActiveLocale($data[static::ACTIVE_LOCALE_FIELD] ?? null);

        $this->ensureUniqueIdentifiers($data['contacts'] ?? [], 'contacts');
        $this->ensureUniqueIdentifiers($data['social_links'] ?? [], 'social_links');

        $record = $this->getRecord() ?? new SiteSetting;
        $record->fill($this->prepareRecordData($record, $data, $activeLocale));
        $record->save();

        if ($record->wasRecentlyCreated) {
            $this->form->record($record)->saveRelationships();
        }

        $this->activeLocale = $activeLocale;
        $this->form->fill($this->getFormState($record));

        Notification::make()
            ->success()
            ->title(__('commero::admin.site_setting.saved'))
            ->send();
    }

    protected function getRecord(): ?SiteSetting
    {
        return SiteSetting::query()->first();
    }

    public function updatedActiveLocale(?string $locale): void
    {
        $this->activeLocale = in_array($locale, AdminLocales::supported(), true)
            ? (string) $locale
            : Locales::default();

        data_set($this, 'data.'.static::ACTIVE_LOCALE_FIELD, $this->activeLocale);
    }

    protected function ensureUniqueIdentifiers(array $items, string $key): void
    {
        $duplicates = collect($items)
            ->pluck('identifier')
            ->filter()
            ->map(fn (string $identifier): string => mb_strtolower(trim($identifier)))
            ->duplicates()
            ->unique()
            ->values();

        if ($duplicates->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            "data.{$key}" => __('commero::admin.site_setting.identifier_unique', [
                'identifiers' => $duplicates->implode(', '),
            ]),
        ]);
    }

    protected function getFormState(?SiteSetting $record = null): array
    {
        $record ??= $this->getRecord();
        $activeLocale = $this->resolveActiveLocale();

        return [
            static::ACTIVE_LOCALE_FIELD => $activeLocale,
            'site_name' => $record?->getSiteNameForLocale($activeLocale, false),
            'logo_path' => $record?->getLogoPathForLocale($activeLocale, false),
            'footer_logo_path' => $record?->getFooterLogoPathForLocale($activeLocale, false),
            'favicon_svg_path' => $record?->getRawOriginal('favicon_svg_path'),
            'favicon_png_path' => $record?->getRawOriginal('favicon_png_path'),
            'nova_poshta_api_key' => $record?->getAttribute('nova_poshta_api_key'),
            'google_maps_api_key' => $record?->getAttribute('google_maps_api_key'),
            'contacts' => $record?->getEditableContactsForLocale($activeLocale) ?? [],
            'addresses' => $record?->getEditableAddresses() ?? [],
            'social_links' => $record?->getEditableSocialLinksForLocale($activeLocale) ?? [],
            'multi_currency_enabled' => (bool) $record?->getRawOriginal('multi_currency_enabled'),
            'country_source' => $record?->country_source,
            'show_price_decimals' => (bool) $record?->getRawOriginal('show_price_decimals'),
        ];
    }

    protected function prepareRecordData(SiteSetting $record, array $data, string $activeLocale): array
    {
        $preparedData = [
            'favicon_svg_path' => $this->normalizeTextValue($data['favicon_svg_path'] ?? null),
            'favicon_png_path' => $this->normalizeTextValue($data['favicon_png_path'] ?? null),
            'nova_poshta_api_key' => $data['nova_poshta_api_key'] ?? null,
            'google_maps_api_key' => $data['google_maps_api_key'] ?? null,
            'multi_currency_enabled' => (bool) ($data['multi_currency_enabled'] ?? false),
            'country_source' => $data['multi_currency_enabled'] ? ($data['country_source'] ?? null) : null,
            'show_price_decimals' => (bool) ($data['show_price_decimals'] ?? false),
        ];

        if (Locales::isDefault($activeLocale)) {
            $preparedData['site_name'] = $this->normalizeTextValue($data['site_name'] ?? null);
            $preparedData['logo_path'] = $this->normalizeTextValue($data['logo_path'] ?? null);
            $preparedData['footer_logo_path'] = $this->normalizeTextValue($data['footer_logo_path'] ?? null);
            $preparedData['contacts'] = $this->normalizeLocalizedItems($data['contacts'] ?? [], ['label', 'value']);
            $preparedData['addresses'] = $this->normalizeAddresses($data['addresses'] ?? []);
            $preparedData['social_links'] = $this->normalizeLocalizedItems($data['social_links'] ?? [], ['label', 'url']);

            return $preparedData;
        }

        $siteNameTranslations = $record->site_name_translations ?? [];
        $logoPathTranslations = $record->logo_path_translations ?? [];
        $footerLogoPathTranslations = $record->footer_logo_path_translations ?? [];
        $contactsTranslations = $record->contacts_translations ?? [];
        $socialLinksTranslations = $record->social_links_translations ?? [];

        $siteNameTranslations[$activeLocale] = $this->normalizeTextValue($data['site_name'] ?? null);
        $logoPathTranslations[$activeLocale] = $this->normalizeTextValue($data['logo_path'] ?? null);
        $footerLogoPathTranslations[$activeLocale] = $this->normalizeTextValue($data['footer_logo_path'] ?? null);
        $contactsTranslations[$activeLocale] = $this->normalizeLocalizedItems($data['contacts'] ?? [], ['label', 'value']);
        $socialLinksTranslations[$activeLocale] = $this->normalizeLocalizedItems($data['social_links'] ?? [], ['label', 'url']);

        $preparedData['site_name_translations'] = $this->filterEmptyTranslationValues($siteNameTranslations);
        $preparedData['logo_path_translations'] = $this->filterEmptyTranslationValues($logoPathTranslations);
        $preparedData['footer_logo_path_translations'] = $this->filterEmptyTranslationValues($footerLogoPathTranslations);
        $preparedData['contacts_translations'] = $this->filterEmptyTranslationItemLists($contactsTranslations, ['label', 'value']);
        $preparedData['social_links_translations'] = $this->filterEmptyTranslationItemLists($socialLinksTranslations, ['label', 'url']);

        return $preparedData;
    }

    protected function filterEmptyTranslationValues(array $translations): array
    {
        return collect($translations)
            ->map(fn (mixed $value): ?string => $this->normalizeTextValue($value))
            ->filter(fn (mixed $value): bool => filled($value))
            ->all();
    }

    protected function filterEmptyTranslationItemLists(array $translations, array $translatableFields): array
    {
        return collect($translations)
            ->map(function (mixed $items) use ($translatableFields): array {
                return collect($this->normalizeLocalizedItems((array) $items, $translatableFields))
                    ->filter(function (array $item) use ($translatableFields): bool {
                        foreach ($translatableFields as $field) {
                            if (filled($item[$field] ?? null)) {
                                return true;
                            }
                        }

                        return false;
                    })
                    ->values()
                    ->all();
            })
            ->filter(fn (array $items): bool => $items !== [])
            ->all();
    }

    protected function normalizeLocalizedItems(array $items, array $translatableFields): array
    {
        return collect($items)
            ->map(function (mixed $item) use ($translatableFields): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $identifier = $this->normalizeTextValue($item['identifier'] ?? null);

                if (! filled($identifier)) {
                    return null;
                }

                $normalized = [
                    'identifier' => $identifier,
                    'icon' => $this->normalizeTextValue($item['icon'] ?? null),
                ];

                foreach ($translatableFields as $field) {
                    $normalized[$field] = $this->normalizeTextValue($item[$field] ?? null);
                }

                return $normalized;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeAddresses(array $items): array
    {
        return collect($items)
            ->map(function (mixed $item): ?array {
                if (! is_array($item)) {
                    return null;
                }

                $label = $this->normalizeTextValue($item['label'] ?? null);
                $address = $this->normalizeTextValue($item['address'] ?? null);
                $coordinates = $this->normalizeTextValue($item['coordinates'] ?? null);

                if (! filled($label) && ! filled($address) && ! filled($coordinates)) {
                    return null;
                }

                return [
                    'label' => $label,
                    'address' => $address,
                    'coordinates' => $coordinates,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function resolveActiveLocale(?string $locale = null): string
    {
        $locale = $locale
            ?? data_get($this, 'data.'.static::ACTIVE_LOCALE_FIELD)
            ?? request()->query('lang')
            ?? $this->activeLocale;

        if (! in_array($locale, AdminLocales::supported(), true)) {
            $locale = Locales::default();
        }

        return $locale;
    }

    protected function normalizeTextValue(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return filled($value) ? $value : null;
    }
}
