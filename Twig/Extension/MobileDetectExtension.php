<?php

namespace SunCat\MobileDetectBundle\Twig\Extension;

use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use Symfony\Component\DependencyInjection\Container;

use Twig_Extension;

/**
 * MobileDetectExtension
 */
class MobileDetectExtension extends Twig_Extension
{
    private $mobileDetector;

    /**
     * Set mobile detector
     * @param MobileDetector $mobileDetector 
     */
    public function setMobileDetector(MobileDetector $mobileDetector)
    {
        $this->mobileDetector = $mobileDetector;
    }

    /**
     * Get extension twig function
     * @return array 
     */
    public function getFunctions()
    {
        return array(
            'is_mobile' => new \Twig_Function_Method($this, 'isMobile'),
            'is_tablet' => new \Twig_Function_Method($this, 'isTablet'),
            'is_device' => new \Twig_Function_Method($this, 'isDevice'),
            'is_full_view' => new \Twig_Function_Method($this, 'isFullView'),
            'is_mobile_view' => new \Twig_Function_Method($this, 'isMobileView'),
            'is_tablet_view' => new \Twig_Function_Method($this, 'isTabletView'),
            'is_not_mobile_view' => new \Twig_Function_Method($this, 'isNotMobileView'),
        );
    }

    /**
     * Is mobile
     * @return boolean 
     */
    public function isMobile()
    {
        return $this->mobileDetector->isMobile();
    }

    /**
     * Is tablet
     * @return boolean 
     */
    public function isTablet()
    {
        return $this->mobileDetector->isTablet();
    }

    /**
     * Is device
     * @param string $deviceName is[iPhone|BlackBerry|HTC|Nexus|Dell|Motorola|Samsung|Sony|Asus|Palm|Vertu|...]
     * 
     * @return boolean 
     */
    public function isDevice($deviceName)
    {
        $magicMethodName = 'is' . strtolower((string) $deviceName);

        return $this->mobileDetector->$magicMethodName();
    }

    /**
     * Is full view type
     * @return boolean 
     */
    public function isFullView()
    {
        return $this->mobileDetector->getDeviceView()->isFullView();
    }

    /**
     * Is mobile view type
     * @return type 
     */
    public function isMobileView()
    {
        return $this->mobileDetector->getDeviceView()->isMobileView();
    }

    /**
     * Is tablet view type
     * @return type 
     */
    public function isTabletView()
    {
        return $this->mobileDetector->getDeviceView()->isTabletView();
    }

    /**
     * Is not mobile view type
     * @return type 
     */
    public function isNotMobileView()
    {
        return $this->mobileDetector->getDeviceView()->isNotMobileView();
    }

    /**
     * Extension name
     * @return string 
     */
    public function getName()
    {
        return 'mobile_detect.twig.extension';
    }
}
