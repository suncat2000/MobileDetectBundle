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

use SunCat\MobileDetectBundle\DeviceDetector\DeviceDetectorInterface;
use SunCat\MobileDetectBundle\Helper\DeviceView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * MobileDetectExtension
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class MobileDetectExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /**
     * @var \SunCat\MobileDetectBundle\DeviceDetector\DeviceDetectorInterface
     */
    private $deviceDetector;
    
    /**
     * @var \SunCat\MobileDetectBundle\Helper\DeviceView
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
     * @param DeviceDetectorInterface $deviceDetector
     * @param DeviceView $deviceView
     * @param array $redirectConf
     */
    public function __construct(DeviceDetectorInterface $deviceDetector, DeviceView $deviceView, array $redirectConf)
    {
        $this->deviceDetector = $deviceDetector;
        $this->deviceView = $deviceView;
        $this->redirectConf = $redirectConf;
    }

    /**
     * Get extension twig function
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
            new \Twig_SimpleFunction('full_view_url', array($this, 'fullViewUrl'), array('is_safe' => array('html')))
        );
    }

    /**
     * Regardless of the current view, returns the URL that leads to the equivalent page
     * in the full/desktop view. This is useful for generating <link rel="canonical"> tags
     * on mobile pages for Search Engine Optimization.
     * See: http://searchengineland.com/the-definitive-guide-to-mobile-technical-seo-166066
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
        $result = rtrim($fullHost, '/') . $this->request->getPathInfo();

        $query = Request::normalizeQueryString(http_build_query($this->request->query->all(), null, '&'));
        if ($query) {
            $result .= '?' . $query;
        }

        return $result;
    }

    /**
     * Is mobile
     * @return boolean
     */
    public function isMobile()
    {
        return $this->deviceDetector->isMobile();
    }

    /**
     * Is tablet
     * @return boolean
     */
    public function isTablet()
    {
        return $this->deviceDetector->isTablet();
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
        return $this->deviceDetector->isDevice('IOS');
    }

    /**
     * Is Android OS
     * 
     * @return boolean
     */
    public function isAndroidOS()
    {
        return $this->deviceDetector->isDevice('AndroidOS');
    }

    /**
     * Sets the request from the current scope.
     * @param Request $request
     */
    public function setRequestByRequestStack(RequestStack $requestStack = null) {
        if (null !== $requestStack) {
            $this->request = $requestStack->getMasterRequest();
        }
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
