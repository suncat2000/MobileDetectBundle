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
     * @param Container      $serviceContainer Service container
     * @param array          $redirectConf     Config redirect
     * @param boolean        $fullPath         Full path or front page
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
     * Handle Request
     * 
     * @param GetResponseEvent $event
     *
     * @return null
     */
    public function handleRequest(GetResponseEvent $event)
    {

        // Set flag for response handle by GET switch param and type of view
        if ($this->deviceView->hasSwitchParam()) {
            $event->setResponse($this->getRedirectResponseBySwitchParam());

            return;
        }

        // If full view or not mobile
        if ($this->deviceView->isFullView() || $this->deviceView->isNotMobileView()) {

            return;
        }

        // Redirect to tablet version and set 'tablet' device view (in cookie)
        if ($this->hasTabletRedirect()) {
            if (($response = $this->getTabletRedirectResponse())) {
                $event->setResponse($response);
            }

            return;
        }

        // Redirect to mobile version and set 'mobile' device view (in cookie)
        if ($this->hasMobileRedirect()) {

            if (($response = $this->getMobileRedirectResponse())) {
                $event->setResponse($response);
            }

            return;
        }

        // If not redirects
        // Set flag for response handle and generate closure
        $this->needModifyResponse = true;

        // Set closure modifier tablet Response
        if ($this->needTabletResponseModify()) {
            $this->deviceView->setTabletView();

            return;
        }

        // Set closure modifier mobile Response
        if ($this->needMobileResponseModify()) {
            $this->deviceView->setMobileView();

            return;
        }

        // Set closure modifier not_mobile Response
        if ($this->needNotMobileResponseModify()) {
            $this->deviceView->setNotMobileView();

            return;
        }

    }

    /**
     * Handle Response
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
     * Detect mobile redirect
     * 
     * @return boolean
     */
    private function hasMobileRedirect()
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
     * Detect tablet redirect
     * 
     * @return boolean
     */
    private function hasTabletRedirect()
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
     * If need modify Response for tablet
     * 
     * @return boolean
     */
    private function needTabletResponseModify()
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
     * If need modify Response for tablet
     * 
     * @return boolean
     */
    private function needMobileResponseModify()
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
     * If need modify Response for non mobile device
     * 
     * @return boolean
     */
    private function needNotMobileResponseModify()
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
     * Get RedirectResponse by switch param
     * 
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function getRedirectResponseBySwitchParam()
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
     * Get mobile RedirectResponse
     * 
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function getMobileRedirectResponse()
    {
        if (($host = $this->getRedirectUrl(self::MOBILE))) {
            return $this->deviceView->getMobileRedirectResponse(
                $host,
                $this->redirectConf[self::MOBILE]['status_code']
            );
        }
    }



    /**
     * Get tablet RedirectResponse
     * 
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function getTabletRedirectResponse()
    {
        if (($host = $this->getRedirectUrl(self::TABLET))) {
            return $this->deviceView->getTabletRedirectResponse(
                $host,
                $this->redirectConf[self::TABLET]['status_code']
            );
        }
    }

    /**
     * Get redirest url
     * 
     * @param string $platform
     * 
     * @return string
     */
    private function getRedirectUrl($platform)
    {
        if (($routingOption = $this->getRoutingOption($platform))) {
            switch($routingOption) {
                case self::REDIRECT:
                    return $this->redirectConf[$platform]['host'].$this->container->get('request')->getRequestUri();
                case self::REDIRECT_WITHOUT_PATH:
                    return  $this->redirectConf[$platform]['host'];
            }
        }
    }

    /**
     * Gets named option from current route
     *
     * @param string $name
     * 
     * @return string|null
     */
    private function getRoutingOption($name)
    {
        $route = $this
                    ->container
                    ->get('router')
                    ->getRouteCollection()
                    ->get($this->container->get('request')->get('_route'))
                ;
        
        $option = $route->getOption($name);

        if (!$option) {
            $option = $this->redirectConf[$name]['action'];
        }

        if (in_array($option, array(self::REDIRECT, self::REDIRECT_WITHOUT_PATH, self::NO_REDIRECT))) {
            return $option;
        }

        return false;
    }
    
    /**
     * @return string
     */
    private function getCurrentHost()
    {
        $request = $this->container->get('request');
        return $request->getScheme() . '://' . $request->getHost();
    }

}