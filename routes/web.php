<?php

use Illuminate\Support\Facades\Route;
use Commero\Http\Controllers\AccountController;
use Commero\Http\Controllers\BlogController;
use Commero\Http\Controllers\CartController;
use Commero\Http\Controllers\CatalogFilterPreviewController;
use Commero\Http\Controllers\CheckoutDeliveryController;
use Commero\Http\Controllers\ContactController;
use Commero\Http\Controllers\EntityLinkController;
use Commero\Http\Controllers\ErrorPageController;
use Commero\Http\Controllers\HomeController;
use Commero\Http\Controllers\PageController;
use Commero\Http\Controllers\ProductController;
use Commero\Http\Controllers\ThankYouController;
use Commero\Http\Controllers\WishlistController;
use Commero\Interfaces\Http\Livewire\CatalogPage;
use Commero\Interfaces\Http\Livewire\CheckoutPage;
use Commero\Interfaces\Http\Livewire\SaleProductsPage;
use Commero\Interfaces\Http\Livewire\SearchPage;
use Commero\Interfaces\Http\Livewire\SpecialOffersPage;
use Commero\Livewire\ResetPasswordPage;
use Commero\Support\Locales;

$reservedRootSlugs = implode('|', array_map(
    static fn (string $slug): string => preg_quote($slug, '/'),
    array_merge(
        (array) config('commero.routing.reserved_root_slugs', ['admin', 'home']),
        Locales::supported(),
    ),
));
$additionalLocales = Locales::additional();

Route::redirect('/'.Locales::default(), '/');
Route::redirect('/'.Locales::default().'/catalog', '/catalog');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/catalog', CatalogPage::class)->name('catalog.index');
Route::get('/catalog/filter-preview', CatalogFilterPreviewController::class)->name('catalog.preview-count');

// Sale products page
Route::get('/sale', SaleProductsPage::class)->name('sale.index');

// Special offers page
Route::get('/special-offers', SpecialOffersPage::class)->name('special-offers.index');

// Single product page
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::post('/product/{slug}/reviews', [ProductController::class, 'storeReview'])->name('product.reviews.store');

// Blog routes
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/post/{slug}', [BlogController::class, 'show'])->name('post.show');
Route::get('/category/{slug}', [BlogController::class, 'index'])->name('blog.category');
Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');

// 404 Error Page
Route::get('/404', [ErrorPageController::class, 'notFound'])->name('errors.404');

// Search
Route::get('/search', SearchPage::class)->name('search');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{lineId}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{lineId}', [CartController::class, 'destroy'])->name('cart.items.destroy');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::post('/wishlist/items', [WishlistController::class, 'store'])->name('wishlist.items.store');
Route::delete('/wishlist/items/{productId}', [WishlistController::class, 'destroy'])->name('wishlist.items.destroy');
Route::post('/wishlist/items/{productId}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.items.toggle');
Route::delete('/wishlist', [WishlistController::class, 'clear'])->name('wishlist.clear');

// Checkout
Route::get('/checkout', CheckoutPage::class)->name('checkout.index');
Route::get('/checkout/delivery/cities', [CheckoutDeliveryController::class, 'cities'])->name('checkout.delivery.cities');
Route::get('/checkout/delivery/warehouses', [CheckoutDeliveryController::class, 'warehouses'])->name('checkout.delivery.warehouses');

// Thank You page
Route::get('/thank-you/{orderNumber?}', [ThankYouController::class, 'show'])->name('thank-you.show');

// Account Dashboard
Route::get('/account', [AccountController::class, 'index'])->name('account.index')->middleware('auth');
Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');

// Authentication stubs (заглушки для авторизации)
Route::get('/login', function () {
    return redirect()->route('checkout.index');
})->name('login');

Route::get('/register', function () {
    return redirect()->route('checkout.index');
})->name('register');

Route::post('/logout', function () {
    auth()->logout();

    return redirect()->route('home');
})->name('logout');

Route::get('/politika-konfidencijnosti', [PageController::class, 'show'])
    ->defaults('slug', 'politika-konfidencijnosti')
    ->name('privacy.policy');

Route::get('/{slug}', EntityLinkController::class)
    ->where('slug', '^(?!(?:'.$reservedRootSlugs.')$)[A-Za-z0-9\-_]+$')
    ->name('entity-links.show');

