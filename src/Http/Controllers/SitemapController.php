<?php

namespace Commero\Http\Controllers;

use Commero\Support\Seo\SitemapBuilder;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(SitemapBuilder $sitemapBuilder): Response
    {
        return $this->xml('commero::sitemap.index', [
            'sitemaps' => $sitemapBuilder->index(),
        ]);
    }

    public function map(string $map, SitemapBuilder $sitemapBuilder): Response
    {
        return $this->xml('commero::sitemap.urlset', [
            'urls' => $sitemapBuilder->entries($map),
        ]);
    }

    private function xml(string $view, array $data): Response
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=900');
    }
}
