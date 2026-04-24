<?php

namespace InSquare\OpendxpSeoBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('in_square_opendxp_seo');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('thumbnail_name')
                    ->defaultValue('meta_tag_image')
                ->end()
                ->arrayNode('hreflang')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('x_default_language')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('title_pattern')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('before')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('after')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
