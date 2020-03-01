<?php

declare(strict_types=1);

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MobileDetectBundle\Tests\DataCollector;

use MobileDetectBundle\DataCollector\DeviceDataCollector;
use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ServerBag;

/**
 * DeviceDataCollectorTest.
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 *
 * @internal
 * @coversNothing
 */
class DeviceDataCollectorTest extends TestCase
{
    private $mobileDetector;

    private $requestStack;

    private $request;

    private $response;

    public function setUp(): void
    {
        parent::setUp();

        $this->mobileDetector = $this->getMockBuilder('MobileDetectBundle\DeviceDetector\MobileDetector')->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $this->request->query = new ParameterBag();
        $this->request->cookies = new ParameterBag();
        $this->request->server = new ServerBag();
        $this->request->expects($this->any())->method('duplicate')->willReturn($this->request);

        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')->disableOriginalConstructor()->getMock();
        $this->requestStack->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($this->request)
        ;

        $this->response = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')->getMock();
    }

    /**
     * @test
     */
    public function collectCurrentViewMobileIsCurrent()
    {
        $redirectConfig['tablet'] = [
            'is_enabled' => true,
            'host' => 'http://testsite.com',
            'status_code' => 302,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->cookies = new ParameterBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->setRedirectConfig($redirectConfig);
        $deviceDataCollector->collect($this->request, $this->response);

        $currentView = $deviceDataCollector->getCurrentView();
        $views = $deviceDataCollector->getViews();

        $this->assertEquals($deviceView->getViewType(), $currentView);
        $this->assertEquals(DeviceView::VIEW_MOBILE, $currentView);
        $this->assertCount(3, $views);

        foreach ($views as $view) {
            $this->assertIsArray($view);
            $this->assertArrayHasKey('type', $view);
            $this->assertArrayHasKey('label', $view);
            $this->assertArrayHasKey('link', $view);
            $this->assertArrayHasKey('isCurrent', $view);
            $this->assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                $this->assertTrue($view['isCurrent']);
            }
        }
    }

