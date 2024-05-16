<?php

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $addTwigGlobalOptions = static fn (string $name): NodeDefinition => (new TreeBuilder($name))
        ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->scalarNode('name')->defaultValue($name)->end()
            ->end();

    $definition->rootNode()
        ->children()
            ->scalarNode('fallback_meta_service')->defaultNull()->end()

            ->arrayNode('structured_data')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('json_encode_options')
                        ->defaultValue(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    ->end()
                ->end()
            ->end()

            ->arrayNode('twig')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('globals')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('prefix')->defaultValue('lens_seo_')->end()
                        ->append($addTwigGlobalOptions('meta'))
                        ->append($addTwigGlobalOptions('breadcrumbs'))
                        ->append($addTwigGlobalOptions('structured_data'))
                    ->end()
                ->end()
            ->end()
        ->end();
};
