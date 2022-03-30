<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MobileDetectBundle\Helper;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class DeviceView
{
    public const VIEW_MOBILE = 'mobile';
    public const VIEW_TABLET = 'tablet';
    public const VIEW_FULL = 'full';
    public const VIEW_NOT_MOBILE = 'not_mobile';

    public const COOKIE_KEY_DEFAULT = 'device_view';
    public const COOKIE_PATH_DEFAULT = '/';
    public const COOKIE_DOMAIN_DEFAULT = '';
    public const COOKIE_SECURE_DEFAULT = false;
    public const COOKIE_HTTP_ONLY_DEFAULT = true;
    public const COOKIE_RAW_DEFAULT = false;
    public const COOKIE_SAMESITE_DEFAULT = null;
    public const COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT = '1 month';
    public const SWITCH_PARAM_DEFAULT = 'device_view';

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
     * @var bool
     */
    protected $cookieRaw = self::COOKIE_RAW_DEFAULT;

    /**
     * @var string|null
     */
    protected $cookieSamesite = self::COOKIE_SAMESITE_DEFAULT;

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

    public function __construct(RequestStack $requestStack = null)
    {
        $methodName = method_exists(RequestStack::class, 'getMainRequest') ? 'getMainRequest' : 'getMasterRequest';
        if (!$requestStack || !$this->request = $requestStack->{$methodName}()) {
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
     */
    public function getViewType(): ?string
    {
        return $this->viewType;
    }

    /**
     * Gets the view type that has explicitly been requested either by switch param, or by cookie.
     *
     * @return string the requested view type or null if no view type has been explicitly requested
     */
    public function getRequestedViewType(): ?string
    {
        return $this->requestedViewType;
    }

    /**
     * Is the device in full view.
     */
    public function isFullView(): bool
    {
        return self::VIEW_FULL === $this->viewType;
    }

    /**
     * Is the device a tablet view type.
     */
    public function isTabletView(): bool
    {
        return self::VIEW_TABLET === $this->viewType;
    }

    /**
     * Is the device a mobile view type.
     */
    public function isMobileView(): bool
    {
        return self::VIEW_MOBILE === $this->viewType;
    }

    /**
     * Is not the device a mobile view type (PC, Mac, etc.).
     */
    public function isNotMobileView(): bool
    {
        return self::VIEW_NOT_MOBILE === $this->viewType;
    }

    /**
     * Has the Request the switch param in the query string (GET header).
     */
    public function hasSwitchParam(): bool
    {
        return $this->request && $this->request->query->has($this->switchParam);
    }

    public function setView(string $view): void
    {
        $this->viewType = $view;
    }

    /**
     * Sets the full (desktop) view type.
     */
    public function setFullView(): void
    {
        $this->viewType = self::VIEW_FULL;
    }

    /**
     * Sets the tablet view type.
     */
    public function setTabletView(): void
    {
        $this->viewType = self::VIEW_TABLET;
    }

    /**
     * Sets the mobile view type.
     */
    public function setMobileView(): void
    {
        $this->viewType = self::VIEW_MOBILE;
    }

    /**
     * Sets the not mobile view type.
     */
    public function setNotMobileView(): void
    {
        $this->viewType = self::VIEW_NOT_MOBILE;
    }

    /**
     * Gets the switch param value from the query string (GET header).
     */
    public function getSwitchParamValue(): ?string
    {
        if (!$this->request) {
            return null;
        }

        return $this->request->query->get($this->switchParam, self::VIEW_FULL);
    }

    /**
     * Getter of RedirectConfig.
     */
    public function getRedirectConfig(): array
    {
        return $this->redirectConfig;
    }

    /**
     * Setter of RedirectConfig.
     */
    public function setRedirectConfig(array $redirectConfig): void
    {
        $this->redirectConfig = $redirectConfig;
    }

    /**
     * Gets the RedirectResponse by switch param value.
     */
    public function getRedirectResponseBySwitchParam(string $redirectUrl): RedirectResponseWithCookie
    {
        switch ($this->getSwitchParamValue()) {
            case self::VIEW_MOBILE:
                $viewType = self::VIEW_MOBILE;
                break;
            case self::VIEW_TABLET:
                $viewType = self::VIEW_TABLET;

                if (isset($this->redirectConfig['detect_tablet_as_mobile']) && true === $this->redirectConfig['detect_tablet_as_mobile']) {
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
     * @param string $view the device view for which the response should be modified
     */
    public function modifyResponse(string $view, Response $response): Response
    {
        $response->headers->setCookie($this->createCookie($view));

        return $response;
    }

    /**
     * Gets the RedirectResponse for the specified device view.
     *
     * @param string $view       the device view for which we want the RedirectResponse
     * @param string $host       Uri host
     * @param int    $statusCode Status code
     */
    public function getRedirectResponse(string $view, string $host, int $statusCode): RedirectResponseWithCookie
    {
        return new RedirectResponseWithCookie($host, $statusCode, $this->createCookie($view));
    }

    /**
     * Setter of CookieKey.
     */
    public function setCookieKey(string $cookieKey): void
    {
        $this->cookieKey = $cookieKey;
    }

    /**
     * Getter of CookieKey.
     */
    public function getCookieKey(): string
    {
        return $this->cookieKey;
    }

    /**
     * Getter of CookiePath.
     */
    public function getCookiePath(): string
    {
        return $this->cookiePath;
    }

    /**
     * Setter of CookiePath.
     */
    public function setCookiePath(string $cookiePath): void
    {
        $this->cookiePath = $cookiePath;
    }

    /**
     * Getter of CookieDomain.
     */
    public function getCookieDomain(): string
    {
        return $this->cookieDomain;
    }

    /**
     * Setter of CookieDomain.
     */
    public function setCookieDomain(string $cookieDomain): void
    {
        $this->cookieDomain = $cookieDomain;
    }

    /**
     * Is the cookie secure.
     */
    public function isCookieSecure(): bool
    {
        return $this->cookieSecure;
    }

    /**
     * Setter of CookieSecure.
     */
    public function setCookieSecure(bool $cookieSecure)
    {
        $this->cookieSecure = $cookieSecure;
    }

    /**
     * Is the cookie http only.
     */
    public function isCookieHttpOnly(): bool
    {
        return $this->cookieHttpOnly;
    }

    /**
     * Setter of CookieHttpOnly.
     */
    public function setCookieHttpOnly(bool $cookieHttpOnly): void
    {
        $this->cookieHttpOnly = $cookieHttpOnly;
    }

    /**
     * Is the cookie raw.
     */
    public function isCookieRaw(): bool
    {
        return $this->cookieRaw;
    }

    /**
     * Setter of CookieRaw.
     */
    public function setCookieRaw(bool $cookieRaw): void
    {
        $this->cookieRaw = $cookieRaw;
    }

    /**
     * Getter of CookieSamesite.
     */
    public function getCookieSamesite(): ?string
    {
        return $this->cookieSamesite;
    }

    /**
     * Setter of CookieSamesite.
     */
    public function setCookieSamesite(?string $cookieSamesite): void
    {
        $this->cookieSamesite = $cookieSamesite;
    }

    /**
     * Setter of SwitchParam.
     */
    public function setSwitchParam(string $switchParam): void
    {
        $this->switchParam = $switchParam;
    }

    /**
     * Getter of SwitchParam.
     */
    public function getSwitchParam(): string
    {
        return $this->switchParam;
    }

    public function setCookieExpireDatetimeModifier(string $cookieExpireDatetimeModifier): void
    {
        $this->cookieExpireDatetimeModifier = $cookieExpireDatetimeModifier;
    }

    public function getCookieExpireDatetimeModifier(): string
    {
        return $this->cookieExpireDatetimeModifier;
    }

    /**
     * Create the Cookie object.
     */
    protected function createCookie(string $value): Cookie
    {
        try {
            $expire = new \DateTime($this->getCookieExpireDatetimeModifier());
        } catch (\Exception $e) {
            $expire = new \DateTime(self::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT);
        }

        return new Cookie(
            $this->getCookieKey(),
            $value,
            $expire,
            $this->getCookiePath(),
            $this->getCookieDomain(),
            $this->isCookieSecure(),
            $this->isCookieHttpOnly(),
            $this->isCookieRaw(),
            $this->getCookieSamesite()
        );
    }

    protected function getStatusCode(string $view): int
    {
        if (isset($this->redirectConfig[$view]['status_code'])) {
            return $this->redirectConfig[$view]['status_code'];
        }

        return Response::HTTP_FOUND;
    }
}
