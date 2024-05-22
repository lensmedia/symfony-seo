<?php

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\Attribute\Breadcrumb;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use function is_array;

class BreadcrumbFactory
{
    private static array $cached = [];

    /** @var BreadcrumbResolverInterface[] */
    private array $resolvers = [];

    public function __construct(
        private readonly RouteCollectionHelper $routeCollectionHelper,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
        iterable $resolvers,
    ) {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof BreadcrumbResolverInterface) {
                throw new RuntimeException(sprintf(
                    'Breadcrumb resolver "%s" must implement "%s".',
                    $resolver::class,
                    BreadcrumbResolverInterface::class,
                ));
            }

            $this->resolvers[$resolver::class] = $resolver;
        }
    }

    public function get(?Request $request = null): array
    {
        $request ??= $this->requestStack->getMainRequest();
        if (!$request) {
            return [];
        }

        $routeName = $request->attributes->get('_route');
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

                $breadcrumb = $this->breadcrumbFromRoute($request, $routeName, $route, $breadcrumbs);
                if (!$breadcrumb) {
                    break;
                }
            } while ($routeName = $breadcrumb->parent);

            self::$cached[$index] = $breadcrumbs;
        }

        return self::$cached[$index];
    }

    private function breadcrumbFromRoute(Request $request, string $canonicalRoute, Route $route, array &$breadcrumbs = []): ?Breadcrumb
    {
        $locale = $request->getLocale();

        $breadcrumb = $this->breadcrumbAttributeFromRoute($route);
        if (!$breadcrumb) {
            return null;
        }

        $breadcrumb->routeName = $canonicalRoute;
        $breadcrumb->routeParameters = array_intersect_key(
            $request->attributes->get('_route_params', []),
            array_flip($route->compile()?->getPathVariables() ?? []),
        );

        $breadcrumb->routeParameters['_locale'] = $locale;

        if ($breadcrumb->resolver) {
            $this->executeResolver($request, $breadcrumb, $breadcrumbs);
        } else {
            if (is_array($breadcrumb->title)) {
                $breadcrumb->title = $breadcrumb->title[$locale];
            }

            if ($breadcrumb->translate) {
                $breadcrumb->title = $this->translator->trans(
                    $breadcrumb->title,
                    $breadcrumb->context,
                    $breadcrumb->translationDomain,
                    locale: $locale
                );
            }

            $breadcrumbs[] = $breadcrumb;
        }

        return $breadcrumb;
    }

    private function breadcrumbAttributeFromRoute(Route $route): ?Breadcrumb
    {
        $breadcrumbs = $this->routeCollectionHelper->attributesFromRoute($route, Breadcrumb::class);

        return $breadcrumbs[0] ?? null;
    }

    private function executeResolver(Request $request, Breadcrumb $breadcrumb, array &$breadcrumbs): void
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
