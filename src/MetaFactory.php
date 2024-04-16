<?php

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\Attribute\Meta;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class MetaFactory
{
    private static array $cached = [];

    /** @var MetaResolverInterface[] */
    private array $resolvers;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
        private readonly TranslatorInterface $translator,
        private readonly RouteCollectionHelper $routeCollectionHelper,
        private readonly RequestStack $requestStack,
        iterable $resolvers,
        private readonly ?MetaFallbackInterface $fallbackMetaService = null,
        private readonly bool $isDebug = false,
    ) {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof MetaResolverInterface) {
                throw new RuntimeException(sprintf(
                    'Meta resolver "%s" must implement "%s".',
                    get_class($resolver),
                    MetaResolverInterface::class,
                ));
            }

            $this->resolvers[$resolver::class] = $resolver;
        }
    }

    public function get(): ?Meta
    {
        if (!$this->request()) {
            return null;
        }

        if (str_starts_with($this->request()->get('_route'), '_')) {
            return null;
        }

        $index = spl_object_hash($this->request());
        if (!isset(self::$cached[$index])) {
            $cacheItem = $this->cache->getItem(sprintf(
                'app.meta.%s.%s',
                $this->request()->get('_route'),
                $this->request()->getLocale(),
            ));

            if ($this->isDebug || !$cacheItem->isHit()) {
                $cacheItem->set($this->meta());

                $this->cache->save($cacheItem);
            }

            // If null or a resolver we can not cache its result.
            $meta = $cacheItem->get();
            if (!$meta || !$meta->resolver) {
                return $meta;
            }

            self::$cached[$index] = $this->resolve($meta);
        }

        return self::$cached[$index];
    }

    private function fallback(): ?Meta
    {
        if ($this->isDebug) {
            $this->logger->warning(sprintf(
                'The route "%s" has no associated meta information, using fallback.',
                $this->request()?->get('_route', $this->request()?->getPathInfo()),
            ));
        }

        if ($this->fallbackMetaService instanceof MetaFallbackInterface) {
            return $this->fallbackMetaService->fallback($this->request());
        }

        return null;
    }

    private function request(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function meta(): ?Meta
    {
        // Track both current locale meta and default (no locale) meta.
        $localeMeta = null;
        $defaultMeta = null;

        $locale = $this->request()?->getLocale();

        $metaAttributes = $this->routeCollectionHelper
            ->attributesFromRequest($this->request(), Meta::class);

        foreach ($metaAttributes as $metaAttribute) {
            if (null === $metaAttribute->locale) {
                $defaultMeta = $metaAttribute;
                break;
            }

            if ($metaAttribute->locale === $locale) {
                $localeMeta = $metaAttribute;
                break;
            }
        }

        $meta = $localeMeta ?? $defaultMeta ?? $this->fallback();
        if (!$meta) {
            return null;
        }

        $meta->locale = $locale;

        // If meta is not a resolver we are allowed to do and cache the translations right now.
        if (!$meta->resolver && $meta->hasTranslatableOptions()) {
            $meta = $this->translate($meta);
        }

        return $meta;
    }

    private function resolve(Meta $meta): ?Meta
    {
        if (empty($this->resolvers[$meta->resolver])) {
            throw new RuntimeException(sprintf(
                'Meta resolver "%s" is not a loaded "%s".',
                $meta->resolver,
                MetaResolverInterface::class,
            ));
        }

        $resolver = $this->resolvers[$meta->resolver];

        try {
            $resolver->resolveMeta($this->request(), $meta);

            return $meta;
        } catch (Throwable $error) {
            if ($this->isDebug) {
                $errorMessage = sprintf('MetaResolver "%s" threw an error. If this is intended (to not resolve a Meta value) you can ignore this message.', $meta->resolver)
                    .PHP_EOL
                    .PHP_EOL
                    .$error->getMessage();

                $this->logger->warning($errorMessage, [
                    'exception' => $error
                ]);
            }
        }

        return null;
    }

    private function translate(Meta $meta): Meta
    {
        if ($meta->title && ($meta->translate || $meta->translateTitle)) {
            $meta->title = $this->translator->trans(
                $meta->title,
                $meta->context,
                locale: $meta->locale
            );
        }

        if ($meta->description && ($meta->translate || $meta->translateDescription)) {
            $meta->description = $this->translator->trans(
                $meta->description,
                $meta->context,
                locale: $meta->locale
            );
        }

        if ($meta->keywords && ($meta->translate || $meta->translateKeywords)) {
            foreach ($meta->keywords as $index => $keyword) {
                $meta->keywords[$index] = $this->translator->trans(
                    $keyword,
                    $meta->context,
                    locale: $meta->locale
                );
            }
        }

        return $meta;
    }
}
