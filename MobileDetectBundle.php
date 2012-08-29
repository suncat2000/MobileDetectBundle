<?php

namespace SunCat\MobileDetectBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use SunCat\MobileDetectBundle\DependencyInjection\Compiler\CLIListenerPass;

/**
 * MobileDetectBundle 
 */
class MobileDetectBundle extends Bundle
{
    /**
     * Build method
     * @param ContainerBuilder $container 
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CLIListenerPass());
    }
}
