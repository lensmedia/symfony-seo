<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Lens\Bundle\SeoBundle\BreadcrumbFactory;
use Lens\Bundle\SeoBundle\BreadcrumbResolverInterface;
use Lens\Bundle\SeoBundle\DataCollector\SeoDataCollector;
use Lens\Bundle\SeoBundle\Event\AppendStructuredDataToResponse;
use Lens\Bundle\SeoBundle\MetaCacheWarmer;
use Lens\Bundle\SeoBundle\MetaFactory;
use Lens\Bundle\SeoBundle\MetaResolverInterface;
use Lens\Bundle\SeoBundle\RouteCollectionHelper;
use Lens\Bundle\SeoBundle\StructuredData\StructuredDataBuilder;
use Lens\Bundle\SeoBundle\StructuredData\StructuredDataResolverInterface;
use Lens\Bundle\SeoBundle\Twig\BreadcrumbExtension;
use Lens\Bundle\SeoBundle\Twig\MetaExtension;
use Lens\Bundle\SeoBundle\Twig\StructuredDataExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

return static function (ContainerConfigurator $container) {
    $container->services()
        // Meta
        ->set(MetaFactory::class)
        ->args([
            service(LoggerInterface::class),
            service(CacheInterface::class),
            service(TranslatorInterface::class),
            service(RouteCollectionHelper::class),
            service(RequestStack::class),
            tagged_iterator(MetaResolverInterface::META_RESOLVER_SERVICE_TAG),
            null,
            param('kernel.debug'),
        ])

        ->set(MetaCacheWarmer::class)
        ->args([
            service(RouteCollectionHelper::class),
        ])
        ->tag('kernel.cache_warmer')

        // Breadcrumb
        ->set(BreadcrumbFactory::class)
        ->args([
            service(RouteCollectionHelper::class),
            service(TranslatorInterface::class),
            service(RequestStack::class),
            tagged_iterator(BreadcrumbResolverInterface::BREADCRUMB_RESOLVER_SERVICE_TAG),
        ])

        // StructuredData
        ->set(StructuredDataBuilder::class)
        ->args([
            tagged_iterator(StructuredDataResolverInterface::STRUCTURED_DATA_SERVICE_TAG),
            param('lens_seo.structured_data.json_encode_options'),
        ])

        // RouteCollectionHelper
        ->set(RouteCollectionHelper::class)
        ->args([
            service(CacheInterface::class),
            service(RouterInterface::class),
            param('kernel.debug'),
        ])

        ->set(AppendStructuredDataToResponse::class)
        ->args([
            service(StructuredDataBuilder::class),
        ])
        ->tag('kernel.event_listener', [
            'event' => 'kernel.response',
            'priority' => -8192,
        ])

        // Twig extensions (adds globals)
        ->set(MetaExtension::class)
        ->args([
            service(MetaFactory::class),
            param('lens_seo.twig.globals.meta.enabled'),
            param('lens_seo.twig.globals.meta.name'),
        ])
        ->tag('twig.extension')

        ->set(BreadcrumbExtension::class)
        ->args([
            service(BreadcrumbFactory::class),
            param('lens_seo.twig.globals.breadcrumbs.enabled'),
            param('lens_seo.twig.globals.breadcrumbs.name'),
        ])
        ->tag('twig.extension')

        ->set(StructuredDataExtension::class)
        ->args([
            service(StructuredDataBuilder::class),
            param('lens_seo.twig.globals.structured_data.enabled'),
            param('lens_seo.twig.globals.structured_data.name'),
        ])
        ->tag('twig.extension')

        ->set(SeoDataCollector::class)
        ->args([
            service(MetaFactory::class),
            service(BreadcrumbFactory::class),
            service(StructuredDataBuilder::class),
        ])
        ->tag('data_collector', [
            'id' => 'lens_seo',
            'template' => '@LensSeo/data_collector.html.twig',
            'priority' => 0,
        ])
    ;
};
