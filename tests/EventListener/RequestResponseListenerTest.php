<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\EventListener;

use MobileDetectBundle\DeviceDetector\MobileDetector;
use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\RedirectResponseWithCookie;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 * @coversNothing
 */
class RequestResponseListenerTest extends TestCase
{
    private $mobileDetector;

    private $deviceView;

    private $requestStack;

    private $request;

    private $router;

    /**
     * @var array
     */
    private $config;

    private $cookieKey = DeviceView::COOKIE_KEY_DEFAULT;
    private $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    public function setUp(): void
    {
        parent::setUp();

        $this->mobileDetector = $this->getMockBuilder(MobileDetector::class)->disableOriginalConstructor()->getMock();
        $this->deviceView = $this->getMockBuilder(DeviceView::class)->disableOriginalConstructor()->getMock();
        if (method_exists(MockBuilder::class, 'onlyMethods')) {
            $this->router = $this->getMockBuilder(Router::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getRouteCollection'])
                ->getMock()
            ;
        } else {
            $this->router = $this->getMockBuilder(Router::class)
                ->disableOriginalConstructor()
                ->setMethods(['getRouteCollection'])
                ->getMock()
            ;
        }

        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $this->request->expects($this->any())->method('getScheme')->willReturn('http');
        $this->request->expects($this->any())->method('getHost')->willReturn('testhost.com');
        $this->request->expects($this->any())->method('get')->willReturn('value');
        $this->request->expects($this->any())->method('getUriForPath')->willReturn('/');
        $this->request->query = new ParameterBag();
        $this->request->cookies = new ParameterBag();

        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')->disableOriginalConstructor()->getMock();
        $this->requestStack->expects($this->any())
            ->method('getMainRequest')
            ->willReturn($this->request)
        ;

        $this->config = [
            'mobile' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ];
    }

    public function testHandleRequestHasSwitchParam()
    {
        $this->request->query = new ParameterBag(['myparam' => 'myvalue', $this->switchParam => DeviceView::VIEW_MOBILE]);
        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/');
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, []);
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);
        $this->assertFalse($listener->needsResponseModification());

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponseWithCookie::class, $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasSwitchParamAndQuery()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->request->query = new ParameterBag(['myparam' => 'myvalue', $this->switchParam => DeviceView::VIEW_MOBILE]);
        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/');
        $this->request->expects($this->any())->method('get')->willReturn('value');
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );

        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);
        $this->assertFalse($listener->needsResponseModification());

        $response = $event->getResponse();
        $this->assertInstanceOf('MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals(sprintf(
            'http://mobilehost.com/?%s=%s&myparam=myvalue',
            $this->switchParam,
            DeviceView::VIEW_MOBILE
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestIsFullView()
    {
        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        $this->assertFalse($deviceView->hasSwitchParam());
        $this->assertNull($deviceView->getRequestedViewType());
        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        $this->assertTrue($listener->needsResponseModification());
        $this->assertEquals(DeviceView::VIEW_FULL, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        $this->assertNull($requestEventResponse);

        $responseEventResponse = new Response('Full view', 200);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $modifiedResponse);
        $this->assertEquals(200, $modifiedResponse->getStatusCode());
        $this->assertEquals('Full view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_FULL, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestIsNotMobileView()
    {
        $deviceView = new DeviceView();
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        $this->assertFalse($deviceView->hasSwitchParam());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_NOT_MOBILE, $deviceView->getViewType());
        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        $this->assertFalse($listener->needsResponseModification());

        $requestEventResponse = $getResponseEvent->getResponse();
        $this->assertNull($requestEventResponse);

        $responseEventResponse = new Response('Not mobile view', 200);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $modifiedResponse);
        $this->assertEquals(200, $modifiedResponse->getStatusCode());
        $this->assertEquals('Not mobile view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        $this->assertCount(0, $cookies);
    }

    public function testHandleRequestHasTabletRedirect()
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->query = new ParameterBag(['some' => 'param']);
        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->request->expects($this->any())->method('get')->willReturn('value');
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects($this->once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        $this->assertFalse($deviceView->hasSwitchParam());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();
        $this->assertInstanceOf('MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals(sprintf(
                'http://testsite.com/some/parameters?%s=%s&some=param',
                $this->switchParam,
                DeviceView::VIEW_TABLET
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestWithDifferentSwitchParamRedirect()
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $switchParam = 'custom_param';

        $this->request->query = new ParameterBag(['some' => 'param']);
        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects($this->once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setSwitchParam($switchParam);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        $this->assertFalse($deviceView->hasSwitchParam());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();
        $this->assertInstanceOf('MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals(sprintf(
                'http://testsite.com/some/parameters?%s=%s&some=param',
                $switchParam,
                DeviceView::VIEW_TABLET
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleDeviceIsTabletAndTabletRedirectIsDisabledAndDetectTabletAsMobileIsFalse()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->mobileDetector->expects($this->once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        $this->assertTrue($listener->needsResponseModification());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        $this->assertNull($requestEventResponse);

        $responseEventResponse = new Response('Tablet view', 200);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $modifiedResponse);
        $this->assertEquals(200, $modifiedResponse->getStatusCode());
        $this->assertEquals('Tablet view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleDeviceIsTabletAsMobileAndTabletRedirectIsDisabledAndDetectTabletAsMobileIsTrue()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com', 'status_code' => Response::HTTP_FOUND];
        $this->config['detect_tablet_as_mobile'] = true;

        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects($this->atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects($this->atLeastOnce())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        $this->assertFalse($listener->needsResponseModification());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        $this->assertInstanceOf('MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals(sprintf(
                'http://mobilehost.com/some/parameters?%s=%s',
                $this->switchParam,
                DeviceView::VIEW_MOBILE
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasTabletRedirectWithoutPath()
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects($this->atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT_WITHOUT_PATH, 2)
        );
        $this->mobileDetector->expects($this->once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        $this->assertFalse($listener->needsResponseModification());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        $this->assertInstanceOf('MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals('http://testsite.com?device_view=tablet', $response->getTargetUrl());
        $this->assertEquals(sprintf(
                'http://testsite.com?%s=%s',
                $this->switchParam,
                DeviceView::VIEW_TABLET
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasTabletNoRedirect()
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects($this->atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::NO_REDIRECT, 1)
        );
        $this->mobileDetector->expects($this->once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        $this->assertTrue($listener->needsResponseModification());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        $this->assertNull($requestEventResponse);

        $responseEventResponse = new Response('Tablet view no redirect', 200);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $modifiedResponse);
        $this->assertEquals(200, $modifiedResponse->getStatusCode());
        $this->assertEquals('Tablet view no redirect', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasMobileRedirect()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects($this->atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects($this->once())->method('isTablet')->willReturn(false);
        $this->mobileDetector->expects($this->once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        $this->assertFalse($listener->needsResponseModification());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        $this->assertInstanceOf('MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals(sprintf(
                'http://testsite.com/some/parameters?%s=%s',
                $this->switchParam,
                DeviceView::VIEW_MOBILE
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasMobileRedirectWithoutPath()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects($this->atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT_WITHOUT_PATH, 2)
        );
        $this->mobileDetector->expects($this->once())->method('isTablet')->willReturn(false);
        $this->mobileDetector->expects($this->once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        $this->assertFalse($listener->needsResponseModification());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        $this->assertInstanceOf('MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertEquals(sprintf(
                'http://testsite.com?%s=%s',
                $this->switchParam,
                DeviceView::VIEW_MOBILE
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasMobileNoRedirect()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123];

        $this->request->expects($this->any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects($this->atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::NO_REDIRECT, 1)
        );
        $this->mobileDetector->expects($this->once())->method('isTablet')->willReturn(false);
        $this->mobileDetector->expects($this->once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        $this->assertTrue($listener->needsResponseModification());
        $this->assertNull($deviceView->getRequestedViewType());
        $this->assertEquals(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        $this->assertNull($requestEventResponse);

        $responseEventResponse = new Response('Mobile view no redirect', 200);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $modifiedResponse);
        $this->assertEquals(200, $modifiedResponse->getStatusCode());
        $this->assertEquals('Mobile view no redirect', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        $this->assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            // @var \Symfony\Component\HttpFoundation\Cookie $cookie
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestUpdatedMobileDetectorUserAgent()
    {
        $this->mobileDetector->expects($this->once())->method('setUserAgent')->with($this->equalTo('agent'));

        $event = $this->createGetResponseEvent('some content');
        $event->getRequest()->headers->set('user-agent', 'agent');

        $listener = new RequestResponseListener($this->mobileDetector, $this->deviceView, $this->router, $this->config);
        $listener->handleRequest($event);
    }

    private function createRouteCollecitonWithRouteAndRoutingOption($returnValue, $times)
    {
        $route = $this->getMockBuilder('Symfony\Component\Routing\Route')->disableOriginalConstructor()->getMock();
        $route->expects($this->exactly($times))->method('getOption')->willReturn($returnValue);
        $routeCollection = $this->createMock('Symfony\Component\Routing\RouteCollection');
        $routeCollection->expects($this->exactly($times))->method('get')->willReturn($route);

        return $routeCollection;
    }

    private function createGetResponseEvent(string $content, string $method = 'GET', array $headers = []): ViewEvent
    {
        $event = new ViewEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MASTER_REQUEST,
            $content
        );
        $event->getRequest()->headers = new HeaderBag($headers);

        return $event;
    }

    /**
     * createResponseEvent.
     *
     * @param Response $response
     * @param string   $method   Method
     * @param array    $headers  Headers
     *
     * @return \Symfony\Component\HttpKernel\Event\ResponseEvent
     */
    private function createResponseEvent($response, $method = 'GET', $headers = [])
    {
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );
        $event->getRequest()->headers = new HeaderBag($headers);

        return $event;
    }
}
