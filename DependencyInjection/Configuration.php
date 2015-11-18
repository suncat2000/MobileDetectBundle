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
                        ->arrayNode('full')
                            ->children()
                                ->booleanNode('is_enabled')->defaultFalse()->end()
                                ->scalarNode('host')->defaultNull()->end()
                                ->scalarNode('status_code')->defaultValue(302)->cannotBeEmpty()->end()
                                ->scalarNode('action')->defaultValue(RequestListener::REDIRECT)->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                        ->booleanNode('detect_tablet_as_mobile')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('switch_device_view')
                    ->isRequired()
                    ->children()
                        ->booleanNode('save_referer_path')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('service')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('mobile_detector')->defaultValue('mobile_detect.mobile_detector.default')->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->scalarNode('cookie_key')->defaultValue('device_view')->cannotBeEmpty()->end()
                ->scalarNode('switch_param')->defaultValue('device_view')->cannotBeEmpty()->end()
                ->scalarNode('mobile_detector_class')->defaultValue('SunCat\MobileDetectBundle\DeviceDetector\MobileDetector')->cannotBeEmpty()->end()
                ->scalarNode('device_view_class')->defaultValue('SunCat\MobileDetectBundle\Helper\DeviceView')->cannotBeEmpty()->end()
                ->scalarNode('request_listener_class')->defaultValue('SunCat\MobileDetectBundle\EventListener\RequestListener')->cannotBeEmpty()->end()
                ->scalarNode('extension_class')->defaultValue('SunCat\MobileDetectBundle\Twig\Extension\MobileDetectExtension')->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}
