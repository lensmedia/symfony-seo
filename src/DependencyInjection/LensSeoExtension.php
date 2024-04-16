<?php

declare(strict_types=1);

namespace Lens\Bundle\SeoBundle\DependencyInjection;

use Lens\Bundle\SeoBundle\BreadcrumbResolverInterface;
use Lens\Bundle\SeoBundle\MetaFactory;
use Lens\Bundle\SeoBundle\MetaResolverInterface;
use Lens\Bundle\SeoBundle\StructuredData\StructuredDataResolverInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class LensSeoExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('lens_seo.structured_data.json_encode_options', $config['structured_data']['json_encode_options']);

        // Using container parameter for enabled as $container->removeDefinition does not work?
        $container->setParameter('lens_seo.twig.globals.meta.enabled', $config['twig']['globals']['meta']['enabled']);
        $container->setParameter('lens_seo.twig.globals.meta.name', $config['twig']['globals']['prefix'].$config['twig']['globals']['meta']['name']);
        $container->setParameter('lens_seo.twig.globals.breadcrumbs.enabled', $config['twig']['globals']['breadcrumbs']['enabled']);
        $container->setParameter('lens_seo.twig.globals.breadcrumbs.name', $config['twig']['globals']['prefix'].$config['twig']['globals']['breadcrumbs']['name']);
        $container->setParameter('lens_seo.twig.globals.structured_data.enabled', $config['twig']['globals']['structured_data']['enabled']);
        $container->setParameter('lens_seo.twig.globals.structured_data.name', $config['twig']['globals']['prefix'].$config['twig']['globals']['structured_data']['name']);

        $loader = new PhpFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.php');

        $container->registerForAutoconfiguration(MetaResolverInterface::class)->addTag(MetaResolverInterface::SERVICE_TAG);
        $container->registerForAutoconfiguration(BreadcrumbResolverInterface::class)->addTag(BreadcrumbResolverInterface::SERVICE_TAG);
        $container->registerForAutoconfiguration(StructuredDataResolverInterface::class)->addTag(StructuredDataResolverInterface::SERVICE_TAG);

        $fallbackMetaService = $config['fallback_meta_service'];
        if ($fallbackMetaService) {
            $metaFactory = $container->getDefinition(MetaFactory::class);
            $metaFactory->setArgument('$fallbackMetaService', new Reference($fallbackMetaService));
        }
    }
}
