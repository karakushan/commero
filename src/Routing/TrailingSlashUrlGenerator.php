<?php

namespace Commero\Routing;

use Closure;
use Commero\Support\TrailingSlash;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Str;

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

    /**
     * Signed URLs are generated with a trailing slash, while the base check trims it from the request URL.
     */
    public function hasCorrectSignature(Request $request, $absolute = true, Closure|array $ignoreQuery = [])
    {
        if (parent::hasCorrectSignature($request, $absolute, $ignoreQuery)) {
            return true;
        }

        $signature = $request->query('signature');

        if (! is_string($signature)) {
            return false;
        }

        $path = rtrim($request->getPathInfo(), '/').'/';
        $url = $absolute
            ? $request->getSchemeAndHttpHost().$request->getBaseUrl().$path
            : '/'.ltrim($path, '/');

        $queryString = collect(explode('&', (string) ($request->server->get('VAPOR_RAW_QUERY_STRING') ?? $request->server->get('QUERY_STRING'))))
            ->reject(function (string $parameter) use ($ignoreQuery): bool {
                $parameter = Str::before($parameter, '=');

                if ($parameter === 'signature') {
                    return true;
                }

                return $ignoreQuery instanceof Closure ? (bool) $ignoreQuery($parameter) : in_array($parameter, $ignoreQuery, true);
            })
            ->join('&');

        $original = rtrim($url.'?'.$queryString, '?');
        $keys = call_user_func($this->keyResolver);

        foreach (is_array($keys) ? $keys : [$keys] as $key) {
            if (hash_equals(hash_hmac('sha256', $original, (string) $key), $signature)) {
                return true;
            }
        }

        return false;
    }
}
