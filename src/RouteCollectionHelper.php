<?php

namespace Lens\Bundle\SeoBundle;

use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;

use const ARRAY_FILTER_USE_BOTH;

readonly class RouteCollectionHelper
{
    public function __construct(
        private CacheInterface $cache,
        private RouterInterface $router,
        private bool $isDebug = false,
    ) {
    }

    public function all(): array
    {
        $cacheItem = $this->cache->getItem('lens_seo.route_collection_helper.routes');
        if ($this->isDebug || !$cacheItem->isHit()) {
            $routes = array_filter(
                $this->router->getRouteCollection()->all(),
                static fn (Route $route, string $routeName) => !str_starts_with($routeName, '_') && !str_starts_with($route->getPath(), '/_'),
                ARRAY_FILTER_USE_BOTH
            );

            ksort($routes);

            $cacheItem->set($routes);

            $this->cache->save($cacheItem);
        }

        return $cacheItem->get();
    }

    public function route(string $route, ?string $locale = null): ?Route
    {
        $routes = $this->all();

        return $routes[$route.'.'.$locale]
            ?? $routes[$route]
            ?? null;
    }

    public function attributesFromRequest(Request $request, ?string $attribute = null): array
    {
        $controller = $request->attributes->get('_controller');
        if (!$controller) {
            return [];
        }

        return $this->attributesFromControllerMethod($controller, $attribute);
    }

    public function attributesFromRoute(Route $route, ?string $attribute = null): array
    {
        $controller = $route->getDefault('_controller');
        if (!$controller) {
            return [];
        }

        return $this->attributesFromControllerMethod($controller, $attribute);
    }

    public function attributesFromControllerMethod(string $controllerMethod, ?string $attribute = null): array
    {
        try {
            $controllerMethod = $this->validateControllerMethodString($controllerMethod);
        } catch (InvalidArgumentException) {
            return [];
        }

        [$class, $method] = explode('::', $controllerMethod);

        try {
            $reflectionClass = new ReflectionClass($class);
            $reflectionMethod = $reflectionClass->getMethod($method);
        } catch (ReflectionException) {
            return [];
        }

        return array_map(
            static fn (ReflectionAttribute $attribute) => $attribute->newInstance(),
            $reflectionMethod->getAttributes($attribute),
        );
    }

    private function validateControllerMethodString(string $controller): string
    {
        @[$controller, $method] = explode('::', $controller);

        $method ??= '__invoke';

        if (!class_exists($controller) || !method_exists($controller, $method)) {
            throw new InvalidArgumentException(sprintf(
                'Controller method "%s" does not exist.',
                $controller.'::'.$method,
            ));
        }

        return $controller.'::'.$method;
    }
}
