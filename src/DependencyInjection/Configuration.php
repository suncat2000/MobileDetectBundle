<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MobileDetectBundle\DependencyInjection;

use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 * @author HenriVesala <email@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('mobile_detect');
        $rootNode = $treeBuilder->getRootNode();

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
            ->scalarNode('status_code')->defaultValue(Response::HTTP_FOUND)->cannotBeEmpty()->end()
            ->scalarNode('action')->defaultValue(RequestResponseListener::REDIRECT)->cannotBeEmpty()->end()
            ->end()
            ->end()
            ->arrayNode('tablet')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('is_enabled')->defaultFalse()->end()
            ->scalarNode('host')->defaultNull()->end()
            ->scalarNode('status_code')->defaultValue(Response::HTTP_FOUND)->cannotBeEmpty()->end()
            ->scalarNode('action')->defaultValue(RequestResponseListener::REDIRECT)->cannotBeEmpty()->end()
            ->end()
            ->end()
            ->arrayNode('full')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('is_enabled')->defaultFalse()->end()
            ->scalarNode('host')->defaultNull()->end()
            ->scalarNode('status_code')->defaultValue(Response::HTTP_FOUND)->cannotBeEmpty()->end()
            ->scalarNode('action')->defaultValue(RequestResponseListener::REDIRECT)->cannotBeEmpty()->end()
            ->end()
            ->end()
            ->booleanNode('detect_tablet_as_mobile')->defaultFalse()->end()
            ->end()
            ->end()
            ->arrayNode('switch_device_view')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('save_referer_path')->defaultTrue()->end()
            ->end()
            ->end()
            ->arrayNode('service')
            ->addDefaultsIfNotSet()
            ->end()
            ->scalarNode('cookie_key')->defaultValue(DeviceView::COOKIE_KEY_DEFAULT)->cannotBeEmpty()->end()
            ->scalarNode('cookie_path')->defaultValue(DeviceView::COOKIE_PATH_DEFAULT)->cannotBeEmpty()->end()
            ->scalarNode('cookie_domain')->defaultValue(DeviceView::COOKIE_DOMAIN_DEFAULT)->cannotBeEmpty()->end()
            ->booleanNode('cookie_secure')->defaultValue(DeviceView::COOKIE_SECURE_DEFAULT)->end()
            ->booleanNode('cookie_httpOnly')->defaultValue(DeviceView::COOKIE_HTTP_ONLY_DEFAULT)->end()
            ->scalarNode('cookie_expire_datetime_modifier')->defaultValue(DeviceView::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT)->cannotBeEmpty()->end()
            ->scalarNode('switch_param')->defaultValue(DeviceView::SWITCH_PARAM_DEFAULT)->cannotBeEmpty()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
