<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SunCat\MobileDetectBundle\EventListener;

use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use SunCat\MobileDetectBundle\Helper\DeviceView;
use SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * Request and response listener
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 * @author HenriVesala <henri.vesala@gmail.com>
 */
class RequestResponseListener
{
    const REDIRECT                  = 'redirect';
    const NO_REDIRECT               = 'no_redirect';
    const REDIRECT_WITHOUT_PATH     = 'redirect_without_path';

    const MOBILE    = 'mobile';
    const TABLET    = 'tablet';
    const FULL      = 'full';

    /**
     * @var MobileDetector
     */
    protected $mobileDetector;

    /**
     * @var DeviceView
     */
    protected $deviceView;

    /**
     * @var array
     */
    protected $redirectConf;

    /**
     * @var bool
     */
    protected $isFullPath;

    /**
     * @var bool
     */
    protected $needModifyResponse = false;

    /**
     * @var \Closure
     */
    protected $modifyResponseClosure;

    /**
     * RequestResponseListener constructor.
     *
     * @param MobileDetector  $mobileDetector
     * @param DeviceView      $deviceView
     * @param RouterInterface $router
     * @param array           $redirectConf
     * @param bool            $fullPath
     */
    public function __construct(
        MobileDetector $mobileDetector,
        DeviceView $deviceView,
        RouterInterface $router,
        array $redirectConf,
        $fullPath = true
    ) {
        $this->mobileDetector = $mobileDetector;
        $this->deviceView = $deviceView;
        $this->router = $router;

        // Configs mobile & tablet
        $this->redirectConf = $redirectConf;
        $this->isFullPath = $fullPath;
    }

    /**
     * Handles the Request
     *
     * @param GetResponseEvent $event
     *
     * @return null
     */
    public function handleRequest(GetResponseEvent $event)
    {
        // only handle master request, do not handle sub request like esi includes
        // If the device view is "not the mobile view" (e.g. we're not in the request context)
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST || $this->deviceView->isNotMobileView()) {
            return;
        }

        $request = $event->getRequest();
        $this->mobileDetector->setUserAgent($request->headers->get('user-agent'));

        // Sets the flag for the response handled by the GET switch param and the type of the view.
        if ($this->deviceView->hasSwitchParam()) {
            $event->setResponse($this->getRedirectResponseBySwitchParam($request));

            return;
        }

        // If neither the SwitchParam nor the cookie are set, detect the view...
        $cookieIsSet = $this->deviceView->getRequestedViewType() !== null;
        if (!$cookieIsSet) {
            if ($this->redirectConf['detect_tablet_as_mobile'] === false && $this->mobileDetector->isTablet()) {
                $this->deviceView->setTabletView();
            } elseif ($this->mobileDetector->isMobile()) {
                $this->deviceView->setMobileView();
            } else {
                $this->deviceView->setFullView();
            }
        }

        // Check if we must redirect to the target view and do so if needed
        if ($this->mustRedirect($request, $this->deviceView->getViewType())) {
            if (($response = $this->getRedirectResponse($request, $this->deviceView->getViewType()))) {
                $event->setResponse($response);
            }

            return;
        }

        // No need to redirect

        // We don't need to modify _every_ response: once the cookie is set,
        // save bandwith and CPU cycles by just letting it expire someday.
        if ($cookieIsSet) {
            return;
        }

