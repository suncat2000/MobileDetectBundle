<?php

namespace SunCat\MobileDetectBundle\DeviceDetector;

use Symfony\Component\HttpFoundation\Request;

use SunCat\MobileDetectBundle\Helper\DeviceView;

/**
 * MobileDetector class
 * 
 * Extend Mobile_Detect, use Request instead of $_SERVER for get HTTP headers
 */
class MobileDetector extends \Mobile_Detect
{
    private $request;
    private $deviceView;

    protected $isMobile = null;
    protected $isTablet = null;

            /**
     * Constructor
     * Use Request instead of $_SERVER
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        // Merge all rules together
        $this->detectionRules = array_merge(
            $this->phoneDevices,
            $this->tabletDevices,
            $this->operatingSystems,
            $this->userAgents
        );

        $this->userAgent = $request->headers->has('User-Agent') ? $request->headers->get('User-Agent') : null;
        $this->accept = $request->headers->has('Accept') ? $request->headers->get('Accept') : null;

        // If mobile view 'full' or 'not_mobile' disable initDetect
        $this->deviceView = new DeviceView($request);
        if (($this->deviceView->isFullView() || $this->deviceView->isNotMobileView()) &&
                !$this->deviceView->hasSwitchParam()) {
            return;
        }

        // Init detect devices
        $this->initDetect();
    }

    /**
     * Detect mobile devices
     */
    protected function initDetect()
    {
        if (
                $this->request->server->has('HTTP_X_WAP_PROFILE') ||
                $this->request->server->has('HTTP_X_WAP_CLIENTID') ||
                $this->request->server->has('HTTP_WAP_CONNECTION') ||
                $this->request->server->has('HTTP_PROFILE') ||
                // Reported by Nokia devices (eg. C3)
                $this->request->server->has('HTTP_X_OPERAMINI_PHONE_UA') ||
                $this->request->server->has('HTTP_X_NOKIA_IPADDRESS') ||
                $this->request->server->has('HTTP_X_NOKIA_GATEWAY_ID') ||
                $this->request->server->has('HTTP_X_ORANGE_ID') ||
                $this->request->server->has('HTTP_X_VODAFONE_3GPDPCONTEXT') ||
                $this->request->server->has('HTTP_X_HUAWEI_USERID') ||
                // Reported by Windows Smartphones
                $this->request->server->has('HTTP_UA_OS') ||
                // Seen this on a HTC
                ($this->request->server->has('HTTP_UA_CPU') && $this->request->server->get('HTTP_UA_CPU') == 'ARM')
        ) {
            $this->isMobile = true;
        } elseif (!empty($this->accept) && (strpos($this->accept, 'text/vnd.wap.wml') !== false || strpos($this->accept, 'application/vnd.wap.xhtml+xml') !== false)) {
            $this->isMobile = true;
        } else {
            $this->isMobile = $this->detectByRules();
        }
    }

    /**
     * Detect mobile devices by rules
     * @return boolean 
     */
    protected function detectByRules()
    {
        foreach ($this->detectionRules as $regex) {
            if (empty($regex)) {
                continue;
            }

            if (preg_match('/'.$regex.'/is', $this->userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get DeviceView Helper
     * @return SunCat\MobileDetectBundle\Helper\DeviceView 
     */
    public function getDeviceView()
    {
        return $this->deviceView;
    }

    /**
     * Is mobile device
     * @return boolean 
     */
    public function isMobile()
    {
        if (null !== $this->isMobile) {
            return $this->isMobile;
        }

        $this->initDetect();

        return $this->isMobile;
    }

    /**
     * Is tablet device
     * @return boolean 
     */
    public function isTablet()
    {
        if (null !== $this->isTablet) {
            return $this->isTablet;
        }

        $this->isTablet = parent::isTablet();

        return $this->isTablet;
    }
}
