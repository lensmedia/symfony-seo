<?php

namespace Lens\Bundle\SeoBundle;

use ReflectionAttribute;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;

use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;

readonly class RouteCollectionHelper
{
    public function __construct(
        private KernelInterface $kernel,
        private CacheInterface $cache,
        private RouterInterface $router,
    ) {
    }

    public function all(): array
    {
        return $this->routes();
    }

    public function routes(): array
    {
        $cacheItem = $this->cache->getItem('app.route_collection_helper.routes');

        if (!$cacheItem->isHit() || $this->kernel->isDebug()) {
            $routes = array_filter(
                $this->router->getRouteCollection()->all(),
                static fn (Route $route, string $routeName) => !str_starts_with($routeName, '_')
                    && !str_starts_with($route->getPath(), '/_'),
                ARRAY_FILTER_USE_BOTH
            );

            ksort($routes);

            $cacheItem->set($routes);

            $this->cache->save($cacheItem);
        }

        return $cacheItem->get();
    }

    public function public(): array
    {
        $cacheItem = $this->cache->getItem('app.route_collection_helper.public_routes');

        if (!$cacheItem->isHit() || $this->kernel->isDebug()) {
            $routes = array_filter(
                $this->all(),
                static fn (string $routeName) => !str_starts_with($routeName, 'admin_')
                    && !str_starts_with($routeName, 'api_'),
                ARRAY_FILTER_USE_KEY
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

    public function attributesFromRoute(Route $route, ?string $attribute = null): array
    {
        $controller = $route->getDefault('_controller');
        if (!$controller) {
            return [];
        }

        return $this->attributesFromControllerMethod($controller, $attribute);
    }

    public function attributesFromRequest(
        Request $request,
        ?string $attribute = null
    ): array {
        $controller = $request->get('_controller');
        if (!$controller) {
            return [];
        }

        return $this->attributesFromControllerMethod($controller, $attribute);
    }

    public function attributesFromControllerMethod(
        string $controllerMethod,
        ?string $attribute = null
    ): array {
        $controllerMethod = $this->checkControllerMethod($controllerMethod);
        if (!$controllerMethod) {
            return [];
        }

        $cacheItem = $this->cache->getItem(
            'app.route_collection_helper.attributes.'
            .md5($controllerMethod)
            .($attribute ? '.'.md5($attribute) : '')
        );

        if (!$cacheItem->isHit() || $this->kernel->isDebug()) {
            [$class, $method] = explode('::', $controllerMethod);

            $reflectionClass = new ReflectionClass($class);
            $reflectionMethod = $reflectionClass->getMethod($method);

            $cacheItem->set(array_map(
                static fn (ReflectionAttribute $attribute) => $attribute->newInstance(),
                $reflectionMethod->getAttributes($attribute),
            ));

            $this->cache->save($cacheItem);
        }

        return $cacheItem->get();
    }

    private function checkControllerMethod(string $controller): ?string
    {
        $cacheItem = $this->cache->getItem(
            'app.route_collection_helper.controller.'
            .md5($controller)
        );

        if (!$cacheItem->isHit() || $this->kernel->isDebug()) {
            $cacheItem->set($this->controllerMethod($controller));

            $this->cache->save($cacheItem);
        }

        return $cacheItem->get();
    }

    private function controllerMethod(string $controller): ?string
    {
        if (!str_contains($controller, '::')) {
            if (!method_exists($controller, '__invoke')) {
                throw new RuntimeException('Route controller is missing method or is not invokable.');
            }

            $controller .= '::__invoke';
        }

        [$class, $method] = explode('::', $controller);
        if (!class_exists($class)) {
            if (!$this->kernel->getContainer()->has($class)) {
                throw new RuntimeException(sprintf(
                    'Invalid controller service detected, no such class or service "%s".',
                    $class,
                ));
            }

            if ('web_profiler.controller.profiler' === $class) {
                return null;
            }

            /** @var object $service */
            $service = $this->kernel->getContainer()->get($class);
            $class = $service::class;
        }

        if (!method_exists($class, $method)) {
            throw new RuntimeException(sprintf(
                'Invalid controller method detected, no such class or method "%s::%s".',
                $class,
                $method,
            ));
        }

        return $class.'::'.$method;
    }
}
