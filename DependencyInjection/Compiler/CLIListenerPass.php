<?php

namespace SunCat\MobileDetectBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * CLI listener
 */
class CLIListenerPass implements CompilerPassInterface
{
    /**
     * If CLI, when remove setMobileDetector method from definition mobile_detect.twig.extension
     * @param ContainerBuilder $container 
     */
    public function process(ContainerBuilder $container)
    {
        if (php_sapi_name() == "cli") {
            $definition = $container->getDefinition('mobile_detect.twig.extension');
            $definition->removeMethodCall('setMobileDetector');
        }
    }
}
