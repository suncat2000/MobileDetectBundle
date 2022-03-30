<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\Helper;

use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Helper\RedirectResponseWithCookie;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 * @coversNothing
 */
final class DeviceViewTest extends TestCase
{
    private $requestStack;

    private $request;

    private $cookieKey = DeviceView::COOKIE_KEY_DEFAULT;
    private $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->request->expects(static::any())->method('getScheme')->willReturn('http');
        $this->request->expects(static::any())->method('getHost')->willReturn('testhost.com');
        $this->request->expects(static::any())->method('getUriForPath')->willReturn('/');
        $this->request->query = new ParameterBag();
        $this->request->cookies = new ParameterBag();

        $this->requestStack->expects(static::any())
            ->method(method_exists(RequestStack::class, 'getMainRequest') ? 'getMainRequest' : 'getMasterRequest')
            ->willReturn($this->request)
        ;
    }

    public function testGetViewTypeMobile()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());
        static::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getRequestedViewType());
    }

    public function testGetViewTypeTablet()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame(DeviceView::VIEW_TABLET, $deviceView->getViewType());
        static::assertSame(DeviceView::VIEW_TABLET, $deviceView->getRequestedViewType());
    }

    public function testGetViewTypeFull()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame(DeviceView::VIEW_FULL, $deviceView->getViewType());
        static::assertSame(DeviceView::VIEW_FULL, $deviceView->getRequestedViewType());
    }

    public function testGetViewTypeNotMobile()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView();
        static::assertSame(DeviceView::VIEW_NOT_MOBILE, $deviceView->getViewType());
        static::assertNull($deviceView->getRequestedViewType());
    }

    public function testGetViewTypeMobileFromCookie()
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getViewType());
        static::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getRequestedViewType());
    }

    public function testIsFullViewTrue()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertTrue($deviceView->isFullView());
    }

    public function testIsFullViewFalse()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertFalse($deviceView->isFullView());
    }

    public function testIsTabletViewTrue()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertTrue($deviceView->isTabletView());
    }

    public function testIsTabletViewFalse()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertFalse($deviceView->isTabletView());
    }

    public function testIsMobileViewTrue()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertTrue($deviceView->isMobileView());
    }

    public function testIsMobileViewFalse()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertFalse($deviceView->isMobileView());
    }

    public function testIsNotMobileViewTrue()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_NOT_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertTrue($deviceView->isNotMobileView());
    }

    public function testIsNotMobileViewFalse()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertFalse($deviceView->isNotMobileView());
    }

    public function testHasSwitchParamTrue()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertTrue($deviceView->hasSwitchParam());
    }

    public function testHasSwitchParamFalse1()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        static::assertFalse($deviceView->hasSwitchParam());
    }

    public function testHasSwitchParamFalse2()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView();
        static::assertFalse($deviceView->hasSwitchParam());
    }

    public function testSetViewMobile()
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setView(DeviceView::VIEW_MOBILE);
        static::assertTrue($deviceView->isMobileView());
    }

    public function testSetViewFull()
    {
        $deviceView = new DeviceView();
        $deviceView->setView(DeviceView::VIEW_FULL);
        static::assertTrue($deviceView->isFullView());
    }

    public function testSetFullViewAndCheckIsFullView()
    {
        $deviceView = new DeviceView();
        $deviceView->setFullView();
        static::assertTrue($deviceView->isFullView());
    }

    public function testSetTabletViewAndCheckIsTabletView()
    {
        $deviceView = new DeviceView();
        $deviceView->setTabletView();
        static::assertTrue($deviceView->isTabletView());
    }

    public function testSetMobileViewAndCheckIsMobileView()
    {
        $deviceView = new DeviceView();
        $deviceView->setMobileView();
        static::assertTrue($deviceView->isMobileView());
    }

    public function testSetNotMobileViewAndCheckIsNotMobileView()
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setNotMobileView();
        static::assertTrue($deviceView->isNotMobileView());
    }

    public function testGetSwitchParamValueNull()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView();
        static::assertNull($deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueFullDefault()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame(DeviceView::VIEW_FULL, $deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueFull()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame(DeviceView::VIEW_FULL, $deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueMobile()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame(DeviceView::VIEW_MOBILE, $deviceView->getSwitchParamValue());
    }

    public function testGetSwitchParamValueTablet()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame(DeviceView::VIEW_TABLET, $deviceView->getSwitchParamValue());
    }

    public function testGetRedirectResponseBySwitchParamWithCookieViewMobile()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_MOBILE => ['status_code' => Response::HTTP_MOVED_PERMANENTLY]]);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testGetRedirectResponseBySwitchParamWithCookieViewTablet()
    {
        $this->request->query = new ParameterBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_TABLET => ['status_code' => Response::HTTP_MOVED_PERMANENTLY]]);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testGetRedirectResponseBySwitchParamWithCookieViewFullDefault()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function testModifyResponseToMobileAndCheckResponse()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = new Response();
        static::assertCount(0, $response->headers->getCookies());
        $deviceView->modifyResponse(DeviceView::VIEW_MOBILE, $response);

        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testGetRedirectResponseWithCookieViewMobile()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = $deviceView->getRedirectResponse(DeviceView::VIEW_MOBILE, 'http://mobilesite.com', Response::HTTP_FOUND);
        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        static::assertGreaterThan(0, \count($cookies));
        foreach ($cookies as $cookie) {
            static::assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            /** @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() === $deviceView->getCookieKey()) {
                static::assertSame(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    public function testGetRedirectResponseAndCheckCookieSettings()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookiePath('/test');
        $deviceView->setCookieDomain('example.com');
        $deviceView->setCookieSecure(true);
        $deviceView->setCookieHttpOnly(false);

        $response = $deviceView->getRedirectResponse(DeviceView::VIEW_MOBILE, 'http://mobilesite.com', Response::HTTP_FOUND);
        static::assertInstanceOf(RedirectResponseWithCookie::class, $response);
        static::assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        /** @var Cookie[] $cookies */
        $cookies = $response->headers->getCookies();
        static::assertCount(1, $cookies);
        static::assertSame('/test', $cookies[0]->getPath());
        static::assertSame('example.com', $cookies[0]->getDomain());
        static::assertTrue($cookies[0]->isSecure());
        static::assertFalse($cookies[0]->isHttpOnly());
    }

    public function testGetCookieKeyDeviceView()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame($this->cookieKey, $deviceView->getCookieKey());
    }

    public function testGetSwitchParamDeviceView()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        static::assertSame($this->switchParam, $deviceView->getSwitchParam());
    }
}
