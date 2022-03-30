<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\EventListener;

use MobileDetectBundle\DeviceDetector\MobileDetector;
use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\RedirectResponseWithCookie;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 * @coversNothing
 */
final class RequestResponseListenerTest extends TestCase
{
    private $mobileDetector;

    private $requestStack;

    private $request;

    private $router;

    private $config;

    private $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mobileDetector = $this->getMockBuilder(MobileDetector::class)->disableOriginalConstructor()->getMock();
        $this->router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRouteCollection'])
            ->getMock()
        ;

        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->request->expects(static::any())->method('getScheme')->willReturn('http');
        $this->request->expects(static::any())->method('getHost')->willReturn('testhost.com');
        $this->request->expects(static::any())->method('get')->willReturn('value');
        $this->request->expects(static::any())->method('getUriForPath')->willReturn('/');
        $this->request->query = new ParameterBag();
        $this->request->cookies = new ParameterBag();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $this->requestStack->expects(static::any())
            ->method(method_exists(RequestStack::class, 'getMainRequest') ? 'getMainRequest' : 'getMasterRequest')
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
        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/');
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, []);
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);
        static::assertFalse($listener->needsResponseModification());

        $response = $event->getResponse();
        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasSwitchParamAndQuery()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->request->query = new ParameterBag(['myparam' => 'myvalue', $this->switchParam => DeviceView::VIEW_MOBILE]);
        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/');
        $this->request->expects(static::any())->method('get')->willReturn('value');
        $this->router->expects(static::exactly(2))->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );

        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_FOUND]]);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);
        static::assertFalse($listener->needsResponseModification());

        $response = $event->getResponse();
        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertSame(sprintf(
            'http://mobilehost.com/?%s=%s&myparam=myvalue',
            $this->switchParam,
            DeviceView::VIEW_MOBILE
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestIsFullView()
    {
        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        static::assertFalse($deviceView->hasSwitchParam());
        static::assertNull($deviceView->getRequestedViewType());
        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        static::assertTrue($listener->needsResponseModification());
        static::assertSame(DeviceView::VIEW_FULL, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        static::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Full view', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        static::assertInstanceOf(Response::class, $modifiedResponse);
        static::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        static::assertSame('Full view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_FULL, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestIsNotMobileView()
    {
        $deviceView = new DeviceView();
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        static::assertFalse($deviceView->hasSwitchParam());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_NOT_MOBILE, $deviceView->getViewType());
        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        static::assertFalse($listener->needsResponseModification());

        $requestEventResponse = $getResponseEvent->getResponse();
        static::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Not mobile view', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        static::assertInstanceOf(Response::class, $modifiedResponse);
        static::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        static::assertSame('Not mobile view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        static::assertCount(0, $cookies);
    }

    public function testHandleRequestHasTabletRedirect()
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->query = new ParameterBag(['some' => 'param']);
        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->request->expects(static::any())->method('get')->willReturn('value');
        $this->router->expects(static::exactly(2))->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        static::assertFalse($deviceView->hasSwitchParam());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();
        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertSame(sprintf(
                'http://testsite.com/some/parameters?%s=%s&some=param',
                $this->switchParam,
                DeviceView::VIEW_TABLET
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestWithDifferentSwitchParamRedirect()
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $switchParam = 'custom_param';

        $this->request->query = new ParameterBag(['some' => 'param']);
        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(static::exactly(2))->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setSwitchParam($switchParam);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');

        $listener->handleRequest($getResponseEvent);
        static::assertFalse($deviceView->hasSwitchParam());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();
        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertSame(sprintf(
                'http://testsite.com/some/parameters?%s=%s&some=param',
                $switchParam,
                DeviceView::VIEW_TABLET
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleDeviceIsTabletAndTabletRedirectIsDisabledAndDetectTabletAsMobileIsFalse()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        static::assertTrue($listener->needsResponseModification());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        static::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Tablet view', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        static::assertInstanceOf(Response::class, $modifiedResponse);
        static::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        static::assertSame('Tablet view', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleDeviceIsTabletAsMobileAndTabletRedirectIsDisabledAndDetectTabletAsMobileIsTrue()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com', 'status_code' => Response::HTTP_FOUND];
        $this->config['detect_tablet_as_mobile'] = true;

        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(static::atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects(static::atLeastOnce())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        static::assertFalse($listener->needsResponseModification());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertSame(sprintf(
                'http://mobilehost.com/some/parameters?%s=%s',
                $this->switchParam,
                DeviceView::VIEW_MOBILE
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasTabletRedirectWithoutPath()
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(static::atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT_WITHOUT_PATH, 2)
        );
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        static::assertFalse($listener->needsResponseModification());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertSame('http://testsite.com?device_view=tablet', $response->getTargetUrl());
        static::assertSame(sprintf(
                'http://testsite.com?%s=%s',
                $this->switchParam,
                DeviceView::VIEW_TABLET
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasTabletNoRedirect()
    {
        $this->config['tablet'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(static::atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::NO_REDIRECT, 1)
        );
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        static::assertTrue($listener->needsResponseModification());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        static::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Tablet view no redirect', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        static::assertInstanceOf(Response::class, $modifiedResponse);
        static::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        static::assertSame('Tablet view no redirect', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_TABLET, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasMobileRedirect()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(static::atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT, 2)
        );
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(false);
        $this->mobileDetector->expects(static::once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        static::assertFalse($listener->needsResponseModification());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertSame(sprintf(
                'http://testsite.com/some/parameters?%s=%s',
                $this->switchParam,
                DeviceView::VIEW_MOBILE
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasMobileRedirectWithoutPath()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND];

        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(static::atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::REDIRECT_WITHOUT_PATH, 2)
        );
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(false);
        $this->mobileDetector->expects(static::once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        static::assertFalse($listener->needsResponseModification());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $response = $getResponseEvent->getResponse();

        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        static::assertSame(sprintf(
                'http://testsite.com?%s=%s',
                $this->switchParam,
                DeviceView::VIEW_MOBILE
            ), $response->getTargetUrl()
        );

        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestHasMobileNoRedirect()
    {
        $this->config['mobile'] = ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123];

        $this->request->expects(static::any())->method('getPathInfo')->willReturn('/some/parameters');
        $this->router->expects(static::atLeastOnce())->method('getRouteCollection')->willReturn(
                $this->createRouteCollecitonWithRouteAndRoutingOption(RequestResponseListener::NO_REDIRECT, 1)
        );
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(false);
        $this->mobileDetector->expects(static::once())->method('isMobile')->willReturn(true);

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);

        $getResponseEvent = $this->createGetResponseEvent('some content');
        $listener->handleRequest($getResponseEvent);

        static::assertTrue($listener->needsResponseModification());
        static::assertNull($deviceView->getRequestedViewType());
        static::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());

        $requestEventResponse = $getResponseEvent->getResponse();
        static::assertNull($requestEventResponse);

        $responseEventResponse = new Response('Mobile view no redirect', Response::HTTP_OK);
        $responseEvent = $this->createResponseEvent($responseEventResponse);
        $listener->handleResponse($responseEvent);
        $modifiedResponse = $responseEvent->getResponse();

        static::assertInstanceOf(Response::class, $modifiedResponse);
        static::assertSame(Response::HTTP_OK, $modifiedResponse->getStatusCode());
        static::assertSame('Mobile view no redirect', $modifiedResponse->getContent());

        $cookies = $modifiedResponse->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf(Cookie::class, $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testHandleRequestUpdatedMobileDetectorUserAgent()
    {
        $this->mobileDetector->expects(static::once())->method('setUserAgent')->with(static::equalTo('agent'));

        $event = $this->createGetResponseEvent('some content');
        $event->getRequest()->headers->set('user-agent', 'agent');

        $deviceView = new DeviceView($this->requestStack);
        $listener = new RequestResponseListener($this->mobileDetector, $deviceView, $this->router, $this->config);
        $listener->handleRequest($event);
    }

    private function createRouteCollecitonWithRouteAndRoutingOption($returnValue, $times)
    {
        $route = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->getMock();
        $route->expects(static::exactly($times))->method('getOption')->willReturn($returnValue);
        $routeCollection = $this->createMock(RouteCollection::class);
        $routeCollection->expects(static::exactly($times))->method('get')->willReturn($route);

        return $routeCollection;
    }

    private function createGetResponseEvent(string $content, string $method = 'GET', array $headers = []): ViewEvent
    {
        $event = new ViewEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->request,
            \defined('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') ? \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') : \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST'),
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
            \defined('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') ? \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MAIN_REQUEST') : \constant('Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST'),
            $response
        );
        $event->getRequest()->headers = new HeaderBag($headers);

        return $event;
    }
}