        // Sets the flag for the response handler and prepares the modification closure
        $this->needModifyResponse = true;
        $this->prepareResponseModification($this->deviceView->getViewType());
    }

    /**
     * Will this request listener modify the response? This flag will be set during the "handleRequest" phase.
     * Made public for testability.
     *
     * @return boolean True if the response needs to be modified.
     */
    public function needsResponseModification()
    {
        return $this->needModifyResponse;
    }

    /**
     * Handles the Response
     *
     * @param FilterResponseEvent $event
     *
     * @return null
     */
    public function handleResponse(FilterResponseEvent $event)
    {
        if ($this->needModifyResponse && $this->modifyResponseClosure instanceof \Closure) {
            $modifyClosure = $this->modifyResponseClosure;
            $event->setResponse($modifyClosure($this->deviceView, $event));

            return;
        }
    }

    /**
     * Do we have to redirect?
     *
     * @param Request $request
     * @param string  $view    For which view should be check?
     *
     * @return boolean
     */
    protected function mustRedirect(Request $request, $view)
    {
        if (!isset($this->redirectConf[$view]) ||
            !$this->redirectConf[$view]['is_enabled'] ||
            ($this->getRoutingOption($request->get('_route'), $view) === self::NO_REDIRECT)
        ) {
            return false;
        }

        $isHost = ($this->getCurrentHost($request) === $this->redirectConf[$view]['host']);

        if (!$isHost) {
            return true;
        }

        return false;
    }

    /**
     * Prepares the response modification which will take place after the controller logic has been executed.
     *
     * @param string $view The view for which to prepare the response modification.
     *
     * @return boolean
     */
    protected function prepareResponseModification($view)
    {
        $this->modifyResponseClosure = function (DeviceView $deviceView, FilterResponseEvent $event) use ($view) {
            return $deviceView->modifyResponse($view, $event->getResponse());
        };
    }

    /**
     * Gets the RedirectResponse by switch param.
     *
     * @param Request $request
     *
     * @return RedirectResponseWithCookie
     */
    protected function getRedirectResponseBySwitchParam(Request $request)
    {
        if ($this->mustRedirect($request, $this->deviceView->getViewType())) {
            // Avoid unnecessary redirects: if we need to redirect to another view,
            // do it in one response while setting the cookie.
            $redirectUrl = $this->getRedirectUrl($request, $this->deviceView->getViewType());
        } else {
            if (true === $this->isFullPath) {
                $redirectUrl = $request->getUriForPath($request->getPathInfo());
                $queryParams = $request->query->all();
                if (array_key_exists($this->deviceView->getSwitchParam(), $queryParams)) {
                    unset($queryParams[$this->deviceView->getSwitchParam()]);
                }
                if (sizeof($queryParams) > 0) {
                    $redirectUrl .= '?'.Request::normalizeQueryString(http_build_query($queryParams, null, '&'));
                }
            } else {
                $redirectUrl = $this->getCurrentHost($request);
            }
        }

        return $this->deviceView->getRedirectResponseBySwitchParam($redirectUrl);
    }

    /**
     * Gets the RedirectResponse for the specified view.
     *
     * @param Request $request
     * @param string  $view    The view for which we want the RedirectResponse.
     *
     * @return RedirectResponse|null
     */
    protected function getRedirectResponse(Request $request, $view)
    {
        if (($host = $this->getRedirectUrl($request, $view))) {
            return $this->deviceView->getRedirectResponse(
                $view,
                $host,
                $this->redirectConf[$view]['status_code']
            );
        }

        return null;
    }

    /**
     * Gets the redirect url.
     *
     * @param Request $request
     * @param string  $platform
     *
     * @return string|null
     */
    protected function getRedirectUrl(Request $request, $platform)
    {
        if (($routingOption = $this->getRoutingOption($request->get('_route'), $platform))) {
            if (self::REDIRECT === $routingOption) {
                // Make sure to hint at the device override, otherwise infinite loop
                // redirection may occur if different device views are hosted on
                // different domains (since the cookie can't be shared across domains)
                $queryParams = $request->query->all();
                $queryParams[$this->deviceView->getSwitchParam()] = $platform;

                return rtrim($this->redirectConf[$platform]['host'], '/').$request->getPathInfo().'?'.Request::normalizeQueryString(http_build_query($queryParams, null, '&'));
            } elseif (self::REDIRECT_WITHOUT_PATH === $routingOption) {
                // Make sure to hint at the device override, otherwise infinite loop
                // redirections may occur if different device views are hosted on
                // different domains (since the cookie can't be shared across domains)
                return $this->redirectConf[$platform]['host'].'?'.$this->deviceView->getSwitchParam().'='.$platform;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Gets named option from current route.
     *
     * @param string $routeName
     * @param string $optionName
     *
     * @return string|null
     */
    protected function getRoutingOption($routeName, $optionName)
    {
        $option = null;
        $route = $this->router->getRouteCollection()->get($routeName);

        if ($route instanceof Route) {
            $option = $route->getOption($optionName);
        }

        if (!$option && isset($this->redirectConf[$optionName])) {
            $option = $this->redirectConf[$optionName]['action'];
        }

        if (in_array($option, array(self::REDIRECT, self::REDIRECT_WITHOUT_PATH, self::NO_REDIRECT))) {
            return $option;
        }

        return null;
    }

    /**
     * Gets the current host.
     *
     * @param Request $request
     *
     * @return string
     */
    protected function getCurrentHost(Request $request)
    {
        return $request->getScheme().'://'.$request->getHost();
    }
}
