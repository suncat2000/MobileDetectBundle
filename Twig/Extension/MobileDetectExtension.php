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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * MobileDetectExtension
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class MobileDetectExtension extends \Twig_Extension
{
    /**
     * @var MobileDetector
     */
    private $mobileDetector;

    /**
     * @var DeviceView
     */
    private $deviceView;

    /**
     * @var array
     */
    private $redirectConf;

    /**
     * @var Request
     */
    private $request;

    /**
     * MobileDetectExtension constructor.
     *
     * @param MobileDetector $mobileDetector
     * @param DeviceView     $deviceView
     * @param array          $redirectConf
     */
    public function __construct(MobileDetector $mobileDetector, DeviceView $deviceView, array $redirectConf)
    {
        $this->mobileDetector = $mobileDetector;
        $this->deviceView = $deviceView;
        $this->redirectConf = $redirectConf;
    }

    /**
     * Get extension twig function
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('is_mobile', array($this, 'isMobile')),
            new \Twig_SimpleFunction('is_tablet', array($this, 'isTablet')),
            new \Twig_SimpleFunction('is_device', array($this, 'isDevice')),
            new \Twig_SimpleFunction('is_full_view', array($this, 'isFullView')),
            new \Twig_SimpleFunction('is_mobile_view', array($this, 'isMobileView')),
            new \Twig_SimpleFunction('is_tablet_view', array($this, 'isTabletView')),
            new \Twig_SimpleFunction('is_not_mobile_view', array($this, 'isNotMobileView')),
            new \Twig_SimpleFunction('is_ios', array($this, 'isIOS')),
            new \Twig_SimpleFunction('is_android_os', array($this, 'isAndroidOS')),
            new \Twig_SimpleFunction('full_view_url', array($this, 'fullViewUrl'), array('is_safe' => array('html'))),
            new \Twig_SimpleFunction('device_version', array($this, 'deviceVersion')),
        );
    }

    /**
     * Check the version of the given property in the User-Agent.
     * Will return a float number. (eg. 2_0 will return 2.0, 4.3.1 will return 4.31)
     *
     * @param string $propertyName The name of the property. See self::getProperties() array
     *                             keys for all possible properties.
     * @param string $type         Either self::VERSION_TYPE_STRING to get a string value or
     *                             self::VERSION_TYPE_FLOAT indicating a float value. This parameter
     *                             is optional and defaults to self::VERSION_TYPE_STRING. Passing an
     *                             invalid parameter will default to the this type as well.
     *
     * @return string|float The version of the property we are trying to extract.
     */
    public function deviceVersion($propertyName, $type = \Mobile_Detect::VERSION_TYPE_STRING)
    {
        return $this->mobileDetector->version($propertyName, $type);
    }

    /**
     * Regardless of the current view, returns the URL that leads to the equivalent page
     * in the full/desktop view. This is useful for generating <link rel="canonical"> tags
     * on mobile pages for Search Engine Optimization.
     * See: http://searchengineland.com/the-definitive-guide-to-mobile-technical-seo-166066
     *
     * @param boolean $addCurrentPathAndQuery
     *
     * @return string
     */
    public function fullViewUrl($addCurrentPathAndQuery = true)
    {
        if (!isset($this->redirectConf[DeviceView::VIEW_FULL]['host'])) {
            // The host property has not been configured for the full view
            return null;
        }

        $fullHost = $this->redirectConf[DeviceView::VIEW_FULL]['host'];

        if (empty($fullHost)) {
            return null;
        }

        // If not in request scope, we can only return the base URL to the full view
        if (!$this->request) {
            return $fullHost;
        }

        if (false === $addCurrentPathAndQuery) {
            return $fullHost;
        }

        // if fullHost ends with /, skip it since getPathInfo() also starts with /
        $result = rtrim($fullHost, '/').$this->request->getPathInfo();

        $query = Request::normalizeQueryString(http_build_query($this->request->query->all(), null, '&'));
        if ($query) {
            $result .= '?'.$query;
        }

        return $result;
    }

    /**
     * Is mobile
     *
     * @return boolean
     */
    public function isMobile()
    {
        return $this->mobileDetector->isMobile();
    }

    /**
     * Is tablet
     *
     * @return boolean
     */
    public function isTablet()
    {
        return $this->mobileDetector->isTablet();
    }

    /**
     * Is device
     *
     * @param string $deviceName is[iPhone|BlackBerry|HTC|Nexus|Dell|Motorola|Samsung|Sony|Asus|Palm|Vertu|...]
     *
     * @return boolean
     */
    public function isDevice($deviceName)
    {
        $magicMethodName = 'is'.strtolower((string) $deviceName);

        return $this->mobileDetector->$magicMethodName();
    }

    /**
     * Is full view type
     *
     * @return boolean
     */
    public function isFullView()
    {
        return $this->deviceView->isFullView();
    }

    /**
     * Is mobile view type
     *
     * @return boolean
     */
    public function isMobileView()
    {
        return $this->deviceView->isMobileView();
    }

    /**
     * Is tablet view type
     *
     * @return boolean
     */
    public function isTabletView()
    {
        return $this->deviceView->isTabletView();
    }

    /**
     * Is not mobile view type
     *
     * @return boolean
     */
    public function isNotMobileView()
    {
        return $this->deviceView->isNotMobileView();
    }

    /**
     * Is iOS
     *
     * @return boolean
     */
    public function isIOS()
    {
        return $this->mobileDetector->isIOS();
    }

    /**
     * Is Android OS
     *
     * @return boolean
     */
    public function isAndroidOS()
    {
        return $this->mobileDetector->isAndroidOS();
    }

    /**
     * Sets the request from the current scope.
     *
     * @param RequestStack $requestStack
     */
    public function setRequestByRequestStack(RequestStack $requestStack = null)
    {
        if (null !== $requestStack) {
            $this->request = $requestStack->getMasterRequest();
        }
    }
}