if ($additionalLocales !== []) {
    Route::prefix('{locale}')
        ->whereIn('locale', $additionalLocales)
        ->group(function () use ($reservedRootSlugs): void {
            Route::get('/', [HomeController::class, 'index'])->name('localized.home');
            Route::get('/catalog', CatalogPage::class)->name('localized.catalog.index');
            Route::get('/catalog/filter-preview', CatalogFilterPreviewController::class)->name('localized.catalog.preview-count');

            // Sale products page (localized)
            Route::get('/sale', SaleProductsPage::class)->name('localized.sale.index');

            // Special offers page (localized)
            Route::get('/special-offers', SpecialOffersPage::class)->name('localized.special-offers.index');

            // Single product page (localized)
            Route::get('/product/{slug}', [ProductController::class, 'show'])->name('localized.product.show');
            Route::post('/product/{slug}/reviews', [ProductController::class, 'storeReview'])->name('localized.product.reviews.store');

            // Blog routes (localized)
            Route::get('/blog', [BlogController::class, 'index'])->name('localized.blog.index');
            Route::get('/post/{slug}', [BlogController::class, 'show'])->name('localized.post.show');
            Route::get('/category/{slug}', [BlogController::class, 'index'])->name('localized.blog.category');
            Route::get('/contacts', [ContactController::class, 'index'])->name('localized.contacts.index');

            // 404 Error Page (localized)
            Route::get('/404', [ErrorPageController::class, 'notFound'])->name('localized.errors.404');

            // Search (localized)
            Route::get('/search', SearchPage::class)->name('localized.search');

            Route::get('/cart', [CartController::class, 'index'])->name('localized.cart.index');
            Route::post('/cart/items', [CartController::class, 'store'])->name('localized.cart.items.store');
            Route::patch('/cart/items/{lineId}', [CartController::class, 'update'])->name('localized.cart.items.update');
            Route::delete('/cart/items/{lineId}', [CartController::class, 'destroy'])->name('localized.cart.items.destroy');
            Route::delete('/cart', [CartController::class, 'clear'])->name('localized.cart.clear');

            Route::get('/wishlist', [WishlistController::class, 'index'])->name('localized.wishlist.index');
            Route::post('/wishlist/items', [WishlistController::class, 'store'])->name('localized.wishlist.items.store');
            Route::delete('/wishlist/items/{productId}', [WishlistController::class, 'destroy'])->name('localized.wishlist.items.destroy');
            Route::post('/wishlist/items/{productId}/toggle', [WishlistController::class, 'toggle'])->name('localized.wishlist.items.toggle');
            Route::delete('/wishlist', [WishlistController::class, 'clear'])->name('localized.wishlist.clear');

            // Checkout (localized)
            Route::get('/checkout', CheckoutPage::class)->name('localized.checkout.index');
            Route::get('/checkout/delivery/cities', [CheckoutDeliveryController::class, 'cities'])->name('localized.checkout.delivery.cities');
            Route::get('/checkout/delivery/warehouses', [CheckoutDeliveryController::class, 'warehouses'])->name('localized.checkout.delivery.warehouses');

            // Thank You page (localized)
            Route::get('/thank-you/{orderNumber?}', [ThankYouController::class, 'show'])->name('localized.thank-you.show');

            // Account Dashboard (localized)
            Route::get('/account', [AccountController::class, 'index'])->name('localized.account.index')->middleware('auth');
            Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('localized.password.reset');

            // Authentication stubs (localized)
            Route::get('/login', function () {
                return redirect()->route('localized.checkout.index');
            })->name('localized.login');

            Route::get('/register', function () {
                return redirect()->route('localized.checkout.index');
            })->name('localized.register');

            Route::post('/logout', function () {
                auth()->logout();

                return redirect()->route('localized.home');
            })->name('localized.logout');

            Route::get('/politika-konfidencijnosti', [PageController::class, 'show'])
                ->defaults('slug', 'politika-konfidencijnosti')
                ->name('localized.privacy.policy');

            Route::get('/{slug}', EntityLinkController::class)
                ->where('slug', '^(?!(?:'.$reservedRootSlugs.')$)[A-Za-z0-9\-_]+$')
                ->name('localized.entity-links.show');
        });
}
