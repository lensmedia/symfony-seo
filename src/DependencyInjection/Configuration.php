<?php

declare(strict_types=1);

namespace Lens\Bundle\SeoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

readonly class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('lens_seo');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            ->end();

        $this->addStructuredDataSection($rootNode);
        $this->addTwigExtensionsSection($rootNode);

        return $treeBuilder;
    }

    private function addStructuredDataSection(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('structured_data')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('json_encode_options')
                        ->defaultValue(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    ->end()
                ->end()
            ->end();
    }

    private function addTwigExtensionsSection(ArrayNodeDefinition $node): void
    {
        $node->children()
            ->arrayNode('twig')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('globals')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('prefix')->defaultValue('lens_seo_')->end()
                        ->append($this->addTwigGlobalOptions('meta'))
                        ->append($this->addTwigGlobalOptions('breadcrumbs'))
                        ->append($this->addTwigGlobalOptions('structured_data'))
                    ->end()
                ->end()
            ->end();
    }

    private function addTwigGlobalOptions(string $name): NodeDefinition
    {
        return (new TreeBuilder($name))
            ->getRootNode()
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
                ->scalarNode('name')->defaultValue($name)->end()
            ->end();
    }
}
