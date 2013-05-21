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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * DI extension
 */
class MobileDetectExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // valid mobile host
        if ($config['redirect']['mobile']['is_enabled'] && !$this->validHost($config['redirect']['mobile']['host'])) {
            $config['redirect']['mobile']['is_enabled'] = false;
        }

        // valid tablet host
        if ($config['redirect']['tablet']['is_enabled'] && !$this->validHost($config['redirect']['tablet']['host'])) {
            $config['redirect']['tablet']['is_enabled'] = false;
        }

        $container->setParameter('mobile_detect.redirect', $config['redirect']);
        $container->setParameter('mobile_detect.switch_device_view.save_referer_path', $config['switch_device_view']['save_referer_path']);
        $container->setParameter('mobile_detect.mobile_detector.phone_devices', $config['mobile_detector']['phone_devices']);
        $container->setParameter('mobile_detect.mobile_detector.tablet_devices', $config['mobile_detector']['tablet_devices']);
        $container->setParameter('mobile_detect.mobile_detector.operating_systems', $config['mobile_detector']['operating_systems']);
        $container->setParameter('mobile_detect.mobile_detector.user_agents', $config['mobile_detector']['user_agents']);
        $container->setParameter('mobile_detect.mobile_detector.utilities', $config['mobile_detector']['utilities']);
        $container->setParameter('mobile_detect.mobile_detector.properties', $config['mobile_detector']['properties']);
    }

    /**
     * Validate host
     * @param string $url
     *
     * @return boolean
     */
    protected function validHost($url)
    {
        $pattern = "/^(?:(http|https):\/\/)([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";

        return (bool) preg_match($pattern, $url);
    }
}
