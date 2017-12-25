<?php

namespace SunCat\MobileDetectBundle\Tests\Helper;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockBuilder;
use SunCat\MobileDetectBundle\Helper\DeviceView;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * DeviceView Test
 */
class DeviceViewTest extends TestCase
{

    /**
     * @var PHPUnit_Framework_MockObject_MockBuilder
     */
    private $requestStack;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    private $cookieKey = DeviceView::COOKIE_KEY_DEFAULT;
    private $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    /**
     * Set up
     */
    public function setUp()
    {
        parent::setUp();

        $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')->disableOriginalConstructor()->getMock();

        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $this->request->expects($this->any())->method('getScheme')->will($this->returnValue('http'));
        $this->request->expects($this->any())->method('getHost')->will($this->returnValue('testhost.com'));
        $this->request->expects($this->any())->method('getUriForPath')->will($this->returnValue('/'));
        $this->request->query = new ParameterBag();
        $this->request->cookies = new ParameterBag();

        $this->requestStack->expects($this->any())
            ->method('getMasterRequest')
            ->will($this->returnValue($this->request))
        ;
    }

    /**
     * @test
     */
    public function getViewTypeMobile()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals(DeviceView::VIEW_MOBILE, $deviceView->getViewType());
        $this->assertEquals(DeviceView::VIEW_MOBILE, $deviceView->getRequestedViewType());
    }

    /**
     * @test
     */
    public function getViewTypeTablet()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_TABLET));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals(DeviceView::VIEW_TABLET, $deviceView->getViewType());
        $this->assertEquals(DeviceView::VIEW_TABLET, $deviceView->getRequestedViewType());
    }

    /**
     * @test
     */
    public function getViewTypeFull()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_FULL));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals(DeviceView::VIEW_FULL, $deviceView->getViewType());
        $this->assertEquals(DeviceView::VIEW_FULL, $deviceView->getRequestedViewType());
    }

    /**
     * @test
     */
    public function getViewTypeNotMobile()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView();
        $this->assertEquals(DeviceView::VIEW_NOT_MOBILE, $deviceView->getViewType());
        $this->assertNull($deviceView->getRequestedViewType());
    }

    /**
     * @test
     */
    public function getViewTypeMobileFromCookie()
    {
        $this->request->cookies = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals(DeviceView::VIEW_MOBILE, $deviceView->getViewType());
        $this->assertEquals(DeviceView::VIEW_MOBILE, $deviceView->getRequestedViewType());
    }

    /**
     * @test
     */
    public function isFullViewTrue()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_FULL));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertTrue($deviceView->isFullView());
    }

    /**
     * @test
     */
    public function isFullViewFalse()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertFalse($deviceView->isFullView());
    }

    /**
     * @test
     */
    public function isTabletViewTrue()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_TABLET));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertTrue($deviceView->isTabletView());
    }

    /**
     * @test
     */
    public function isTabletViewFalse()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertFalse($deviceView->isTabletView());
    }

    /**
     * @test
     */
    public function isMobileViewTrue()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertTrue($deviceView->isMobileView());
    }

    /**
     * @test
     */
    public function isMobileViewFalse()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_TABLET));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertFalse($deviceView->isMobileView());
    }

    /**
     * @test
     */
    public function isNotMobileViewTrue()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_NOT_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertTrue($deviceView->isNotMobileView());
    }

    /**
     * @test
     */
    public function isNotMobileViewFalse()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertFalse($deviceView->isNotMobileView());
    }

    /**
     * @test
     */
    public function hasSwitchParamTrue()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertTrue($deviceView->hasSwitchParam());
    }

    /**
     * @test
     */
    public function hasSwitchParamFalse1()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $this->assertFalse($deviceView->hasSwitchParam());
    }

    /**
     * @test
     */
    public function hasSwitchParamFalse2()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView();
        $this->assertFalse($deviceView->hasSwitchParam());
    }

    /**
     * @test
     */
    public function setViewMobile()
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setView(DeviceView::VIEW_MOBILE);
        $this->assertTrue($deviceView->isMobileView());
    }

    /**
     * @test
     */
    public function setViewFull()
    {
        $deviceView = new DeviceView();
        $deviceView->setView(DeviceView::VIEW_FULL);
        $this->assertTrue($deviceView->isFullView());
    }

    /**
     * @test
     */
    public function setFullViewAndCheckIsFullView()
    {
        $deviceView = new DeviceView();
        $deviceView->setFullView();
        $this->assertTrue($deviceView->isFullView());
    }

    /**
     * @test
     */
    public function setTabletViewAndCheckIsTabletView()
    {
        $deviceView = new DeviceView();
        $deviceView->setTabletView();
        $this->assertTrue($deviceView->isTabletView());
    }

    /**
     * @test
     */
    public function setMobileViewAndCheckIsMobileView()
    {
        $deviceView = new DeviceView();
        $deviceView->setMobileView();
        $this->assertTrue($deviceView->isMobileView());
    }

    /**
     * @test
     */
    public function setNotMobileViewAndCheckIsNotMobileView()
    {
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setNotMobileView();
        $this->assertTrue($deviceView->isNotMobileView());
    }

    /**
     * @test
     */
    public function getSwitchParamValueNull()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView();
        $this->assertNull($deviceView->getSwitchParamValue());
    }

    /**
     * @test
     */
    public function getSwitchParamValueFullDefault()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals(DeviceView::VIEW_FULL, $deviceView->getSwitchParamValue());
    }

    /**
     * @test
     */
    public function getSwitchParamValueFull()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_FULL));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals(DeviceView::VIEW_FULL, $deviceView->getSwitchParamValue());
    }

    /**
     * @test
     */
    public function getSwitchParamValueMobile()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals(DeviceView::VIEW_MOBILE, $deviceView->getSwitchParamValue());
    }

    /**
     * @test
     */
    public function getSwitchParamValueTablet()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_TABLET));
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals(DeviceView::VIEW_TABLET, $deviceView->getSwitchParamValue());
    }

    /**
     * @test
     */
    public function getRedirectResponseBySwitchParamWithCookieViewMobile()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_MOBILE));
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_MOBILE => ['status_code' => 301]]);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        $this->assertInstanceOf('SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(301, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function getRedirectResponseBySwitchParamWithCookieViewTablet()
    {
        $this->request->query = new ParameterBag(array($this->switchParam=>DeviceView::VIEW_TABLET));
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setRedirectConfig([DeviceView::VIEW_TABLET => ['status_code' => 301]]);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        $this->assertInstanceOf('SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(301, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function getRedirectResponseBySwitchParamWithCookieViewFullDefault()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = $deviceView->getRedirectResponseBySwitchParam('/redirect-url');
        $this->assertInstanceOf('SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function modifyResponseToMobileAndCheckResponse()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = new Response();
        $this->assertEquals(0, count($response->headers->getCookies()));
        $deviceView->modifyResponse(DeviceView::VIEW_MOBILE, $response);

        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            /* @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() == $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    /**
     * @test
     */
    public function getRedirectResponseWithCookieViewMobile()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $response = $deviceView->getRedirectResponse(DeviceView::VIEW_MOBILE, 'http://mobilesite.com', 302);
        $this->assertInstanceOf('SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(302, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        $this->assertGreaterThan(0, count($cookies));
        foreach ($cookies as $cookie) {
            $this->assertInstanceOf('Symfony\Component\HttpFoundation\Cookie', $cookie);
            /* @var \Symfony\Component\HttpFoundation\Cookie $cookie */
            if ($cookie->getName() == $deviceView->getCookieKey()) {
                $this->assertEquals(DeviceView::VIEW_MOBILE, $cookie->getValue());
            }
        }
    }

    /**
     * @test
     */
    public function getRedirectResponseAndCheckCookieSettings()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $deviceView->setCookiePath('/test');
        $deviceView->setCookieDomain('example.com');
        $deviceView->setCookieSecure(true);
        $deviceView->setCookieHttpOnly(false);

        $response = $deviceView->getRedirectResponse(DeviceView::VIEW_MOBILE, 'http://mobilesite.com', 302);
        $this->assertInstanceOf('SunCat\MobileDetectBundle\Helper\RedirectResponseWithCookie', $response);
        $this->assertEquals(302, $response->getStatusCode());

        /** @var Cookie[] $cookies */
        $cookies = $response->headers->getCookies();
        $this->assertEquals(1, count($cookies));
        $this->assertEquals('/test', $cookies[0]->getPath());
        $this->assertEquals('example.com', $cookies[0]->getDomain());
        $this->assertTrue($cookies[0]->isSecure());
        $this->assertFalse($cookies[0]->isHttpOnly());
    }

    /**
     * @test
     */
    public function getCookieKeyDeviceView()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals($this->cookieKey, $deviceView->getCookieKey());
    }

    /**
     * @test
     */
    public function getSwitchParamDeviceView()
    {
        $this->request->query = new ParameterBag();
        $deviceView = new DeviceView($this->requestStack);
        $this->assertEquals($this->switchParam, $deviceView->getSwitchParam());
    }
}
