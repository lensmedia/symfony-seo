<?php

declare(strict_types=1);

namespace Lens\Bundle\SeoBundle;

use Lens\Bundle\SeoBundle\StructuredData\StructuredDataResolverInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class LensSeoBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $container->parameters()
            ->set('lens_seo.structured_data.json_encode_options', $config['structured_data']['json_encode_options'])
            ->set('lens_seo.urls', $config['urls']);

        // Using container parameter for enabled as $container->removeDefinition does not work?
        foreach (['meta', 'breadcrumbs', 'structured_data'] as $type) {
            $container->parameters()
                ->set('lens_seo.twig.globals.'.$type.'.enabled', $config['twig']['globals'][$type]['enabled'])
                ->set('lens_seo.twig.globals.'.$type.'.name', $config['twig']['globals']['prefix'].$config['twig']['globals'][$type]['name']);
        }

        $builder->registerForAutoconfiguration(MetaResolverInterface::class)->addTag(MetaResolverInterface::META_RESOLVER_SERVICE_TAG);
        $builder->registerForAutoconfiguration(BreadcrumbResolverInterface::class)->addTag(BreadcrumbResolverInterface::BREADCRUMB_RESOLVER_SERVICE_TAG);
        $builder->registerForAutoconfiguration(StructuredDataResolverInterface::class)->addTag(StructuredDataResolverInterface::STRUCTURED_DATA_SERVICE_TAG);

        $fallbackMetaService = $config['fallback_meta_service'];
        if ($fallbackMetaService) {
            $metaFactory = $builder->getDefinition(MetaFactory::class);
            $metaFactory->setArgument('$fallbackMetaService', new Reference($fallbackMetaService));
        }
    }
}
