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

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use SunCat\MobileDetectBundle\Helper\DeviceView;
use Doctrine\Tests\Common\Annotations\True;

/**
 * Request listener
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 * @author HenriVesala <henri.vesala@gmail.com>
 */
class RequestListener
{
    CONST REDIRECT = 'redirect';
    CONST NO_REDIRECT = 'no_redirect';
    CONST REDIRECT_WITHOUT_PATH = 'redirect_without_path';

    CONST MOBILE    = 'mobile';
    CONST TABLET    = 'tablet';
    CONST FULL      = 'full';

    protected $container;
    /**
     * @var MobileDetector
     */
    protected $mobileDetector;
    /**
     * @var DeviceView
     */
    protected $deviceView;

    protected $redirectConf;
    protected $isFullPath;

    protected $needModifyResponse = false;
    protected $modifyResponseClosure;

    /**
     * Constructor
     *
     * @param Container $serviceContainer Service container
     * @param array     $redirectConf     Config redirect
     * @param boolean   $fullPath         Full path or front page
     */
    public function __construct(Container $serviceContainer, array $redirectConf,  $fullPath = true)
    {
        $this->container = $serviceContainer;
        $this->mobileDetector = $serviceContainer->get('mobile_detect.mobile_detector');
        $this->deviceView = $serviceContainer->get('mobile_detect.device_view');

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

        $this->mobileDetector->setUserAgent($event->getRequest()->headers->get('user-agent'));

        // Sets the flag for the response handled by the GET switch param and the type of the view.
        if ($this->deviceView->hasSwitchParam()) {
            $event->setResponse($this->getRedirectResponseBySwitchParam());
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
        if ($this->mustRedirect($this->deviceView->getViewType())) {
            if (($response = $this->getRedirectResponse($this->deviceView->getViewType()))) {
                $event->setResponse($response);
            }
            return;
        }

        // No need to redirect

        // We don't need to modify _every_ response: once the cookie is set,
        // save badwith and CPU cycles by just letting it expire someday.
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
     * @param string $view For which view should be check?
     * 
     * @return boolean
     */
    protected function mustRedirect($view)
    {
        if (!isset($this->redirectConf[$view]) || !$this->redirectConf[$view]['is_enabled'] || 
            ($this->getRoutingOption($view) === self::NO_REDIRECT)) {

            return false;
        }

        $isHost = ($this->getCurrentHost() === $this->redirectConf[$view]['host']);

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
        $this->modifyResponseClosure = function($deviceView, $event) use ($view) {
            return $deviceView->modifyResponse($view, $event->getResponse());
        };
    }

    /**
     * If a modified Response for non-mobile devices is needed.
     *
     * @return boolean
     */
    protected function needNotMobileResponseModify()
    {
        if ((null === $this->deviceView->getViewType() || $this->deviceView->isNotMobileView())) {
            $this->modifyResponseClosure = function($deviceView, $event) {
                return $deviceView->modifyNotMobileResponse($event->getResponse());
            };

            return true;
        }

        return false;
    }

    /**
     * Gets the RedirectResponse by switch param.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function getRedirectResponseBySwitchParam()
    {
        if ($this->mustRedirect($this->deviceView->getViewType())) {
            // Avoid unnecessary redirects: if we need to redirect to another view,
            // do it in one response while setting the cookie.
            $redirectUrl = $this->getRedirectUrl($this->deviceView->getViewType());
        } else {
            if (true === $this->isFullPath) {
                /* @var $request Request */
                $request = $this->container->get('request');
                $redirectUrl = $request->getUriForPath($request->getPathInfo());
                $queryParams = $request->query->all();
                if (array_key_exists('device_view', $queryParams)) {
                    unset($queryParams['device_view']);
                }
                if(sizeof($queryParams) > 0) {
                    $redirectUrl .= '?'. Request::normalizeQueryString(http_build_query($queryParams));
                }
            } else {
                $redirectUrl = $this->getCurrentHost();
            }
        }

        return $this->deviceView->getRedirectResponseBySwitchParam($redirectUrl);
    }

    /**
     * Gets the RedirectResponse for the specified view.
     * 
     * @param string $view The view for which we want the RedirectResponse.
     * 
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function getRedirectResponse($view)
    {
        if (($host = $this->getRedirectUrl($view))) {
            return $this->deviceView->getRedirectResponse(
                $view,
                $host,
                $this->redirectConf[$view]['status_code']
            );
        }
    }

    /**
     * Gets the redirect url.
     *
     * @param string $platform
     *
     * @return string
     */
    protected function getRedirectUrl($platform)
    {
        if (($routingOption = $this->getRoutingOption($platform))) {
            $redirectUrl = null;
            if (self::REDIRECT === $routingOption) {
                // Make sure to hint at the device override, otherwise infinite loop
                // redirections may occur if different device views are hosted on
                // different domains (since the cookie can't be shared across domains)
                $queryParams = $this->container->get('request')->query->all();
                $queryParams[DeviceView::SWITCH_PARAM] = $platform;

                return $this->redirectConf[$platform]['host'] . $this->container->get('request')->getRequestUri() . '?' . Request::normalizeQueryString(http_build_query($queryParams));
            } elseif (self::REDIRECT_WITHOUT_PATH === $routingOption) {
                // Make sure to hint at the device override, otherwise infinite loop
                // redirections may occur if different device views are hosted on
                // different domains (since the cookie can't be shared across domains)
                return $this->redirectConf[$platform]['host'] . '?' . DeviceView::SWITCH_PARAM . '=' . $platform;
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
     * @param string $name
     *
     * @return string|null
     */
    protected function getRoutingOption($name)
    {
        $option = null;
        $route = $this
                    ->container
                    ->get('router')
                    ->getRouteCollection()
                    ->get($this->container->get('request')->get('_route'))
                ;

        if ($route instanceof Route) {
            $option = $route->getOption($name);
        }

        if (!$option && isset($this->redirectConf[$name])) {
            $option = $this->redirectConf[$name]['action'];
        }

        if (in_array($option, array(self::REDIRECT, self::REDIRECT_WITHOUT_PATH, self::NO_REDIRECT))) {
            return $option;
        }

        return null;
    }

    /**
     * Gets the current host.
     *
     * @return string
     */
    protected function getCurrentHost()
    {
        $request = $this->container->get('request');

        return $request->getScheme() . '://' . $request->getHost();
    }

}
