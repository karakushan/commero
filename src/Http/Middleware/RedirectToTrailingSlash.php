<?php

namespace Commero\Http\Middleware;

use Commero\Support\TrailingSlash;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RedirectToTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        if (! $request->isMethodCacheable()) {
            return $next($request);
        }

        $currentPath = $request->getPathInfo();
        $normalizedPath = TrailingSlash::normalize($currentPath);

        if ($normalizedPath === $currentPath) {
            return $next($request);
        }

        $target = $normalizedPath;
        $queryString = $request->getQueryString();

        if (is_string($queryString) && $queryString !== '') {
            $target .= '?'.$queryString;
        }

        return new RedirectResponse($target, 301);
    }
}
