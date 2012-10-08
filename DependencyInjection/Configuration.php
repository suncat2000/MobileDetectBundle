<?php

namespace SunCat\MobileDetectBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use SunCat\MobileDetectBundle\EventListener\RequestListener;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mobile_detect');

        $rootNode
            ->children()
                ->arrayNode('redirect')
                    ->children()
                        ->arrayNode('mobile')
                            ->isRequired()
                            ->children()
                                ->booleanNode('is_enabled')->defaultFalse()->end()
                                ->scalarNode('host')->defaultNull()->end()
                                ->scalarNode('status_code')->defaultValue(302)->cannotBeEmpty()->end()
                                ->scalarNode('action')->defaultValue(RequestListener::REDIRECT)->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                        ->arrayNode('tablet')
                            ->isRequired()
                            ->children()
                                ->booleanNode('is_enabled')->defaultFalse()->end()
                                ->scalarNode('host')->defaultNull()->end()
                                ->scalarNode('status_code')->defaultValue(302)->cannotBeEmpty()->end()
                                ->scalarNode('action')->defaultValue(RequestListener::REDIRECT)->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('switch_device_view')
                    ->isRequired()
                    ->children()
                        ->booleanNode('save_referer_path')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
