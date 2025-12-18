<?php

declare(strict_types=1);

namespace Lens\Bundle\SeoBundle;

use Symfony\Component\HttpFoundation\Request;

trait SupportsRequestTrait
{
    private function supportsRequest(Request $request, array $urls = []): bool
    {
        $route = $request->attributes->get('_route');
        if (null === $route || str_starts_with($route, '_')) {
            return false;
        }

        // If no urls are defined, then we support all requests
        if (empty($urls['include']) && empty($urls['exclude'])) {
            return true;
        }

        $path = $request->getPathInfo();

        if (!empty($urls['exclude'])) {
            foreach ($urls['exclude'] as $url) {
                if (preg_match($url, $path)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($urls['include'] as $url) {
            if (preg_match($url, $path)) {
                return true;
            }
        }

        return false;
    }
}
