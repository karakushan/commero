<?php

namespace Commero\Providers;

use Commero\Commands\InstallCommand;
use Commero\Domain\Catalog\Domain\Contracts\AttributeRepositoryInterface;
use Commero\Domain\Catalog\Domain\Contracts\CategoryRepositoryInterface;
use Commero\Domain\Catalog\Domain\Contracts\ProductRepositoryInterface;
use Commero\Domain\Catalog\Infrastructure\Repositories\EloquentAttributeRepository;
use Commero\Domain\Catalog\Infrastructure\Repositories\EloquentCategoryRepository;
use Commero\Domain\Catalog\Infrastructure\Repositories\EloquentProductRepository;
use Commero\Providers\Filament\AdminPanelProvider;
use Commero\Support\Locales;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CommeroServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        require_once $this->packagePath('src/Support/helpers.php');

        $this->loadTranslationsFrom($this->packagePath('lang'), 'commero');
        $this->mergePackageConfig();
        $this->synchronizeApplicationLocales();
        $this->registerFilamentPanelProvider();
        $this->registerConsoleCommands();

        $this->app->bind(ProductRepositoryInterface::class, EloquentProductRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, EloquentCategoryRepository::class);
        $this->app->bind(AttributeRepositoryInterface::class, EloquentAttributeRepository::class);
    }

    public function boot(): void
    {
        $this->app->setLocale(Locales::default());
        $this->registerSchemaMacros();

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group($this->packagePath('routes/web.php'));
        }

        $this->loadViewsFrom($this->packagePath('resources/views'), 'commero');
        $this->loadViewsFrom(config('commero.theme_view_path', resource_path('views/shophats')), 'shophats');
        $this->loadMigrationsFrom($this->packagePath('database/migrations'));
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

    private function registerSchemaMacros(): void
    {
        Blueprint::macro('localizedSlugConstraint', function (array $columns = ['locale', 'slug'], ?string $name = null): void {
            /** @var Blueprint $this */
            $this->unique($columns, $name);
        });
    }

    private function registerFilamentPanelProvider(): void
    {
        if ($this->hostApplicationHasPanelProvider()) {
            return;
        }

        $this->app->register(AdminPanelProvider::class);
    }

    private function mergePackageConfig(): void
    {
        $defaults = require $this->packagePath('config/commero.php');
        $overrides = (array) config('commero', []);

        config([
            'commero' => array_replace_recursive($defaults, $overrides),
        ]);
    }

    private function synchronizeApplicationLocales(): void
    {
        config([
            'app.locale' => Locales::default(),
            'app.fallback_locale' => Locales::fallback(),
            'app.supported_locales' => Locales::supported(),
        ]);
    }

    private function hostApplicationHasPanelProvider(): bool
    {
        $filamentProvidersPath = app_path('Providers/Filament');

        if (! is_dir($filamentProvidersPath)) {
            return false;
        }

        return File::glob($filamentProvidersPath.'/*PanelProvider.php') !== [];
    }

    private function registerConsoleCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
        ]);
    }
}
