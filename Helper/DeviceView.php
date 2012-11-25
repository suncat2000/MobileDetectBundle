<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SunCat\MobileDetectBundle\Helper;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\DependencyInjection\Container;

use SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie;

/**
 * DeviceView
 * 
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class DeviceView
{
    const COOKIE_KEY        = 'device_view';
    const SWITCH_PARAM      = 'device_view';
    const VIEW_MOBILE       = 'mobile';
    const VIEW_TABLET       = 'tablet';
    const VIEW_FULL         = 'full';
    const VIEW_NOT_MOBILE   = 'not_mobile';

    private $request;
    private $viewType;

    /**
     * Construct
     * 
     * @param \Symfony\Component\DependencyInjection\Container $serviceContainer 
     */
    public function __construct(Container $serviceContainer)
    {
        if (false === $serviceContainer->isScopeActive('request')) {
            $this->viewType = self::VIEW_NOT_MOBILE;

            return;
        }

        $this->request = $serviceContainer->get('request');

        if ($this->request->query->has(self::SWITCH_PARAM)) {
            $this->viewType = $this->request->query->get(self::SWITCH_PARAM);
        } elseif ($this->request->cookies->has(self::COOKIE_KEY)) {
            $this->viewType = $this->request->cookies->get(self::COOKIE_KEY);
        }
    }

    /**
     * Get view type for device
     * 
     * @return string 
     */
    public function getViewType()
    {
        return $this->viewType;
    }

    /**
     * Is full view type for device
     * 
     * @return boolean 
     */
    public function isFullView()
    {
        return ($this->viewType == self::VIEW_FULL);
    }

    /**
     * Is tablet view type for device
     * 
     * @return boolean 
     */
    public function isTabletView()
    {
        return ($this->viewType == self::VIEW_TABLET);
    }

    /**
     * Is mobile view type for device
     * 
     * @return boolean 
     */
    public function isMobileView()
    {
        return ($this->viewType == self::VIEW_MOBILE);
    }

    /**
     * Is not mobile view type for device (PC, Mac ...)
     * 
     * @return boolean 
     */
    public function isNotMobileView()
    {
        return ($this->viewType == self::VIEW_NOT_MOBILE);
    }

    /**
     * Has switch param in query string (GET)
     * 
     * @return boolean 
     */
    public function hasSwitchParam()
    {
        return ($this->request->query->has(self::SWITCH_PARAM));
    }

    /**
     * Set tablet view type  
     */
    public function setTabletView()
    {
        $this->viewType = self::VIEW_TABLET;
    }

    /**
     * Set mobile view type 
     */
    public function setMobileView()
    {
        $this->viewType = self::VIEW_MOBILE;
    }

    /**
     * Set not mobile view type 
     */
    public function setNotMobileView()
    {
        $this->viewType = self::VIEW_NOT_MOBILE;
    }

    /**
     * Get switch param value from query string (GET)
     * 
     * @return string 
     */
    public function getSwitchParamValue()
    {
        return $this->request->query->get(self::SWITCH_PARAM, self::VIEW_FULL);
    }

    /**
     * Get RedirectResponsy by switch param value
     * 
     * @param string $redirectUrl
     * 
     * @return \SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie 
     */
    public function getRedirectResponseBySwitchParam($redirectUrl)
    {
        $statusCode = 302;

        switch ($this->getSwitchParamValue()) {
            case self::VIEW_MOBILE:
                return new RedirectResponseWithCookie($redirectUrl, $statusCode, $this->getCookie(self::VIEW_MOBILE));
            case self::VIEW_TABLET:
                return new RedirectResponseWithCookie($redirectUrl, $statusCode, $this->getCookie(self::VIEW_TABLET));
            default:
                return new RedirectResponseWithCookie($redirectUrl, $statusCode, $this->getCookie(self::VIEW_FULL));
        }
    }

    /**
     * Modify Response for not mobile device
     * 
     * @param \Symfony\Component\HttpFoundation\Response $response
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function modifyNotMobileResponse(Response $response)
    {
        $response->headers->setCookie($this->getCookie(self::VIEW_NOT_MOBILE));

        return $response;
    }

    /**
     * Modify Response for tablet device
     * 
     * @param \Symfony\Component\HttpFoundation\Response $response
     * 
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function modifyTabletResponse(Response $response)
    {
        $response->headers->setCookie($this->getCookie(self::VIEW_TABLET));

        return $response;
    }

    /**
     * Modify Response for mobile device
     * 
     * @param \Symfony\Component\HttpFoundation\Response $response
     * 
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function modifyMobileResponse(Response $response)
    {
        $response->headers->setCookie($this->getCookie(self::VIEW_MOBILE));

        return $response;
    }

    /**
     * Get RedirectResponse for tablet
     * 
     * @param string $host       Uri host
     * @param int    $statusCode Status code
     * 
     * @return \SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie 
     */
    public function getTabletRedirectResponse($host, $statusCode)
    {
        return new RedirectResponseWithCookie($host, $statusCode, $this->getCookie(self::VIEW_TABLET));
    }

    /**
     * Get RedirectResponse for mobile
     * 
     * @param string $host       Uri host
     * @param int    $statusCode Status code
     * 
     * @return \SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie 
     */
    public function getMobileRedirectResponse($host, $statusCode)
    {
        return new RedirectResponseWithCookie($host, $statusCode, $this->getCookie(self::VIEW_MOBILE));
    }

    /**
     * Get cookie
     * 
     * @param string $cookieValue
     * 
     * @return \Symfony\Component\HttpFoundation\Cookie 
     */
    protected function getCookie($cookieValue)
    {
        $currentDate = new \Datetime();

        return new Cookie(self::COOKIE_KEY, $cookieValue, $currentDate->modify('+1 month')->format('Y-m-d'));
    }
}
