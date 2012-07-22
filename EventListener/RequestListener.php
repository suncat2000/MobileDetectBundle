<?php
namespace SunCat\MobileDetectBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use SunCat\MobileDetectBundle\Helper\DeviceView;

/**
 * Request listener 
 */
class RequestListener
{
    protected $mobileDetector;
    protected $deviceView;
    protected $request;

    protected $redirectConf;
    protected $needModifyResponse = false;
    protected $modifyResponseClosure;

    /**
     * Constructor
     * @param MobileDetector $mobileDetector Mobile detect object
     * @param Request        $request        Request object
     * @param type           $redirectConf   Config redirect
     */
    public function __construct(MobileDetector $mobileDetector, Request $request, $redirectConf)
    {
        // Mobile_Detect class & Request
        $this->mobileDetector = $mobileDetector;
        $this->deviceView = $this->mobileDetector->getDeviceView();
        $this->request = $request;

        // Configs mobile & tablet
        $this->redirectConf = $redirectConf;
        $this->currentHost = $this->request->getScheme() . '://' . $this->request->getHost();
    }

    /**
     * Handle Request
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
            $event->setResponse($this->getTabletRedirectResponse());

            return;
        }

        // Redirect to mobile version and set 'mobile' device view (in cookie)
        if ($this->hasMobileRedirect()) {
            $event->setResponse($this->getMobileRedirectResponse());

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
     * @return boolean 
     */
    private function hasMobileRedirect()
    {
        if (!$this->redirectConf['mobile']['is_enabled']) {
            return false;
        }

        $isMobile = $this->mobileDetector->isMobile();
        $isMobileHost = ($this->currentHost === $this->redirectConf['mobile']['host']);

        if ($isMobile && !$isMobileHost) {
            return true;
        }

        return false;
    }

    /**
     * Detect tablet redirect
     * @return boolean 
     */
    private function hasTabletRedirect()
    {
        if (!$this->redirectConf['tablet']['is_enabled']) {
            return false;
        }

        $isTablet = $this->mobileDetector->isTablet();
        $isTabletHost = ($this->currentHost === $this->redirectConf['tablet']['host']);

        if ($isTablet && !$isTabletHost) {
            return true;
        }

        return false;
    }

    /**
     * If need modify Response for tablet
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse 
     */
    private function getRedirectResponseBySwitchParam()
    {
        return $this->deviceView->getRedirectResponseBySwitchParam($this->currentHost);
    }

    /**
     * Get mobile RedirectResponse
     * @return \Symfony\Component\HttpFoundation\RedirectResponse 
     */
    private function getMobileRedirectResponse()
    {
        return $this->deviceView->getMobileRedirectResponse(
            $this->redirectConf['mobile']['host'],
            $this->redirectConf['mobile']['status_code']
        );
    }

    /**
     * Get tablet RedirectResponse
     * @return \Symfony\Component\HttpFoundation\RedirectResponse 
     */
    private function getTabletRedirectResponse()
    {
        return $this->deviceView->getTabletRedirectResponse(
            $this->redirectConf['tablet']['host'],
            $this->redirectConf['tablet']['status_code']
        );
    }
}



