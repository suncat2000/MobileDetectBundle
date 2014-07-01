<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SunCat\MobileDetectBundle\Twig\Extension;

use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use SunCat\MobileDetectBundle\Helper\DeviceView;
use Twig_Extension;

/**
 * MobileDetectExtension
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class MobileDetectExtension extends Twig_Extension
{
    private $mobileDetector;

    /**
     * Constructor
     *
     * @param Container $serviceContainer
     */
    public function __construct(MobileDetector $mobileDetector, DeviceView $deviceView)
    {
        $this->mobileDetector = $mobileDetector;
        $this->deviceView = $deviceView;
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
            'is_ios' => new \Twig_Function_Method($this, 'isIOS'),
            'is_android_os' => new \Twig_Function_Method($this, 'isAndroidOS'),
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
        return $this->deviceView->isFullView();
    }

    /**
     * Is mobile view type
     * @return type
     */
    public function isMobileView()
    {
        return $this->deviceView->isMobileView();
    }

    /**
     * Is tablet view type
     * @return type
     */
    public function isTabletView()
    {
        return $this->deviceView->isTabletView();
    }

    /**
     * Is not mobile view type
     * @return type
     */
    public function isNotMobileView()
    {
        return $this->deviceView->isNotMobileView();
    }

    /**
     * Is iOS
     * @return boolean
     */
    public function isIOS()
    {
        return $this->mobileDetector->isIOS();
    }

    /**
     * Is Android OS
     * @return boolean
     */
    public function isAndroidOS()
    {
        return $this->mobileDetector->isAndroidOS();
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
