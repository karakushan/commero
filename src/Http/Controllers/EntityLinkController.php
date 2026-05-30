<?php

namespace Commero\Http\Controllers;

use Commero\Interfaces\Http\Livewire\CatalogPage;
use Commero\Interfaces\Http\Livewire\CityCategoryPage;
use Commero\Models\Link;
use Commero\Support\EntityLinkService;
use Commero\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntityLinkController extends Controller
{
    public function __invoke(Request $request, EntityLinkService $entityLinkService): mixed
    {
        $locale = Locales::resolve($request->route('locale') ?? app()->getLocale());
        $slug = (string) $request->route('slug');

        App::setLocale($locale);

        $link = $entityLinkService->resolve($slug, $locale);

        if (! $link) {
            throw new NotFoundHttpException;
        }

        return match ($link->entity_type) {
            Link::ENTITY_CATEGORY => app(CatalogPage::class)(),
            Link::ENTITY_CITY_CATEGORY => app(CityCategoryPage::class)(),
            Link::ENTITY_PAGE => app()->call([app(PageController::class), 'show']),
            default => throw new NotFoundHttpException,
        };
    }
}
