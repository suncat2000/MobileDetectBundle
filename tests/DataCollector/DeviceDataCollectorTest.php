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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerBag;

/**
 * DeviceDataCollectorTest.
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 *
 * @internal
 * @coversNothing
 */
final class DeviceDataCollectorTest extends TestCase
{
    private $requestStack;

    private $request;

    private $response;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->request->query = new ParameterBag();
        $this->request->cookies = new ParameterBag();
        $this->request->server = new ServerBag();
        $this->request->expects(static::any())->method('duplicate')->willReturn($this->request);

        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects(static::any())
            ->method(method_exists(RequestStack::class, 'getMainRequest') ? 'getMainRequest' : 'getMasterRequest')
            ->willReturn($this->request)
        ;

        $this->response = $this->getMockBuilder(Response::class)->getMock();
    }

    public function testCollectCurrentViewMobileIsCurrent()
    {
        $redirectConfig['tablet'] = [
            'is_enabled' => true,
            'host' => 'http://testsite.com',
            'status_code' => Response::HTTP_FOUND,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->cookies = new ParameterBag([DeviceView::COOKIE_KEY_DEFAULT => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceDataCollector = new DeviceDataCollector($deviceView);
        $deviceDataCollector->setRedirectConfig($redirectConfig);
        $deviceDataCollector->collect($this->request, $this->response);

        $currentView = $deviceDataCollector->getCurrentView();
        $views = $deviceDataCollector->getViews();

        static::assertSame($deviceView->getViewType(), $currentView);
        static::assertSame(DeviceView::VIEW_MOBILE, $currentView);
        static::assertCount(3, $views);

        foreach ($views as $view) {
            static::assertIsArray($view);
            static::assertArrayHasKey('type', $view);
            static::assertArrayHasKey('label', $view);
            static::assertArrayHasKey('link', $view);
            static::assertArrayHasKey('isCurrent', $view);
            static::assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                static::assertTrue($view['isCurrent']);
            }
        }
    }

    public function testCollectCurrentViewMobileCanUseTablet()
    {
        $redirectConfig['tablet'] = [
            'is_enabled' => true,
            'host' => 'http://testsite.com',
            'status_code' => Response::HTTP_FOUND,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->query = new ParameterBag(['param1' => 'value1']);
        $this->request->expects(static::any())->method('getHost')->willReturn('testsite.com');
        $this->request->expects(static::any())->method('getSchemeAndHttpHost')->willReturn('http://testsite.com');
        $this->request->expects(static::any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/path-info');
        $test = $this;
        $this->request->expects(static::any())->method('getQueryString')->willReturnCallback(function () use ($test) {
            $qs = Request::normalizeQueryString($test->request->server->get('QUERY_STRING'));

            return '' === $qs ? null : $qs;
        });
        $this->request->expects(static::any())->method('getUri')->willReturnCallback(function () use ($test) {
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

        static::assertSame($deviceView->getViewType(), $currentView);
        static::assertSame(DeviceView::VIEW_MOBILE, $currentView);
        static::assertCount(3, $views);

        foreach ($views as $view) {
            static::assertIsArray($view);
            static::assertArrayHasKey('type', $view);
            static::assertArrayHasKey('label', $view);
            static::assertArrayHasKey('link', $view);
            static::assertArrayHasKey('isCurrent', $view);
            static::assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                static::assertTrue($view['isCurrent']);
            }
            if (DeviceView::VIEW_TABLET === $view['type']) {
                static::assertFalse($view['isCurrent']);
                static::assertTrue($view['enabled']);
                static::assertSame(
                    sprintf(
                        'http://testsite.com/base-url/path-info?%s=%s&param1=value1',
                        $deviceView->getSwitchParam(),
                        DeviceView::VIEW_TABLET
                    ), $view['link']
                );
            }
        }
    }

    public function testCollectCurrentViewFullCanUseMobile()
    {
        $redirectConfig['tablet'] = [
            'is_enabled' => true,
            'host' => 'http://testsite.com',
            'status_code' => Response::HTTP_FOUND,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->query = new ParameterBag(['param1' => 'value1']);
        $this->request->expects(static::any())->method('getHost')->willReturn('testsite.com');
        $this->request->expects(static::any())->method('getSchemeAndHttpHost')->willReturn('http://testsite.com');
        $this->request->expects(static::any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/path-info');
        $test = $this;
        $this->request->expects(static::any())->method('getQueryString')->willReturnCallback(function () use ($test) {
            $qs = Request::normalizeQueryString($test->request->server->get('QUERY_STRING'));

            return '' === $qs ? null : $qs;
        });
        $this->request->expects(static::any())->method('getUri')->willReturnCallback(function () use ($test) {
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

        static::assertSame($deviceView->getViewType(), $currentView);
        static::assertSame(DeviceView::VIEW_FULL, $currentView);
        static::assertCount(3, $views);

        foreach ($views as $view) {
            static::assertIsArray($view);
            static::assertArrayHasKey('type', $view);
            static::assertArrayHasKey('label', $view);
            static::assertArrayHasKey('link', $view);
            static::assertArrayHasKey('isCurrent', $view);
            static::assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_FULL === $view['type']) {
                static::assertTrue($view['isCurrent']);
            }
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                static::assertFalse($view['isCurrent']);
                static::assertTrue($view['enabled']);
                static::assertSame(
                    sprintf(
                        'http://testsite.com/base-url/path-info?%s=%s&param1=value1',
                        $deviceView->getSwitchParam(),
                        DeviceView::VIEW_MOBILE
                    ), $view['link']
                );
            }
        }
    }

    public function testCollectCurrentViewFullCantUseMobile()
    {
        $redirectConfig['mobile'] = [
            'is_enabled' => true,
            'host' => 'http://m.testsite.com',
            'status_code' => Response::HTTP_FOUND,
            'action' => RequestResponseListener::REDIRECT,
        ];
        $this->request->query = new ParameterBag(['param1' => 'value1']);
        $this->request->expects(static::any())->method('getHost')->willReturn('testsite.com');
        $this->request->expects(static::any())->method('getSchemeAndHttpHost')->willReturn('http://testsite.com');
        $this->request->expects(static::any())->method('getBaseUrl')->willReturn('/base-url');
        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/path-info');
        $test = $this;
        $this->request->expects(static::any())->method('getQueryString')->willReturnCallback(function () use ($test) {
            $qs = Request::normalizeQueryString($test->request->server->get('QUERY_STRING'));

            return '' === $qs ? null : $qs;
        });
        $this->request->expects(static::any())->method('getUri')->willReturnCallback(function () use ($test) {
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

        static::assertSame($deviceView->getViewType(), $currentView);
        static::assertSame(DeviceView::VIEW_FULL, $currentView);
        static::assertCount(3, $views);

        foreach ($views as $view) {
            static::assertIsArray($view);
            static::assertArrayHasKey('type', $view);
            static::assertArrayHasKey('label', $view);
            static::assertArrayHasKey('link', $view);
            static::assertArrayHasKey('isCurrent', $view);
            static::assertArrayHasKey('enabled', $view);
            if (DeviceView::VIEW_FULL === $view['type']) {
                static::assertTrue($view['isCurrent']);
            }
            if (DeviceView::VIEW_MOBILE === $view['type']) {
                static::assertFalse($view['isCurrent']);
                static::assertFalse($view['enabled']);
                static::assertSame(
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
        static::assertSame('device.collector', $deviceDataCollector->getName());
    }
}
