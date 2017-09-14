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

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * DeviceView
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class DeviceView
{
    const VIEW_MOBILE       = 'mobile';
    const VIEW_TABLET       = 'tablet';
    const VIEW_FULL         = 'full';
    const VIEW_NOT_MOBILE   = 'not_mobile';

    const COOKIE_KEY_DEFAULT                      = 'device_view';
    const COOKIE_PATH_DEFAULT                     = '/';
    const COOKIE_DOMAIN_DEFAULT                   = '';
    const COOKIE_SECURE_DEFAULT                   = false;
    const COOKIE_HTTP_ONLY_DEFAULT                = true;
    const COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT = '1 month';
    const SWITCH_PARAM_DEFAULT                    = 'device_view';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $requestedViewType;

    /**
     * @var string
     */
    protected $viewType;

    /**
     * @var string
     */
    protected $cookieKey = self::COOKIE_KEY_DEFAULT;

    /**
     * @var string
     */
    protected $cookiePath = self::COOKIE_PATH_DEFAULT;

    /**
     * @var string
     */
    protected $cookieDomain = self::COOKIE_DOMAIN_DEFAULT;

    /**
     * @var bool
     */
    protected $cookieSecure = self::COOKIE_SECURE_DEFAULT;

    /**
     * @var bool
     */
    protected $cookieHttpOnly = self::COOKIE_HTTP_ONLY_DEFAULT;

    /**
     * @var string
     */
    protected $cookieExpireDatetimeModifier = self::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT;

    /**
     * @var string
     */
    protected $switchParam = self::SWITCH_PARAM_DEFAULT;

    /**
     * @var array
     */
    protected $redirectConfig;

    /**
     * Constructor
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack = null)
    {
        if (!$requestStack || !$this->request = $requestStack->getMasterRequest()) {
            $this->viewType = self::VIEW_NOT_MOBILE;

            return;
        }

        if ($this->request->query->has($this->switchParam)) {
            $this->viewType = $this->request->query->get($this->switchParam);
        } elseif ($this->request->cookies->has($this->cookieKey)) {
            $this->viewType = $this->request->cookies->get($this->cookieKey);
        }

        $this->requestedViewType = $this->viewType;
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
     * Gets the view type that has explicitly been requested either by switch param, or by cookie.
     *
     * @return string The requested view type or null if no view type has been explicitly requested.
     */
    public function getRequestedViewType()
    {
        return $this->requestedViewType;
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
        return $this->request && $this->request->query->has($this->switchParam);
    }

    /**
     * Sets the view type.
     *
     * @param string $view
     */
    public function setView($view)
    {
        $this->viewType = $view;
    }

    /**
     * Sets the full (desktop) view type.
     */
    public function setFullView()
    {
        $this->viewType = self::VIEW_FULL;
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
     * @return string|null
     */
    public function getSwitchParamValue()
    {
        if (!$this->request) {
            return null;
        }

        return $this->request->query->get($this->switchParam, self::VIEW_FULL);
    }

    /**
     * Getter of RedirectConfig.
     *
     * @return array
     */
    public function getRedirectConfig()
    {
        return $this->redirectConfig;
    }

    /**
     * Setter of RedirectConfig.
     *
     * @param array $redirectConfig
     */
    public function setRedirectConfig($redirectConfig)
    {
        $this->redirectConfig = $redirectConfig;
    }

    /**
     * Gets the RedirectResponse by switch param value.
     *
     * @param string $redirectUrl
     *
     * @return RedirectResponseWithCookie
     */
    public function getRedirectResponseBySwitchParam($redirectUrl)
    {
        switch ($this->getSwitchParamValue()) {
            case self::VIEW_MOBILE:
                $viewType = self::VIEW_MOBILE;
                break;
            case self::VIEW_TABLET:
                $viewType = self::VIEW_TABLET;

                if (isset($this->redirectConfig['detect_tablet_as_mobile']) && $this->redirectConfig['detect_tablet_as_mobile'] === true) {
                    $viewType = self::VIEW_MOBILE;
                }
                break;
            default:
                $viewType = self::VIEW_FULL;
        }

        return new RedirectResponseWithCookie($redirectUrl, $this->getStatusCode($viewType), $this->createCookie($viewType));
    }

    /**
     * Modifies the Response for the specified device view.
     *
     * @param string   $view     The device view for which the response should be modified.
     * @param Response $response
     *
     * @return Response
     */
    public function modifyResponse($view, Response $response)
    {
        $response->headers->setCookie($this->createCookie($view));

        return $response;
    }

    /**
     * Gets the RedirectResponse for the specified device view.
     *
     * @param string $view       The device view for which we want the RedirectResponse.
     * @param string $host       Uri host
     * @param int    $statusCode Status code
     *
     * @return RedirectResponseWithCookie
     */
    public function getRedirectResponse($view, $host, $statusCode)
    {
        return new RedirectResponseWithCookie($host, $statusCode, $this->createCookie($view));
    }

    /**
     * Setter of CookieKey
     *
     * @param string $cookieKey
     */
    public function setCookieKey($cookieKey)
    {
        $this->cookieKey = $cookieKey;
    }

    /**
     * Getter of CookieKey
     *
     * @return string
     */
    public function getCookieKey()
    {
        return $this->cookieKey;
    }

    /**
     * Getter of CookiePath.
     *
     * @return string
     */
    public function getCookiePath()
    {
        return $this->cookiePath;
    }

    /**
     * Setter of CookiePath.
     *
     * @param string $cookiePath
     */
    public function setCookiePath($cookiePath)
    {
        $this->cookiePath = $cookiePath;
    }

    /**
     * Getter of CookieDomain.
     *
     * @return string
     */
    public function getCookieDomain()
    {
        return $this->cookieDomain;
    }

    /**
     * Setter of CookieDomain.
     *
     * @param string $cookieDomain
     */
    public function setCookieDomain($cookieDomain)
    {
        $this->cookieDomain = $cookieDomain;
    }

    /**
     * Is the cookie secure.
     *
     * @return bool
     */
    public function isCookieSecure()
    {
        return $this->cookieSecure;
    }

    /**
     * Setter of CookieSecure.
     *
     * @param bool $cookieSecure
     */
    public function setCookieSecure($cookieSecure)
    {
        $this->cookieSecure = $cookieSecure;
    }

    /**
     * Is the cookie http only.
     *
     * @return bool
     */
    public function isCookieHttpOnly()
    {
        return $this->cookieHttpOnly;
    }

    /**
     * Setter of CookieHttpOnly.
     *
     * @param bool $cookieHttpOnly
     */
    public function setCookieHttpOnly($cookieHttpOnly)
    {
        $this->cookieHttpOnly = $cookieHttpOnly;
    }

    /**
     * Setter of SwitchParam.
     *
     * @param string $switchParam
     */
    public function setSwitchParam($switchParam)
    {
        $this->switchParam = $switchParam;
    }

    /**
     * Getter of SwitchParam
     *
     * @return string
     */
    public function getSwitchParam()
    {
        return $this->switchParam;
    }

    /**
     * @param string $cookieExpireDatetimeModifier
     */
    public function setCookieExpireDatetimeModifier($cookieExpireDatetimeModifier)
    {
        $this->cookieExpireDatetimeModifier = $cookieExpireDatetimeModifier;
    }

    /**
     * @return string
     */
    public function getCookieExpireDatetimeModifier()
    {
        return $this->cookieExpireDatetimeModifier;
    }

    /**
     * Create the Cookie object
     *
     * @param string $value
     *
     * @return Cookie
     */
    protected function createCookie($value)
    {
        try {
            $expire = new \Datetime($this->getCookieExpireDatetimeModifier());
        } catch (\Exception $e) {
            $expire = new \Datetime(self::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT);
        }

        return new Cookie(
            $this->getCookieKey(),
            $value,
            $expire,
            $this->getCookiePath(),
            $this->getCookieDomain(),
            $this->isCookieSecure(),
            $this->isCookieHttpOnly()
        );
    }

    /**
     * @param string $view
     *
     * @return integer
     */
    protected function getStatusCode($view)
    {
        if (isset($this->redirectConfig[$view]['status_code'])) {
            return $this->redirectConfig[$view]['status_code'];
        }

        return 302;
    }
}
