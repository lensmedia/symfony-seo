<?php

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\Attribute\Meta;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

readonly class MetaCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private RouteCollectionHelper $routeCollectionHelper,
    ) {
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->routeCollectionHelper->all() as $routeName => $route) {
            // Just triggering this function caches the result in our
            // helper, we can use that here and reuse later.
            $this->routeCollectionHelper->attributesFromRoute($route, Meta::class);
        }

        return [
            Meta::class,
            MetaFactory::class,
            RouteCollectionHelper::class,
        ];
    }

    public function isOptional(): bool
    {
        return true;
    }
}
