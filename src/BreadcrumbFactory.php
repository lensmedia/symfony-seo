<?php

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\Attribute\Breadcrumb;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function is_array;

class BreadcrumbFactory
{
    private static array $cached = [];

    /** @var BreadcrumbResolverInterface[] */
    private array $resolvers = [];

    public function __construct(
        private readonly RouteCollectionHelper $routeCollectionHelper,
        private readonly CacheInterface $cache,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        iterable $resolvers,
        private readonly bool $isDebug = false,
    ) {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof BreadcrumbResolverInterface) {
                throw new RuntimeException(sprintf(
                    'Breadcrumb resolver "%s" must implement "%s".',
                    get_class($resolver),
                    BreadcrumbResolverInterface::class,
                ));
            }

            $this->resolvers[$resolver::class] = $resolver;
        }
    }

    public function get(?Request $request = null): array
    {
        $request ??= $this->requestStack->getCurrentRequest();
        if (!$request) {
            throw new RuntimeException('Breadcrumbs only work when in request context.');
        }

        $routeName = $request->get('_route');
        if (!$routeName || str_starts_with($routeName, '_')) {
            return [];
        }

        $index = spl_object_hash($request);
        if (!isset(self::$cached[$index])) {
            $breadcrumbs = [];
            do {
                $route = $this->routeCollectionHelper->route($routeName, $request->getLocale());
                if (!$route) {
                    break;
                }

                $breadCrumb = $this->breadcrumb($request, $routeName, $route, $request->getLocale(), $breadcrumbs);
                if (!$breadCrumb) {
                    break;
                }
            } while ($routeName = $breadCrumb->parent);

            self::$cached[$index] = $breadcrumbs;
        }

        return self::$cached[$index];
    }

    private function breadcrumb(Request $request, string $canonicalRoute, Route $route, string $currentLocale, array &$breadcrumbs = []): ?Breadcrumb
    {
        $cacheIndex = 'lens_seo.breadcrumb.'.$canonicalRoute.'.'.$currentLocale;
        if ($this->isDebug) {
            $this->cache->delete($cacheIndex);
        }

        /** @var ?Breadcrumb $breadcrumb */
        $breadcrumb = $this->cache->get($cacheIndex, function () use ($request, $route, $canonicalRoute, $currentLocale) {
            $breadcrumbs = $this->routeCollectionHelper->attributesFromRoute($route, Breadcrumb::class);
            if (empty($breadcrumbs)) {
                return null;
            }

            /** @var Breadcrumb $breadcrumb */
            $breadcrumb = $breadcrumbs[0];
            $breadcrumb->routeName = $canonicalRoute;
            $breadcrumb->routeParameters = array_intersect_key(
                $request->attributes->get('_route_params', []),
                array_flip($route->compile()?->getPathVariables() ?? []),
            );

            $breadcrumb->routeParameters['_locale'] = $currentLocale;

            // We can only resolve and cache the result when it's not a resolver as they tend to be dynamic.
            if (!$breadcrumb->resolver) {
                if (is_array($breadcrumb->title)) {
                    $breadcrumb->title = $breadcrumb->title[$currentLocale];
                }

                if ($breadcrumb->translate) {
                    $breadcrumb->title = $this->translator->trans(
                        $breadcrumb->title,
                        $breadcrumb->context,
                        $breadcrumb->translationDomain,
                        locale: $currentLocale
                    );
                }
            }

            return $breadcrumb;
        });

        if ($breadcrumb?->resolver) {
            $this->resolve($request, $breadcrumb, $breadcrumbs);
        }

        return $breadcrumb;
    }

    private function resolve(Request $request, Breadcrumb $breadcrumb, array &$breadcrumbs): void
    {
        if (empty($this->resolvers[$breadcrumb->resolver])) {
            throw new RuntimeException(sprintf(
                'Breadcrumb resolver "%s" is not an existing service.',
                $breadcrumb->resolver,
            ));
        }

        $resolver = $this->resolvers[$breadcrumb->resolver];
        $resolver->resolveBreadcrumb($request, $breadcrumb, $breadcrumbs);
    }
}
