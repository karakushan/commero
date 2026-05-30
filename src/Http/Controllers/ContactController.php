<?php

namespace Commero\Http\Controllers;

use Commero\Models\Page;
use Commero\Models\SiteSetting;
use Commero\Support\Locales;
use Commero\Support\Seo\LocalizedSeoResolver;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(LocalizedSeoResolver $seoResolver): View
    {
        $locale = app()->getLocale();
        $page = Page::query()
            ->published()
            ->withTranslationsFor($locale)
            ->whereHas('translations', fn ($query) => $query->where('slug', 'contacts'))
            ->first();
        $pageTranslation = $page?->translation($locale);
        $siteSetting = SiteSetting::query()->first();
        $contacts = collect($siteSetting?->contacts ?? []);

        $findContact = function (array $identifiers, array $labels = []) use ($contacts): ?array {
            $normalizedIdentifiers = collect($identifiers)
                ->map(fn (string $identifier): string => mb_strtolower($identifier))
                ->all();
            $normalizedLabels = collect($labels)
                ->map(fn (string $label): string => mb_strtolower($label))
                ->all();

            return $contacts->first(function (array $contact) use ($normalizedIdentifiers, $normalizedLabels): bool {
                $identifier = mb_strtolower(trim((string) ($contact['identifier'] ?? '')));
                $label = mb_strtolower(trim((string) ($contact['label'] ?? '')));

                return in_array($identifier, $normalizedIdentifiers, true)
                    || in_array($label, $normalizedLabels, true);
            });
        };

        $phoneContact = $findContact(['phone', 'telephone'], ['телефон', 'phone', 'telephone']);
        $addressContact = $findContact(['address', 'location'], ['адреса', 'address', 'location']);
        $workingHoursContact = $findContact(['working_hours', 'working-hours', 'hours'], ['графік', 'години', 'working hours']);
        $emailContact = $findContact(['email', 'mail'], ['email', 'e-mail', 'пошта']);

        $phone = $phoneContact['value'] ?? '+38 098 607 43 03';
        $address = $addressContact['value'] ?? __('Kyiv, 19A Lesia Kurbasa Avenue');
        $email = $emailContact['value'] ?? 'shophats.info@gmail.com';
        $workingHours = $workingHoursContact['value'] ?? __('Mon - Fri 10:00 - 17:00, Sat 10:00 - 13:00, Sun - day off');
        $mapQuery = Str::of($address)
            ->replace(["\r\n", "\n", "\r"], ' ')
            ->squish()
            ->value();
        $googleMapsEmbedUrl = 'https://maps.google.com/maps?hl='
            .urlencode($locale)
            .'&q='
            .urlencode($mapQuery)
            .'&t=&z=17&ie=UTF8&iwloc=B&output=embed';

        return view('shophats::pages.contacts', [
            'locale' => $locale,
            'seo' => $seoResolver->forCurrentRoute(
                request: request(),
                locale: $locale,
                title: $pageTranslation?->meta_title ?: (filled($pageTranslation?->title) ? $pageTranslation->title : __('Contacts')),
                heading: filled($pageTranslation?->title) ? $pageTranslation->title : __('Contacts'),
                description: $pageTranslation?->meta_description,
                robots: $pageTranslation?->robots,
            ),
            'homeUrl' => Locales::isDefault($locale)
                ? route('home')
                : route('localized.home', ['locale' => $locale]),
            'page' => $page,
            'pageTranslation' => $pageTranslation,
            'pageTitle' => filled($pageTranslation?->title) ? $pageTranslation->title : __('Contacts'),
            'pageBlocks' => $pageTranslation?->blocks ?? $this->defaultContactBlocks(),
            'phone' => $phone,
            'phoneHref' => preg_replace('/[^0-9+]/', '', $phone) ?: null,
            'address' => $address,
            'email' => $email,
            'workingHours' => $workingHours,
            'workingHoursHtml' => nl2br(e(str_replace(', ', PHP_EOL, $workingHours))),
            'googleMapsEmbedUrl' => $googleMapsEmbedUrl,
        ]);
    }

    private function defaultContactBlocks(): array
    {
        return [
            [
                'type' => 'contact_cards',
                'data' => [],
            ],
            [
                'type' => 'contacts_map',
                'data' => [],
            ],
            [
                'type' => 'contacts_feedback_form',
                'data' => [
                    'title' => __('Feedback'),
                    'description' => __('Write to us if you have questions about the website or service in the store.'),
                ],
            ],
            [
                'type' => 'contacts_faq',
                'data' => [
                    'title' => __('Popular questions and answers'),
                    'button_label' => __('FAQ section'),
                    'items' => [
                        [
                            'question' => __('How long does delivery take?'),
                            'answer' => __('Delivery across Ukraine takes 1-3 days. We ship orders on the day of payment or the next day. You will receive a tracking number to track your parcel.'),
                        ],
                        [
                            'question' => __('What payment methods are available?'),
                            'answer' => __('We accept payment by Visa/Mastercard bank cards through PrivatBank and Monobank, as well as payment upon receipt (cash on delivery).'),
                        ],
                        [
                            'question' => __('Can I return a product?'),
                            'answer' => __('Yes, you can return a product within 14 days from the date of receipt. The product must be in its original condition with tags and packaging. To process a return, contact our support service.'),
                        ],
                        [
                            'question' => __('How to find out my size?'),
                            'answer' => __('Each product page has a size chart. Measure your head circumference with a measuring tape and compare it with our chart. If in doubt, contact our support service for help choosing a size.'),
                        ],
                        [
                            'question' => __('Are there discounts for wholesale orders?'),
                            'answer' => __('Yes, we offer special terms for wholesale orders from 10 units. Contact us through the feedback form or call to discuss individual discounts and cooperation terms.'),
                        ],
                    ],
                ],
            ],
        ];
    }
}
