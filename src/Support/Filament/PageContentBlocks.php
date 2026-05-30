<?php

namespace Commero\Support\Filament;

use Commero\Models\Category;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class PageContentBlocks
{
    /**
     * @return array<int, Block>
     */
    public static function forPages(): array
    {
        return [
            ...static::homeBlocks(),
            ...static::deliveryBlocks(),
            ...static::returnBlocks(),
            ...static::aboutBlocks(),
            Block::make('rich_text_content')
                ->label(static::labelWithIdentifier('rich_text_content', __('admin.content.blocks.rich_text_content.label')))
                ->icon('heroicon-o-document-text')
                ->schema([
                    RichEditor::make('content')
                        ->label(__('admin.content.blocks.rich_text_content.fields.content'))
                        ->required()
                        ->columnSpanFull()
                        ->extraAttributes(['style' => 'min-height: 24rem;']),
                    ...static::spacingFields('rich_text_content', mobileTop: '24', mobileBottom: '0', desktopTop: '24', desktopBottom: '0'),
                ])
                ->columns(2),
            Block::make('contact_cards')
                ->label(static::labelWithIdentifier('contact_cards', __('admin.content.blocks.contact_cards.label')))
                ->icon('heroicon-o-identification')
                ->maxItems(1)
                ->schema([]),
            Block::make('contacts_map')
                ->label(static::labelWithIdentifier('contacts_map', __('admin.content.blocks.contacts_map.label')))
                ->icon('heroicon-o-map')
                ->maxItems(1)
                ->schema([]),
            Block::make('contacts_feedback_form')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('contacts_feedback_form', __('admin.content.blocks.contacts_feedback_form.label'), $state);
                })
                ->icon('heroicon-o-chat-bubble-left-right')
                ->maxItems(1)
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.contacts_feedback_form.fields.title'))
                        ->required()
                        ->default('Feedback')
                        ->live(onBlur: true),
                    Textarea::make('description')
                        ->label(__('admin.content.blocks.contacts_feedback_form.fields.description'))
                        ->rows(3)
                        ->default('Write to us if you have questions about the website or service in the store.')
                        ->columnSpanFull(),
                ])
                ->columns(1),
            Block::make('contacts_faq')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('contacts_faq', __('admin.content.blocks.contacts_faq.label'), $state);
                })
                ->icon('heroicon-o-question-mark-circle')
                ->maxItems(1)
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.contacts_faq.fields.title'))
                        ->required()
                        ->default('Popular questions and answers')
                        ->live(onBlur: true),
                    TextInput::make('button_label')
                        ->label(__('admin.content.blocks.contacts_faq.fields.button_label'))
                        ->required()
                        ->default('FAQ section'),
                    TextInput::make('button_link')
                        ->label(__('admin.content.blocks.contacts_faq.fields.button_link'))
                        ->placeholder('/faq'),
                    Repeater::make('items')
                        ->label(__('admin.content.blocks.contacts_faq.fields.items'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            TextInput::make('question')
                                ->label(__('admin.content.blocks.contacts_faq.fields.question'))
                                ->required(),
                            Textarea::make('answer')
                                ->label(__('admin.content.blocks.contacts_faq.fields.answer'))
                                ->rows(4)
                                ->required(),
                        ])
                        ->columns(1)
                        ->columnSpanFull(),
                    ...static::spacingFields('contacts_faq', mobileTop: '70', mobileBottom: '70', desktopTop: '70', desktopBottom: '70'),
                ])
                ->columns(2),
            Block::make('price_table')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('price_table', __('admin.content.blocks.price_table.label'), $state);
                })
                ->icon('heroicon-o-table-cells')
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.price_table.fields.title'))
                        ->required()
                        ->default('Prices for children headwear')
                        ->live(onBlur: true),
                    TextInput::make('productHeading')
                        ->label(__('admin.content.blocks.price_table.fields.product_heading'))
                        ->required()
                        ->default('Product'),
                    TextInput::make('priceHeading')
                        ->label(__('admin.content.blocks.price_table.fields.price_heading'))
                        ->required()
                        ->default('Price'),
                    Repeater::make('items')
                        ->label(__('admin.content.blocks.price_table.fields.items'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            TextInput::make('product')
                                ->label(__('admin.content.blocks.price_table.fields.product'))
                                ->required(),
                            TextInput::make('price')
                                ->label(__('admin.content.blocks.price_table.fields.price'))
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    ...static::spacingFields('price_table', mobileTop: '24', desktopTop: '24'),
                ])
                ->columns(2),
        ];
    }

    /**
     * @return array<int, Block>
     */
    protected static function homeBlocks(): array
    {
        return [
            Block::make('home_top_section')
                ->label(static::labelWithIdentifier('home_top_section', __('admin.content.blocks.home_top_section.label')))
                ->icon('heroicon-o-home')
                ->maxItems(1)
                ->schema([
                    Repeater::make('slides')
                        ->label(__('admin.content.blocks.home_top_section.fields.slides'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            FileUpload::make('desktop_image')
                                ->label(__('admin.content.blocks.home_top_section.fields.desktop_image'))
                                ->disk('public')
                                ->directory('pages/home/top-slides')
                                ->visibility('public')
                                ->image()
                                ->required(),
                            FileUpload::make('mobile_image')
                                ->label(__('admin.content.blocks.home_top_section.fields.mobile_image'))
                                ->disk('public')
                                ->directory('pages/home/top-slides')
                                ->visibility('public')
                                ->image(),
                            TextInput::make('button_label')
                                ->label(__('admin.content.blocks.home_top_section.fields.button_label')),
                            TextInput::make('button_link')
                                ->label(__('admin.content.blocks.home_top_section.fields.button_link'))
                                ->placeholder('/catalog'),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Repeater::make('gender_cards')
                        ->label(__('admin.content.blocks.home_top_section.fields.gender_cards'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            TextInput::make('title')
                                ->label(__('admin.content.blocks.home_top_section.fields.card_title'))
                                ->required(),
                            Select::make('icon')
                                ->label(__('admin.content.blocks.home_top_section.fields.card_icon'))
                                ->options(static::homeShortcutIcons())
                                ->default('men')
                                ->required(),
                            ColorPicker::make('color')
                                ->label(__('admin.content.blocks.home_top_section.fields.card_color'))
                                ->default('#26ADB8'),
                            TextInput::make('url')
                                ->label(__('admin.content.blocks.home_top_section.fields.card_url'))
                                ->placeholder('/catalog')
                                ->required(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Select::make('excluded_sidebar_category_ids')
                        ->label(__('admin.content.blocks.home_top_section.fields.excluded_sidebar_categories'))
                        ->helperText(__('admin.content.blocks.home_top_section.fields.excluded_sidebar_categories_hint'))
                        ->options(fn (): array => static::categoryOptions())
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->default([])
                        ->columnSpanFull(),
                    ...static::spacingFields('home_top_section', mobileTop: '0', desktopTop: '0'),
                ])
                ->columns(2),
            Block::make('home_about_advantages')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('home_about_advantages', __('admin.content.blocks.home_about_advantages.label'), $state);
                })
                ->icon('heroicon-o-sparkles')
                ->maxItems(1)
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.home_about_advantages.fields.title'))
                        ->required()
                        ->default('The #1 headwear online store in Ukraine')
                        ->live(onBlur: true),
                    Repeater::make('items')
                        ->label(__('admin.content.blocks.home_about_advantages.fields.items'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            TextInput::make('title')
                                ->label(__('admin.content.blocks.home_about_advantages.fields.item_title'))
                                ->required(),
                            Textarea::make('description')
                                ->label(__('admin.content.blocks.home_about_advantages.fields.item_description'))
                                ->rows(3)
                                ->required(),
                            TextInput::make('icon')
                                ->label(__('admin.content.blocks.home_about_advantages.fields.icon'))
                                ->required(),
                        ])
                        ->columns(1)
                        ->columnSpanFull(),
                    ...static::spacingFields('home_about_advantages', mobileTop: '24', desktopTop: '48'),
                ])
                ->columns(2),
            Block::make('home_product_slider')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('home_product_slider', __('admin.content.blocks.home_product_slider.label'), $state);
                })
                ->icon('heroicon-o-squares-plus')
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.home_product_slider.fields.title'))
                        ->required()
                        ->live(onBlur: true),
                    Textarea::make('description')
                        ->label(__('admin.content.blocks.home_product_slider.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('button_label')
                        ->label(__('admin.content.blocks.home_product_slider.fields.button_label')),
                    Select::make('source_type')
                        ->label(__('admin.content.blocks.home_product_slider.fields.source_type'))
                        ->options([
                            'category' => __('admin.content.blocks.home_product_slider.sources.category'),
                            'popular' => __('admin.content.blocks.home_product_slider.sources.popular'),
                            'sale' => __('admin.content.blocks.home_product_slider.sources.sale'),
                            'hit' => __('admin.content.blocks.home_product_slider.sources.hit'),
                            'new' => __('admin.content.blocks.home_product_slider.sources.new'),
                        ])
                        ->default('category')
                        ->afterStateHydrated(function (Select $component, mixed $state): void {
                            $component->state(filled($state) ? $state : 'category');
                        })
                        ->live()
                        ->required(),
                    Select::make('source_category_id')
                        ->label(__('admin.content.blocks.home_product_slider.fields.source_category'))
                        ->options(fn (): array => static::categoryOptions())
                        ->searchable()
                        ->preload()
                        ->visible(fn (callable $get): bool => ($get('source_type') ?? 'category') === 'category')
                        ->required(fn (callable $get): bool => ($get('source_type') ?? 'category') === 'category'),
                    TextInput::make('products_limit')
                        ->label(__('admin.content.blocks.home_product_slider.fields.products_limit'))
                        ->numeric()
                        ->default(8)
                        ->required(),
                    Select::make('theme')
                        ->label(__('admin.content.blocks.home_product_slider.fields.theme'))
                        ->options([
                            'dark' => __('admin.content.blocks.home_product_slider.themes.dark'),
                            'light' => __('admin.content.blocks.home_product_slider.themes.light'),
                            'plain' => __('admin.content.blocks.home_product_slider.themes.plain'),
                        ])
                        ->default('light')
                        ->required(),
                    ColorPicker::make('background_color')
                        ->label(__('admin.content.blocks.home_product_slider.fields.background_color'))
                        ->default('#F9F5F7'),
                    FileUpload::make('desktop_image')
                        ->label(__('admin.content.blocks.home_product_slider.fields.desktop_image'))
                        ->disk('public')
                        ->directory('pages/home/product-slider')
                        ->visibility('public')
                        ->image(),
                    FileUpload::make('mobile_image')
                        ->label(__('admin.content.blocks.home_product_slider.fields.mobile_image'))
                        ->disk('public')
                        ->directory('pages/home/product-slider')
                        ->visibility('public')
                        ->image(),
                    ...static::spacingFields('home_product_slider', mobileTop: '24', desktopTop: '48'),
                ])
                ->columns(2),
            Block::make('home_filter_links')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('home_filter_links', __('admin.content.blocks.home_filter_links.label'), $state);
                })
                ->icon('heroicon-o-funnel')
                ->maxItems(1)
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.home_filter_links.fields.title'))
                        ->required()
                        ->default('Choose what suits you best')
                        ->live(onBlur: true),
                    Textarea::make('description')
                        ->label(__('admin.content.blocks.home_filter_links.fields.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                    Repeater::make('groups')
                        ->label(__('admin.content.blocks.home_filter_links.fields.groups'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            TextInput::make('title')
                                ->label(__('admin.content.blocks.home_filter_links.fields.group_title'))
                                ->required(),
                            TextInput::make('icon')
                                ->label(__('admin.content.blocks.home_filter_links.fields.group_icon'))
                                ->default('season')
                                ->required(),
                            Repeater::make('links')
                                ->label(__('admin.content.blocks.home_filter_links.fields.links'))
                                ->defaultItems(0)
                                ->reorderableWithButtons()
                                ->schema([
                                    TextInput::make('label')
                                        ->label(__('admin.content.blocks.home_filter_links.fields.link_label'))
                                        ->required(),
                                    TextInput::make('url')
                                        ->label(__('admin.content.blocks.home_filter_links.fields.link_url'))
                                        ->required(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    ...static::spacingFields('home_filter_links', mobileTop: '24', desktopTop: '48'),
                ])
                ->columns(2),
            Block::make('home_seo_text')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('home_seo_text', __('admin.content.blocks.home_seo_text.label'), $state);
                })
                ->icon('heroicon-o-document-text')
                ->maxItems(1)
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.home_seo_text.fields.title'))
                        ->required()
                        ->live(onBlur: true),
                    RichEditor::make('content')
                        ->label(__('admin.content.blocks.home_seo_text.fields.content'))
                        ->required()
                        ->columnSpanFull()
                        ->extraAttributes(['style' => 'min-height: 24rem;']),
                    TextInput::make('read_more_label')
                        ->label(__('admin.content.blocks.home_seo_text.fields.read_more_label'))
                        ->default('Read more'),
                    TextInput::make('collapse_label')
                        ->label(__('admin.content.blocks.home_seo_text.fields.collapse_label'))
                        ->default('Collapse'),
                    TextInput::make('collapsed_height')
                        ->label(__('admin.content.blocks.home_seo_text.fields.collapsed_height'))
                        ->numeric()
                        ->default(240),
                    Toggle::make('show_advantages')
                        ->label(__('admin.content.blocks.home_seo_text.fields.show_advantages'))
                        ->default(true)
                        ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                            $component->state($state ?? true);
                        }),
                    ...static::spacingFields('home_seo_text', mobileTop: '24', desktopTop: '70'),
                ])
                ->columns(2),
        ];
    }

    /**
     * @return array<int, Block>
     */
    protected static function aboutBlocks(): array
    {
        return [
            Block::make('about_banner')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('about_banner', __('admin.content.blocks.about_banner.label'), $state);
                })
                ->icon('heroicon-o-photo')
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.about_banner.fields.title'))
                        ->required()
                        ->default('про компанію')
                        ->live(onBlur: true),
                    FileUpload::make('bannerImageUrl')
                        ->label(__('admin.content.blocks.about_banner.fields.banner_image'))
                        ->disk('public')
                        ->directory('pages/about-banner')
                        ->visibility('public')
                        ->image()
                        ->columnSpan(1),
                    TextInput::make('bannerUrl')
                        ->label(__('admin.content.blocks.about_banner.fields.banner_url'))
                        ->url()
                        ->columnSpan(1),
                    TextInput::make('height')
                        ->label(__('admin.content.blocks.about_banner.fields.height'))
                        ->numeric()
                        ->default('400'),
                    TextInput::make('heightLg')
                        ->label(__('admin.content.blocks.about_banner.fields.height_lg'))
                        ->numeric()
                        ->default('570'),
                    ...static::spacingFields('about_banner', mobileTop: '24'),
                ])
                ->columns(2)
                ->maxItems(1),
            Block::make('about_content')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('about_content', __('admin.content.blocks.about_content.label'), $state);
                })
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.about_content.fields.title'))
                        ->required()
                        ->default('SHOPHATS')
                        ->live(onBlur: true),
                    Textarea::make('description')
                        ->label(__('admin.content.blocks.about_content.fields.description'))
                        ->rows(5)
                        ->required()
                        ->default('Ми зібрали великий асортимент моделей для чоловіків, жінок і дітей, щоб кожен міг знайти варіант під свій стиль і потреби. Працюємо лише з перевіреними виробниками та приділяємо увагу якості кожного товару. Замовлення швидко обробляємо та доставляємо по всій Україні. Наша мета — зробити вибір головних уборів простим, зручним і приємним для кожного клієнта.')
                        ->columnSpanFull(),
                    FileUpload::make('logoImageUrl')
                        ->label(__('admin.content.blocks.about_content.fields.logo_image'))
                        ->disk('public')
                        ->directory('pages/about-content')
                        ->visibility('public')
                        ->image()
                        ->columnSpan(1),
                    TextInput::make('logoUrl')
                        ->label(__('admin.content.blocks.about_content.fields.logo_url'))
                        ->url()
                        ->columnSpan(1),
                    TextInput::make('catalogLink')
                        ->label(__('admin.content.blocks.about_content.fields.catalog_link'))
                        ->placeholder('/catalog')
                        ->columnSpanFull(),
                    ...static::spacingFields('about_content', mobileTop: '0', desktopTop: '48'),
                ])
                ->columns(2)
                ->maxItems(1),
            Block::make('about_advantages')
                ->label(static::labelWithIdentifier('about_advantages', __('admin.content.blocks.about_advantages.label')))
                ->icon('heroicon-o-squares-2x2')
                ->schema([
                    TextInput::make('introTitle1')
                        ->label(__('admin.content.blocks.about_advantages.fields.intro_title_1'))
                        ->required()
                        ->default('ShopHats — інтернет-магазин головних'),
                    TextInput::make('introTitle2')
                        ->label(__('admin.content.blocks.about_advantages.fields.intro_title_2'))
                        ->required()
                        ->default('уборів для всієї родини.'),
                    Textarea::make('introDescription')
                        ->label(__('admin.content.blocks.about_advantages.fields.intro_description'))
                        ->rows(3)
                        ->required()
                        ->default('Зручний каталог, чесні ціни та доставка по всій Україні роблять покупку простою і комфортною.')
                        ->columnSpanFull(),
                    Repeater::make('items')
                        ->label(__('admin.content.blocks.about_advantages.fields.items'))
                        ->default([
                            [
                                'title' => 'Широкий вибір',
                                'description' => 'Моделі для будь-якого стилю, віку та потреб.',
                                'icon' => 'about-adv1.svg',
                            ],
                            [
                                'title' => 'Перевірена якість',
                                'description' => 'Працюємо з надійними брендами та виробниками.',
                                'icon' => 'about-adv2.svg',
                            ],
                            [
                                'title' => 'Швидка доставка',
                                'description' => 'Відправляємо замовлення по всій Україні без затримок.',
                                'icon' => 'about-adv3.svg',
                            ],
                            [
                                'title' => 'Зручний сервіс',
                                'description' => 'Просте оформлення, різні способи оплати та легке повернення.',
                                'icon' => 'about-adv4.svg',
                            ],
                        ])
                        ->defaultItems(4)
                        ->minItems(1)
                        ->reorderableWithButtons()
                        ->schema([
                            TextInput::make('title')
                                ->label(__('admin.content.blocks.about_advantages.fields.item_title'))
                                ->required(),
                            Textarea::make('description')
                                ->label(__('admin.content.blocks.about_advantages.fields.item_description'))
                                ->rows(3)
                                ->required(),
                            TextInput::make('icon')
                                ->label(__('admin.content.blocks.about_advantages.fields.icon'))
                                ->default('about-adv1.svg'),
                        ])
                        ->columns(1)
                        ->columnSpanFull(),
                    ...static::spacingFields('about_advantages', mobileTop: '48', desktopTop: '48'),
                ])
                ->columns(2)
                ->maxItems(1),
            Block::make('delivery_map')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('delivery_map', __('admin.content.blocks.delivery_map.label'), $state);
                })
                ->icon('heroicon-o-map')
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.delivery_map.fields.title'))
                        ->required()
                        ->default('Твій стиль у будь-якому')
                        ->live(onBlur: true),
                    TextInput::make('titleLine2')
                        ->label(__('admin.content.blocks.delivery_map.fields.title_line_2'))
                        ->required()
                        ->default('місті України'),
                    Textarea::make('description')
                        ->label(__('admin.content.blocks.delivery_map.fields.description'))
                        ->rows(3)
                        ->required()
                        ->default('Доставляємо замовлення у міста та населені пункти по всій країні - швидко, зручно та надійно.')
                        ->columnSpanFull(),
                    Textarea::make('cities')
                        ->label(__('admin.content.blocks.delivery_map.fields.cities'))
                        ->rows(4)
                        ->required()
                        ->default('Івано-Франківськ, Чернігів, Біла церква, Суми, Житомир, Черкаси, Ужгород, Запоріжжя, Павлоград, Вінниця, Херсон, Харків, Кривий ріг, Слов\'янськ, Краматорськ, Кременчук, Кропивницький, Тернопіль, Хмельницький, Чернівці, Миколаїв, Рівне, Кам\'янське, Одеса, Дніпро, Львів')
                        ->columnSpanFull(),
                    FileUpload::make('mapImageUrl')
                        ->label(__('admin.content.blocks.delivery_map.fields.map_image'))
                        ->disk('public')
                        ->directory('pages/delivery-map')
                        ->visibility('public')
                        ->image()
                        ->columnSpan(1),
                    ColorPicker::make('backgroundColor')
                        ->label(__('admin.content.blocks.delivery_map.fields.background_color'))
                        ->default('#F5F5F5')
                        ->columnSpan(1),
                    ...static::spacingFields('delivery_map', mobileTop: '0', desktopTop: '0'),
                ])
                ->columns(2)
                ->maxItems(1),
        ];
    }

    /**
     * @return array<int, Block>
     */
    protected static function deliveryBlocks(): array
    {
        return [
            Block::make('delivery_hero')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('delivery_hero', __('admin.content.blocks.delivery_hero.label'), $state);
                })
                ->icon('heroicon-o-photo')
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.delivery_hero.fields.title'))
                        ->required()
                        ->default('Delivery and payment')
                        ->live(onBlur: true),
                    TextInput::make('subtitle')
                        ->label(__('admin.content.blocks.delivery_hero.fields.subtitle'))
                        ->required()
                        ->default('Order easily, pay conveniently, receive quickly.')
                        ->columnSpanFull(),
                    FileUpload::make('desktopImageUrl')
                        ->label(__('admin.content.blocks.delivery_hero.fields.desktop_image'))
                        ->disk('public')
                        ->directory('pages/delivery-hero')
                        ->visibility('public')
                        ->image()
                        ->columnSpan(1),
                    FileUpload::make('mobileImageUrl')
                        ->label(__('admin.content.blocks.delivery_hero.fields.mobile_image'))
                        ->disk('public')
                        ->directory('pages/delivery-hero')
                        ->visibility('public')
                        ->image()
                        ->columnSpan(1),
                    ...static::spacingFields('delivery_hero', mobileTop: '24', desktopTop: '24'),
                ])
                ->columns(2)
                ->maxItems(1),
            Block::make('delivery_info_section')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('delivery_info_section', __('admin.content.blocks.delivery_info_section.label'), $state);
                })
                ->icon('heroicon-o-queue-list')
                ->schema([
                    Select::make('variant')
                        ->label(__('admin.content.blocks.delivery_info_section.fields.variant'))
                        ->options([
                            'delivery' => __('admin.content.blocks.delivery_info_section.variants.delivery'),
                            'payment' => __('admin.content.blocks.delivery_info_section.variants.payment'),
                        ])
                        ->default('delivery')
                        ->required(),
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.delivery_info_section.fields.title'))
                        ->required()
                        ->default('Delivery')
                        ->live(onBlur: true),
                    Textarea::make('description')
                        ->label(__('admin.content.blocks.delivery_info_section.fields.description'))
                        ->rows(4)
                        ->required()
                        ->columnSpanFull(),
                    Repeater::make('items')
                        ->label(__('admin.content.blocks.delivery_info_section.fields.items'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            Select::make('icon')
                                ->label(__('admin.content.blocks.delivery_info_section.fields.icon'))
                                ->options(static::deliveryInfoIcons())
                                ->default('branch')
                                ->required(),
                            Textarea::make('text')
                                ->label(__('admin.content.blocks.delivery_info_section.fields.text'))
                                ->rows(4)
                                ->required()
                                ->columnSpanFull(),
                            FileUpload::make('customIconUrl')
                                ->label(__('admin.content.blocks.delivery_info_section.fields.custom_icon'))
                                ->disk('public')
                                ->directory('pages/delivery-icons')
                                ->visibility('public')
                                ->image()
                                ->columnSpanFull(),
                        ])
                        ->columns(1)
                        ->columnSpanFull(),
                    ...static::spacingFields('delivery_info_section', mobileTop: '24', desktopTop: '48'),
                ])
                ->columns(2),
            Block::make('faq_section')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('faq_section', __('admin.content.blocks.faq_section.label'), $state, 'sectionTitle');
                })
                ->icon('heroicon-o-question-mark-circle')
                ->schema([
                    TextInput::make('sectionTitle')
                        ->label(__('admin.content.blocks.faq_section.fields.section_title'))
                        ->required()
                        ->default('Popular questions and answers')
                        ->live(onBlur: true),
                    TextInput::make('faqButtonText')
                        ->label(__('admin.content.blocks.faq_section.fields.button_text'))
                        ->default('FAQ section'),
                    TextInput::make('faqButtonLink')
                        ->label(__('admin.content.blocks.faq_section.fields.button_link'))
                        ->placeholder('/faq'),
                    Repeater::make('items')
                        ->label(__('admin.content.blocks.faq_section.fields.items'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            TextInput::make('question')
                                ->label(__('admin.content.blocks.faq_section.fields.question'))
                                ->required(),
                            Textarea::make('answer')
                                ->label(__('admin.content.blocks.faq_section.fields.answer'))
                                ->rows(4)
                                ->required(),
                        ])
                        ->columns(1)
                        ->columnSpanFull(),
                    ...static::spacingFields('faq_section', mobileTop: '24', desktopTop: '70'),
                ])
                ->columns(2),
        ];
    }

    /**
     * @return array<int, Block>
     */
    protected static function returnBlocks(): array
    {
        return [
            Block::make('return_hero')
                ->label(static::labelWithIdentifier('return_hero', __('admin.content.blocks.return_hero.label')))
                ->icon('heroicon-o-photo')
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.content.blocks.return_hero.fields.title'))
                        ->required()
                        ->default('Обмін та повернення')
                        ->live(onBlur: true),
                    Textarea::make('subtitle')
                        ->label(__('admin.content.blocks.return_hero.fields.subtitle'))
                        ->rows(3)
                        ->required()
                        ->default('Повернути можна будь-який товар придбаний в магазині')
                        ->columnSpanFull(),
                    FileUpload::make('desktopImageUrl')
                        ->label(__('admin.content.blocks.return_hero.fields.desktop_image'))
                        ->disk('public')
                        ->directory('pages/return-hero')
                        ->visibility('public')
                        ->image()
                        ->columnSpan(1),
                    FileUpload::make('mobileImageUrl')
                        ->label(__('admin.content.blocks.return_hero.fields.mobile_image'))
                        ->disk('public')
                        ->directory('pages/return-hero')
                        ->visibility('public')
                        ->image()
                        ->columnSpan(1),
                    TextInput::make('imageAlt')
                        ->label(__('admin.content.blocks.return_hero.fields.image_alt'))
                        ->columnSpanFull(),
                    ...static::spacingFields('return_hero', mobileTop: '24', desktopTop: '24'),
                ])
                ->columns(2)
                ->maxItems(1),
            Block::make('return_info_section')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('return_info_section', __('admin.content.blocks.return_info_section.label'), $state, 'sectionTitle');
                })
                ->icon('heroicon-o-queue-list')
                ->schema([
                    TextInput::make('sectionTitle')
                        ->label(__('admin.content.blocks.return_info_section.fields.section_title'))
                        ->live(onBlur: true),
                    Repeater::make('items')
                        ->label(__('admin.content.blocks.return_info_section.fields.items'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            Select::make('iconType')
                                ->label(__('admin.content.blocks.return_info_section.fields.icon_type'))
                                ->options([
                                    'check' => __('admin.content.blocks.return_info_section.icons.check'),
                                    'warning' => __('admin.content.blocks.return_info_section.icons.warning'),
                                    'delivery' => __('admin.content.blocks.return_info_section.icons.delivery'),
                                    'location' => __('admin.content.blocks.return_info_section.icons.location'),
                                ])
                                ->default('check')
                                ->required(),
                            ColorPicker::make('bgColor')
                                ->label(__('admin.content.blocks.return_info_section.fields.background_color'))
                                ->default('#E4F5F6'),
                            Textarea::make('text')
                                ->label(__('admin.content.blocks.return_info_section.fields.text'))
                                ->rows(4)
                                ->required()
                                ->columnSpanFull(),
                            FileUpload::make('customIconUrl')
                                ->label(__('admin.content.blocks.return_info_section.fields.custom_icon'))
                                ->disk('public')
                                ->directory('pages/return-icons')
                                ->visibility('public')
                                ->image()
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    ...static::spacingFields('return_info_section', mobileTop: '24', desktopTop: '24'),
                ])
                ->columns(1),
            Block::make('return_faq_section')
                ->label(function (?array $state): string {
                    return static::dynamicBlockLabel('return_faq_section', __('admin.content.blocks.return_faq_section.label'), $state, 'sectionTitle');
                })
                ->icon('heroicon-o-question-mark-circle')
                ->schema([
                    TextInput::make('sectionTitle')
                        ->label(__('admin.content.blocks.return_faq_section.fields.section_title'))
                        ->required()
                        ->default('Популярні запитання та відповіді')
                        ->live(onBlur: true),
                    TextInput::make('faqButtonText')
                        ->label(__('admin.content.blocks.return_faq_section.fields.button_text'))
                        ->default('Розділ FAQ'),
                    TextInput::make('faqButtonLink')
                        ->label(__('admin.content.blocks.return_faq_section.fields.button_link'))
                        ->placeholder('/faq'),
                    Repeater::make('items')
                        ->label(__('admin.content.blocks.return_faq_section.fields.items'))
                        ->defaultItems(0)
                        ->reorderableWithButtons()
                        ->schema([
                            TextInput::make('question')
                                ->label(__('admin.content.blocks.return_faq_section.fields.question'))
                                ->required(),
                            Textarea::make('answer')
                                ->label(__('admin.content.blocks.return_faq_section.fields.answer'))
                                ->rows(4)
                                ->required(),
                        ])
                        ->columns(1)
                        ->columnSpanFull(),
                    ...static::spacingFields('return_faq_section', mobileTop: '24', desktopTop: '70'),
                ])
                ->columns(2),
        ];
    }

    /**
     * @return array<int, TextInput>
     */
    protected static function labelWithIdentifier(string $identifier, string $label): string
    {
        return sprintf('%s (%s)', $label, $identifier);
    }

    protected static function dynamicBlockLabel(
        string $identifier,
        string $fallbackLabel,
        ?array $state,
        string $stateKey = 'title',
    ): string {
        $label = filled($state[$stateKey] ?? null) ? (string) $state[$stateKey] : $fallbackLabel;

        return static::labelWithIdentifier($identifier, $label);
    }

    /**
     * @return array<int, TextInput>
     */
    protected static function spacingFields(
        string $prefix,
        string $mobileTop = '24',
        string $mobileBottom = '0',
        string $desktopTop = '0',
        string $desktopBottom = '0',
    ): array {
        return [
            TextInput::make('marginTopMobile')
                ->label(__('admin.content.blocks.'.$prefix.'.fields.margin_top_mobile'))
                ->numeric()
                ->default($mobileTop),
            TextInput::make('marginBottomMobile')
                ->label(__('admin.content.blocks.'.$prefix.'.fields.margin_bottom_mobile'))
                ->numeric()
                ->default($mobileBottom),
            TextInput::make('marginTopDesktop')
                ->label(__('admin.content.blocks.'.$prefix.'.fields.margin_top_desktop'))
                ->numeric()
                ->default($desktopTop),
            TextInput::make('marginBottomDesktop')
                ->label(__('admin.content.blocks.'.$prefix.'.fields.margin_bottom_desktop'))
                ->numeric()
                ->default($desktopBottom),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function deliveryInfoIcons(): array
    {
        return [
            'branch' => __('admin.content.blocks.delivery_info_section.icons.branch'),
            'parcel-locker' => __('admin.content.blocks.delivery_info_section.icons.parcel_locker'),
            'courier' => __('admin.content.blocks.delivery_info_section.icons.courier'),
            'pickup' => __('admin.content.blocks.delivery_info_section.icons.pickup'),
            'transport' => __('admin.content.blocks.delivery_info_section.icons.transport'),
            'card' => __('admin.content.blocks.delivery_info_section.icons.card'),
            'cash' => __('admin.content.blocks.delivery_info_section.icons.cash'),
            'cod' => __('admin.content.blocks.delivery_info_section.icons.cod'),
            'bank' => __('admin.content.blocks.delivery_info_section.icons.bank'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function homeShortcutIcons(): array
    {
        return [
            'men' => __('admin.content.blocks.home_top_section.icons.men'),
            'women' => __('admin.content.blocks.home_top_section.icons.women'),
            'children' => __('admin.content.blocks.home_top_section.icons.children'),
            'promo' => __('admin.content.blocks.home_top_section.icons.promo'),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected static function categoryOptions(): array
    {
        $defaultLocale = \Commero\Support\Locales::default();
        $currentLocale = app()->getLocale();

        return Category::query()
            ->with('translations')
            ->orderBy('sort')
            ->orderBy('path')
            ->get()
            ->mapWithKeys(function (Category $category) use ($currentLocale, $defaultLocale): array {
                $name = $category->exactTranslation($currentLocale)?->name
                    ?? $category->exactTranslation($defaultLocale)?->name
                    ?? $category->translations->first()?->name
                    ?? $category->path;

                return [$category->id => $name];
            })
            ->all();
    }
}
