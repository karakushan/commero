<?php

namespace Commero\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Commero\Interfaces\Filament\Livewire\DatabaseNotifications;
use Commero\Interfaces\Filament\Pages\SiteSettings;
use Commero\Interfaces\Filament\Resources\CategoryResource\Pages\CreateCategory;
use Commero\Interfaces\Filament\Resources\CategoryResource\Pages\EditCategory;
use Commero\Interfaces\Filament\Resources\CityCategoryResource\Pages\CreateCityCategory;
use Commero\Interfaces\Filament\Resources\CityCategoryResource\Pages\EditCityCategory;
use Commero\Interfaces\Filament\Resources\MenuResource\Pages\CreateMenu;
use Commero\Interfaces\Filament\Resources\MenuResource\Pages\EditMenu;
use Commero\Interfaces\Filament\Resources\OrderStatusResource\Pages\CreateOrderStatus;
use Commero\Interfaces\Filament\Resources\OrderStatusResource\Pages\EditOrderStatus;
use Commero\Interfaces\Filament\Resources\PageResource\Pages\CreatePage;
use Commero\Interfaces\Filament\Resources\PageResource\Pages\EditPage;
use Commero\Interfaces\Filament\Resources\PaymentMethodResource\Pages\CreatePaymentMethod;
use Commero\Interfaces\Filament\Resources\PaymentMethodResource\Pages\EditPaymentMethod;
use Commero\Interfaces\Filament\Resources\PostCategoryResource\Pages\CreatePostCategory;
use Commero\Interfaces\Filament\Resources\PostCategoryResource\Pages\EditPostCategory;
use Commero\Interfaces\Filament\Resources\PostResource\Pages\CreatePost;
use Commero\Interfaces\Filament\Resources\PostResource\Pages\EditPost;
use Commero\Interfaces\Filament\Resources\ProductAttributeResource\Pages\CreateProductAttribute;
use Commero\Interfaces\Filament\Resources\ProductAttributeResource\Pages\EditProductAttribute;
use Commero\Interfaces\Filament\Resources\ProductResource\Pages\CreateProduct;
use Commero\Interfaces\Filament\Resources\ProductResource\Pages\EditProduct;
use Commero\Interfaces\Filament\Resources\ShippingMethodResource\Pages\CreateShippingMethod;
use Commero\Interfaces\Filament\Resources\ShippingMethodResource\Pages\EditShippingMethod;
use Commero\Models\SiteSetting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->default()
            ->login()
            ->navigationGroups([
                __('commero::admin.navigation.catalog'),
                __('commero::admin.navigation.marketing'),
                __('commero::admin.navigation.orders'),
                __('commero::admin.navigation.content'),
                __('commero::admin.navigation.access'),
                __('commero::admin.navigation.system'),
            ])
            ->discoverResources(
                in: dirname(__DIR__, 2).'/Interfaces/Filament/Resources',
                for: 'Commero\\Interfaces\\Filament\\Resources',
            )
            ->discoverPages(
                in: dirname(__DIR__, 2).'/Interfaces/Filament/Pages',
                for: 'Commero\\Interfaces\\Filament\\Pages',
            )
            ->pages([])
            ->widgets([
                AccountWidget::class,
            ])
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
                fn (): string => view('commero::filament.components.page-language-switcher')->render(),
                scopes: [
                    CreatePage::class,
                    EditPage::class,
                    CreateProduct::class,
                    EditProduct::class,
                    CreateCategory::class,
                    EditCategory::class,
                    CreateCityCategory::class,
                    EditCityCategory::class,
                    CreateProductAttribute::class,
                    EditProductAttribute::class,
                    CreateMenu::class,
                    EditMenu::class,
                    CreatePost::class,
                    EditPost::class,
                    CreatePostCategory::class,
                    EditPostCategory::class,
                    CreateOrderStatus::class,
                    EditOrderStatus::class,
                    CreatePaymentMethod::class,
                    EditPaymentMethod::class,
                    CreateShippingMethod::class,
                    EditShippingMethod::class,
                    SiteSettings::class,
                ],
            )
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('commero::filament.components.admin-rich-editor-styles')->render(),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup(__('commero::admin.navigation.access'))
                    ->navigationSort(9998)
                    ->navigationLabel(__('commero::admin.resources.role.navigation'))
                    ->modelLabel(__('commero::admin.resources.role.singular'))
                    ->pluralModelLabel(__('commero::admin.resources.role.plural'))
                    ->localizePermissionLabels(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web')
            ->databaseNotifications(livewireComponent: DatabaseNotifications::class)
            ->brandLogo(fn (): ?string => static::getSetting()?->logo_path
                ? asset('storage/'.static::getSetting()->logo_path)
                : null)
            ->brandLogoHeight('2rem')
            ->brandName(fn (): string => static::getBrandName());
    }

    protected static function getSetting(): ?SiteSetting
    {
        static $setting;
        static $loaded = false;

        if ($loaded || app()->environment('testing')) {
            return $setting instanceof SiteSetting ? $setting : null;
        }

        try {
            $setting = SiteSetting::query()->first();
        } catch (\Throwable) {
            $setting = null;
        }

        $loaded = true;

        return $setting instanceof SiteSetting ? $setting : null;
    }

    protected static function getBrandName(): string
    {
        return static::getSetting()?->site_name
            ?? config('commero::app.name', 'ShopHats');
    }
}
