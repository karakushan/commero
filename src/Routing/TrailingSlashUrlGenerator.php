<?php

namespace Commero\Routing;

use Commero\Support\TrailingSlash;
use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;

class TrailingSlashUrlGenerator extends UrlGenerator
{
    public static function fromBase(UrlGenerator $url): self
    {
        $generator = new self($url->routes, $url->request, $url->assetRoot);

        $generator->forcedRoot = $url->forcedRoot;
        $generator->forceScheme = $url->forceScheme;
        $generator->cachedRoot = $url->cachedRoot;
        $generator->cachedScheme = $url->cachedScheme;
        $generator->rootNamespace = $url->rootNamespace;
        $generator->sessionResolver = $url->sessionResolver;
        $generator->keyResolver = $url->keyResolver;
        $generator->missingNamedRouteResolver = $url->missingNamedRouteResolver;
        $generator->formatHostUsing = $url->formatHostUsing;
        $generator->formatPathUsing = $url->formatPathUsing;

        $defaults = $url->getDefaultParameters();

        if ($defaults !== []) {
            $generator->defaults($defaults);
        }

        return $generator;
    }

    public function format($root, $path, $route = null)
    {
        $path = '/'.trim($path, '/');

        if ($this->formatHostUsing) {
            $root = call_user_func($this->formatHostUsing, $root, $route);
        }

        if ($this->formatPathUsing) {
            $path = call_user_func($this->formatPathUsing, $path, $route);
        }

        $path = $this->shouldKeepWithoutTrailingSlash($path, $route)
            ? $path
            : TrailingSlash::normalize($path);

        if ($path === '/') {
            return rtrim($root, '/').'/';
        }

        return rtrim($root, '/').$path;
    }

    protected function shouldKeepWithoutTrailingSlash(string $path, ?Route $route): bool
    {
        if ($route?->methods() !== null && ! in_array('GET', $route->methods(), true)) {
            return true;
        }

        return TrailingSlash::shouldSkip($path);
    }
}
