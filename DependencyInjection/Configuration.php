<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SunCat\MobileDetectBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use SunCat\MobileDetectBundle\EventListener\RequestListener;

/**
 * Bundle configuration
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 * @author HenriVesala <email@gmail.com>
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
