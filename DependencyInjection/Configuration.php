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
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('mobile')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->booleanNode('is_enabled')->defaultFalse()->end()
                                ->scalarNode('host')->defaultNull()->end()
                                ->scalarNode('status_code')->defaultValue(302)->cannotBeEmpty()->end()
                                ->scalarNode('action')->defaultValue(RequestListener::REDIRECT)->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                        ->arrayNode('tablet')
                            ->addDefaultsIfNotSet()
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
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('save_referer_path')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('mobile_detector')
                    ->info('Custom mobile detection configuration rules')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('phone_devices')
                            ->info('custom phone devices detection rules')
                            ->example('[ "CustomPhone": "CustomPhone User Agent regular expression" ]')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('tablet_devices')
                            ->info('custom tablet devices detection rules')
                            ->example('[ "CustomTablet": "CustomTablet User Agent regular expression" ]')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('operating_systems')
                            ->info('custom operating system detection rules')
                            ->example('[ "CustomOS": "CustomOS User Agent regular expression" ]')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('user_agents')
                            ->info('custom user agents detection rules')
                            ->example('[ "CustomUserAgent": "Custom User Agent regular expression" ]')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('utilities')
                            ->info('custom device modes detection rules')
                            ->example('[ "CustomDeviceMode": "CustomDeviceMode User Agent regular expression" ]')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('properties')
                            ->info('custom device property detection rules')
                            ->example('[ "CustomProperty": "CustomProperty User Agent regular expression" ]')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