    /**
     * @test
     */
    public function collectCurrentViewMobileCanUseTablet()
    {
        $redirectConfig['tablet'] = [
            'is_enabled' => true,
            'host' => 'http://testsite.com',
            'status_code' => 302,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->query = new ParameterBag(['param1' => 'value1']);
        $this->request->expects($this->any())->method('getHost')->willReturn('testsite.com');
        $this->request->expects($this->any())->method('getSchemeAndHttpHost')->willReturn('http://testsite.com');
        $this->request->expects($this->any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/path-info');
        $test = $this;
        $this->request->expects($this->any())->method('getQueryString')->willReturnCallback(function () use ($test) {
            $qs = Request::normalizeQueryString($test->request->server->get('QUERY_STRING'));

            return '' === $qs ? null : $qs;
        });
        $this->request->expects($this->any())->method('getUri')->willReturnCallback(function () use ($test) {
            if (null !== $qs = $test->request->getQueryString()) {
                $qs = '?'.$qs;
            }

            return $test->request->getSchemeAndHttpHost().$test->request->getBaseUrl().$test->request->getPathInfo().$qs;
        });
        $this->request->cookies = new ParameterBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->setRedirectConfig($redirectConfig);
        $deviceDataCollector->collect($this->request, $this->response);

        $currentView = $deviceDataCollector->getCurrentView();
        $views = $deviceDataCollector->getViews();

        $this->assertEquals($deviceView->getViewType(), $currentView);
        $this->assertEquals(DeviceView::VIEW_MOBILE, $currentView);
        $this->assertCount(3, $views);

        foreach ($views as $view) {
            $this->assertIsArray($view);
            $this->assertArrayHasKey('type', $view);
            $this->assertArrayHasKey('label', $view);
            $this->assertArrayHasKey('link', $view);
            $this->assertArrayHasKey('isCurrent', $view);
            $this->assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                $this->assertTrue($view['isCurrent']);
            }
            if (DeviceView::VIEW_TABLET === $view['type']) {
                $this->assertFalse($view['isCurrent']);
                $this->assertTrue($view['enabled']);
                $this->assertEquals(
                    sprintf(
                        'http://testsite.com/base-url/path-info?%s=%s&param1=value1',
                        $deviceView->getSwitchParam(),
                        DeviceView::VIEW_TABLET
                    ), $view['link']
                );
            }
        }
    }

    /**
     * @test
     */
    public function collectCurrentViewFullCanUseMobile()
    {
        $redirectConfig['tablet'] = [
            'is_enabled' => true,
            'host' => 'http://testsite.com',
            'status_code' => 302,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->query = new ParameterBag(['param1' => 'value1']);
        $this->request->expects($this->any())->method('getHost')->willReturn('testsite.com');
        $this->request->expects($this->any())->method('getSchemeAndHttpHost')->willReturn('http://testsite.com');
        $this->request->expects($this->any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/path-info');
        $test = $this;
        $this->request->expects($this->any())->method('getQueryString')->willReturnCallback(function () use ($test) {
            $qs = Request::normalizeQueryString($test->request->server->get('QUERY_STRING'));

            return '' === $qs ? null : $qs;
        });
        $this->request->expects($this->any())->method('getUri')->willReturnCallback(function () use ($test) {
            if (null !== $qs = $test->request->getQueryString()) {
                $qs = '?'.$qs;
            }

            return $test->request->getSchemeAndHttpHost().$test->request->getBaseUrl().$test->request->getPathInfo().$qs;
        });
        $this->request->cookies = new ParameterBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->setRedirectConfig($redirectConfig);
        $deviceDataCollector->collect($this->request, $this->response);

        $currentView = $deviceDataCollector->getCurrentView();
        $views = $deviceDataCollector->getViews();

        $this->assertEquals($deviceView->getViewType(), $currentView);
        $this->assertEquals(DeviceView::VIEW_FULL, $currentView);
        $this->assertCount(3, $views);

        foreach ($views as $view) {
            $this->assertIsArray($view);
            $this->assertArrayHasKey('type', $view);
            $this->assertArrayHasKey('label', $view);
            $this->assertArrayHasKey('link', $view);
            $this->assertArrayHasKey('isCurrent', $view);
            $this->assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_FULL === $view['type']) {
                $this->assertTrue($view['isCurrent']);
            }
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                $this->assertFalse($view['isCurrent']);
                $this->assertTrue($view['enabled']);
                $this->assertEquals(
                    sprintf(
                        'http://testsite.com/base-url/path-info?%s=%s&param1=value1',
                        $deviceView->getSwitchParam(),
                        DeviceView::VIEW_MOBILE
                    ), $view['link']
                );
            }
        }
    }

    /**
     * @test
     */
    public function collectCurrentViewFullCantUseMobile()
    {
        $redirectConfig['mobile'] = [
            'is_enabled' => true,
            'host' => 'http://m.testsite.com',
            'status_code' => 302,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->query = new ParameterBag(['param1' => 'value1']);
        $this->request->expects($this->any())->method('getHost')->willReturn('testsite.com');
        $this->request->expects($this->any())->method('getSchemeAndHttpHost')->willReturn('http://testsite.com');
        $this->request->expects($this->any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/path-info');
        $test = $this;
        $this->request->expects($this->any())->method('getQueryString')->willReturnCallback(function () use ($test) {
            $qs = Request::normalizeQueryString($test->request->server->get('QUERY_STRING'));

            return '' === $qs ? null : $qs;
        });
        $this->request->expects($this->any())->method('getUri')->willReturnCallback(function () use ($test) {
            if (null !== $qs = $test->request->getQueryString()) {
                $qs = '?'.$qs;
            }

            return $test->request->getSchemeAndHttpHost().$test->request->getBaseUrl().$test->request->getPathInfo().$qs;
        });
        $this->request->cookies = new ParameterBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->setRedirectConfig($redirectConfig);
        $deviceDataCollector->collect($this->request, $this->response);

        $currentView = $deviceDataCollector->getCurrentView();
        $views = $deviceDataCollector->getViews();

        $this->assertEquals($deviceView->getViewType(), $currentView);
        $this->assertEquals(DeviceView::VIEW_FULL, $currentView);
        $this->assertCount(3, $views);

        foreach ($views as $view) {
            $this->assertIsArray($view);
            $this->assertArrayHasKey('type', $view);
            $this->assertArrayHasKey('label', $view);
            $this->assertArrayHasKey('link', $view);
            $this->assertArrayHasKey('isCurrent', $view);
            $this->assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_FULL === $view['type']) {
                $this->assertTrue($view['isCurrent']);
            }
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                $this->assertFalse($view['isCurrent']);
                $this->assertFalse($view['enabled']);
                $this->assertEquals(
                    sprintf(
                        'http://testsite.com/base-url/path-info?%s=%s&param1=value1',
                        $deviceView->getSwitchParam(),
                        DeviceView::VIEW_MOBILE
                    ), $view['link']
                );
            }
        }
    }

    public function getNameValue()
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $this->assertEquals('device.collector', $deviceDataCollector->getName());
    }
}
