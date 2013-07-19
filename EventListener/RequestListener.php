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

    protected $container;
    protected $mobileDetector;
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
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        // Sets the flag for the response handled by the GET switch param and the type of the view.
        if ($this->deviceView->hasSwitchParam()) {
            $event->setResponse($this->getRedirectResponseBySwitchParam());

            return;
        }

        // If the device view is either the the full view or not the mobile view
        if ($this->deviceView->isFullView() || $this->deviceView->isNotMobileView()) {
            return;
        }

        // Redirects to the tablet version and set the 'tablet' device view in a cookie.
        if ($this->hasTabletRedirect()) {
            if (($response = $this->getTabletRedirectResponse())) {
                $event->setResponse($response);
            }

            return;
        }

        // Redirects to the mobile version and set the 'mobile' device view in a cookie.
        if ($this->hasMobileRedirect()) {

            if (($response = $this->getMobileRedirectResponse())) {
                $event->setResponse($response);
            }

            return;
        }

        // No need to redirect

        // Sets the flag for the response handler
        $this->needModifyResponse = true;

        // Checking the need to modify the Response and set closure
        if ($this->needTabletResponseModify()) {
            $this->deviceView->setTabletView();

            return;
        }

        // Sets the closure modifier mobile Response
        if ($this->needMobileResponseModify()) {
            $this->deviceView->setMobileView();

            return;
        }

        // Sets the closure modifier not_mobile Response
        if ($this->needNotMobileResponseModify()) {
            $this->deviceView->setNotMobileView();

            return;
        }

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
     * Detects mobile redirections.
     *
     * @return boolean
     */
    protected function hasMobileRedirect()
    {

        if (!$this->redirectConf['mobile']['is_enabled']) {
            return false;
        }

        $isMobile = $this->mobileDetector->isMobile();
        $isMobileHost = ($this->getCurrentHost() === $this->redirectConf['mobile']['host']);

        if ($isMobile && !$isMobileHost && ($this->getRoutingOption(self::MOBILE) != self::NO_REDIRECT)) {
            return true;
        }

        return false;
    }

    /**
     * Detects tablet redirections.
     *
     * @return boolean
     */
    protected function hasTabletRedirect()
    {
        if (!$this->redirectConf['tablet']['is_enabled']) {
            return false;
        }

        $isTablet = $this->mobileDetector->isTablet();
        $isTabletHost = ($this->getCurrentHost() === $this->redirectConf['tablet']['host']);

        if ($isTablet && !$isTabletHost && ($this->getRoutingOption(self::TABLET) != self::NO_REDIRECT)) {
            return true;
        }

        return false;
    }

    /**
     * If a modified Response for tablet devices is needed
     *
     * @return boolean
     */
    protected function needTabletResponseModify()
    {

        if ((null === $this->deviceView->getViewType() || $this->deviceView->isTabletView()) &&
            $this->mobileDetector->isTablet()) {

            $this->modifyResponseClosure = function($deviceView, $event) {
                return $deviceView->modifyTabletResponse($event->getResponse());
            };

            return true;
        }

        return false;
    }

    /**
     * If a modified Response for mobile devices is needed
     *
     * @return boolean
     */
    protected function needMobileResponseModify()
    {
        if ((null === $this->deviceView->getViewType() || $this->deviceView->isMobileView()) &&
            $this->mobileDetector->isMobile()) {

            $this->modifyResponseClosure = function($deviceView, $event) {
                return $deviceView->modifyMobileResponse($event->getResponse());
            };

            return true;
        }

        return false;
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
        if (true === $this->isFullPath) {
            $request = $this->container->get('request');
            $redirectUrl = $request->getUriForPath($request->getPathInfo());
        } else {
            $redirectUrl = $this->getCurrentHost();
        }

        return $this->deviceView->getRedirectResponseBySwitchParam($redirectUrl);
    }

    /**
     * Gets the mobile RedirectResponse.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function getMobileRedirectResponse()
    {
        if (($host = $this->getRedirectUrl(self::MOBILE))) {
            return $this->deviceView->getMobileRedirectResponse(
                $host,
                $this->redirectConf[self::MOBILE]['status_code']
            );
        }
    }

    /**
     * Gets the tablet RedirectResponse.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function getTabletRedirectResponse()
    {
        if (($host = $this->getRedirectUrl(self::TABLET))) {
            return $this->deviceView->getTabletRedirectResponse(
                $host,
                $this->redirectConf[self::TABLET]['status_code']
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
            switch ($routingOption) {
                case self::REDIRECT:
                    return $this->redirectConf[$platform]['host'].$this->container->get('request')->getRequestUri();
                case self::REDIRECT_WITHOUT_PATH:
                    return  $this->redirectConf[$platform]['host'];
            }
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

        if (!$option) {
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
