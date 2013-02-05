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

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
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
     * Constructor
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
     * Gets the view type for a device.
     *
     * @return string
     */
    public function getViewType()
    {
        return $this->viewType;
    }

    /**
     * Is the device in full view.
     *
     * @return boolean
     */
    public function isFullView()
    {
        return $this->viewType === self::VIEW_FULL;
    }

    /**
     * Is the device a tablet view type.
     *
     * @return boolean
     */
    public function isTabletView()
    {
        return $this->viewType === self::VIEW_TABLET;
    }

    /**
     * Is the device a mobile view type.
     *
     * @return boolean
     */
    public function isMobileView()
    {
        return $this->viewType === self::VIEW_MOBILE;
    }

    /**
     * Is not the device a mobile view type (PC, Mac, etc.).
     *
     * @return boolean
     */
    public function isNotMobileView()
    {
        return $this->viewType === self::VIEW_NOT_MOBILE;
    }

    /**
     * Has the Request the switch param in the query string (GET header).
     *
     * @return boolean
     */
    public function hasSwitchParam()
    {
        return $this->request->query->has(self::SWITCH_PARAM);
    }

    /**
     * Sets the tablet view type.
     */
    public function setTabletView()
    {
        $this->viewType = self::VIEW_TABLET;
    }

    /**
     * Sets the mobile view type.
     */
    public function setMobileView()
    {
        $this->viewType = self::VIEW_MOBILE;
    }

    /**
     * Sets the not mobile view type.
     */
    public function setNotMobileView()
    {
        $this->viewType = self::VIEW_NOT_MOBILE;
    }

    /**
     * Gets the switch param value from the query string (GET header).
     *
     * @return string
     */
    public function getSwitchParamValue()
    {
        return $this->request->query->get(self::SWITCH_PARAM, self::VIEW_FULL);
    }

    /**
     * Gets the RedirectResponse by switch param value.
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
     * Modifies the Response for non-mobile devices.
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
     * Modifies the Response for tablet devices.
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
     * Modifies the Response for mobile devices.
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
     * Gets the RedirectResponse for tablet devices.
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
     * Gets the RedirectResponse for mobile devices.
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
     * Gets the cookie.
     *
     * @param string $cookieValue
     *
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    protected function getCookie($cookieValue)
    {
        $currentDate = new \Datetime('+1 month');

        return new Cookie(self::COOKIE_KEY, $cookieValue, $currentDate->format('Y-m-d'));
    }
}
