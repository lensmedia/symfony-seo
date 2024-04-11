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

    public function get(): array
    {
        $routeName = $this->request()?->get('_route');
        if (!$routeName || str_starts_with($routeName, '_')) {
            return [];
        }

        $index = spl_object_hash($this->request());
        if (!isset(self::$cached[$index])) {
            $items = [];
            do {
                $route = $this->routeCollectionHelper->route($routeName, $this->request()->getLocale());
                if (!$route) {
                    break;
                }

                $breadCrumb = $this->breadcrumb($routeName, $route, $this->request()->getLocale());
                if (!$breadCrumb) {
                    break;
                }

                array_unshift($items, $breadCrumb);
            } while ($routeName = $breadCrumb->parent);

            self::$cached[$index] = $items;
        }

        return self::$cached[$index];
    }

    public function breadcrumb(string $canonicalRoute, Route $route, string $currentLocale): ?Breadcrumb
    {
        $cacheIndex = 'lens_seo.breadcrumb.'.$canonicalRoute.'.'.$currentLocale;
        if ($this->isDebug) {
            $this->cache->delete($cacheIndex);
        }

        $breadcrumb = $this->cache->get($cacheIndex, function () use ($route, $canonicalRoute, $currentLocale) {
            $breadcrumbs = $this->routeCollectionHelper->attributesFromRoute($route, Breadcrumb::class);
            if (empty($breadcrumbs)) {
                return null;
            }

            /** @var Breadcrumb $breadcrumb */
            $breadcrumb = $breadcrumbs[0];
            $breadcrumb->routeName = $canonicalRoute;
            $breadcrumb->routeParameters = array_intersect_key(
                $this->request()->attributes->get('_route_params', []),
                array_flip($route->compile()?->getPathVariables() ?? []),
            );

            $breadcrumb->routeParameters['_locale'] = $currentLocale;

            // We can cache the result only when it's not
            // a resolver as they tend to be dynamic.
            if (!$breadcrumb->resolver) {
                if (is_array($breadcrumb->title)) {
                    $breadcrumb->title = $breadcrumb->title[$currentLocale];
                }

                if ($breadcrumb->translate) {
                    $breadcrumb->title = $this->translator->trans(
                        $breadcrumb->title,
                        $breadcrumb->context,
                        locale: $currentLocale
                    );
                }
            }

            return $breadcrumb;
        });

        if ($breadcrumb?->resolver) {
            if (empty($this->resolvers[$breadcrumb->resolver])) {
                throw new RuntimeException(sprintf(
                    'Breadcrumb resolver "%s" is not an existing service.',
                    $breadcrumb->resolver,
                ));
            }

            $resolver = $this->resolvers[$breadcrumb->resolver];
            $resolver->resolveBreadcrumb($this->request(), $breadcrumb);

            if ($breadcrumb->translate) {
                $breadcrumb->title = $this->translator->trans(
                    $breadcrumb->title,
                    $breadcrumb->context,
                    locale: $currentLocale
                );
            }
        }

        return $breadcrumb;
    }

    private function request(): ?Request
    {
        return $this->requestStack->getMainRequest();
    }
}
