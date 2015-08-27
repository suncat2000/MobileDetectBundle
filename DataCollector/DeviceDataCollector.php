<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SunCat\MobileDetectBundle\DataCollector;

use SunCat\MobileDetectBundle\Helper\DeviceView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * DeviceDataCollector class
 *
 * @author Jonas HAOUZI <haouzijonas@gmail.com>
 *
 */
class DeviceDataCollector extends DataCollector
{
    /**
     * @var DeviceView
     */
    protected $deviceView;

    /**
     * DeviceDataCollector constructor.
     *
     * @param DeviceView $deviceView Device View Detector
     */
    public function __construct(DeviceView $deviceView)
    {
        $this->deviceView = $deviceView;
    }

    /**
     * Collects data for the given Request and Response.
     *
     * @param Request    $request   A Request instance
     * @param Response   $response  A Response instance
     * @param \Exception $exception An Exception instance
     *
     * @api
     */
    public function collect(
        Request $request,
        Response $response,
        \Exception $exception = null
    ) {
        $this->data['currentView'] = $this->deviceView->getViewType();
        $this->data['views'] = array(
            array(
                'label' => 'Full',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceView::VIEW_FULL
                ),
                'isCurrent' => $this->deviceView->isFullView()
            ),
            array(
                'label' => 'Tablet',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceView::VIEW_TABLET
                ),
                'isCurrent' => $this->deviceView->isTabletView()
            ),
            array(
                'label' => 'Mobile',
                'link' => $this->generateSwitchLink(
                    $request,
                    DeviceView::VIEW_MOBILE
                ),
                'isCurrent' => $this->deviceView->isMobileView()
            ),
        );
    }

    /**
     * @param Request $request
     * @param         $view
     *
     * @return string
     */
    private function generateSwitchLink(
        Request $request,
        $view
    ) {
        $requestSwitchView = $request->duplicate();
        $requestSwitchView->query->set('device_view', $view);
        $requestSwitchView->server->set(
            'QUERY_STRING',
            Request::normalizeQueryString(
                http_build_query($requestSwitchView->query->all(), null, '&')
            )
        );

        return $requestSwitchView->getUri();
    }

    /**
     * @return string
     */
    public function getCurrentView()
    {
        return $this->data['currentView'];
    }

    /**
     * @return array
     */
    public function getViews()
    {
        return $this->data['views'];
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     *
     * @api
     */
    public function getName()
    {
        return 'device.collector';
    }
}
