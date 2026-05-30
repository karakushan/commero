<?php

namespace Commero\Providers;

use Commero\Domain\Catalog\Domain\Contracts\AttributeRepositoryInterface;
use Commero\Domain\Catalog\Domain\Contracts\CategoryRepositoryInterface;
use Commero\Domain\Catalog\Domain\Contracts\ProductRepositoryInterface;
use Commero\Domain\Catalog\Infrastructure\Repositories\EloquentAttributeRepository;
use Commero\Domain\Catalog\Infrastructure\Repositories\EloquentCategoryRepository;
use Commero\Domain\Catalog\Infrastructure\Repositories\EloquentProductRepository;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CommeroServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once $this->packagePath('src/Support/helpers.php');

        $this->mergeConfigFrom($this->packagePath('config/commero.php'), 'commero');

        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(AttributeRepositoryInterface::class, EloquentAttributeRepository::class);
    }

    public function boot(): void
    {
        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group($this->packagePath('routes/web.php'));
        }

        $this->loadViewsFrom(config('commero.theme_view_path', resource_path('views/shophats')), 'shophats');
        $this->loadMigrationsFrom($this->packagePath('database/migrations'));
        $this->loadTranslationsFrom($this->packagePath('lang'), 'commero');

        $this->publishes([
            $this->packagePath('config/commero.php') => config_path('commero.php'),
        ], 'commero-config');

        $this->publishes([
            $this->packagePath('lang') => lang_path('vendor/commero'),
        ], 'commero-lang');
    }

    private function packagePath(string $path = ''): string
    {
        return dirname(__DIR__, 2).'/'.ltrim($path, '/');
    }
}
