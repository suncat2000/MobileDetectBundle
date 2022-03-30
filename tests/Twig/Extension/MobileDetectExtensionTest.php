<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\Twig\Extension;

use MobileDetectBundle\DeviceDetector\MobileDetector;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Twig\Extension\MobileDetectExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\TwigFunction;

/**
 * @internal
 * @coversNothing
 */
final class MobileDetectExtensionTest extends TestCase
{
    private $mobileDetector;

    private $requestStack;

    /**
     * @var array
     */
    private $config;

    private $request;

    private $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mobileDetector = $this->getMockBuilder(MobileDetector::class)->disableOriginalConstructor()->getMock();
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

        $this->config = [
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ];
    }

    public function testGetFunctionsArray()
    {
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);

        $functions = $extension->getFunctions();
        static::assertCount(11, $functions);
        $names = [
            'is_mobile' => 'isMobile',
            'is_tablet' => 'isTablet',
            'is_device' => 'isDevice',
            'is_full_view' => 'isFullView',
            'is_mobile_view' => 'isMobileView',
            'is_tablet_view' => 'isTabletView',
            'is_not_mobile_view' => 'isNotMobileView',
            'is_ios' => 'isIOS',
            'is_android_os' => 'isAndroidOS',
            'full_view_url' => 'fullViewUrl',
            'device_version' => 'deviceVersion',
        ];
        foreach ($functions as $function) {
            static::assertInstanceOf(TwigFunction::class, $function);
            $name = $function->getName();
            $callable = $function->getCallable();
            static::assertArrayHasKey($name, $names);
            static::assertIsArray($callable);
            static::assertSame($names[$name], $callable[1]);
        }
    }

    public function testFullViewUrlHostNull()
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => null];

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertNull($extension->fullViewUrl());
    }

    public function testFullViewUrlHostEmpty()
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => ''];

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertNull($extension->fullViewUrl());
    }

    public function testFullViewUrlNotSetRequest()
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertSame('http://mobilehost.com', $extension->fullViewUrl());
    }

    public function testFullViewUrlWithRequestQuery()
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->request->query = new ParameterBag(['myparam' => 'myvalue']);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        $extension->setRequestByRequestStack($this->requestStack);
        static::assertSame('http://mobilehost.com?myparam=myvalue', $extension->fullViewUrl());
    }

    public function testFullViewUrlWithRequestOnlyHost()
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->request->query = new ParameterBag(['myparam' => 'myvalue']);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        $extension->setRequestByRequestStack($this->requestStack);
        static::assertSame('http://mobilehost.com', $extension->fullViewUrl(false));
    }

    public function testIsMobileTrue()
    {
        $this->mobileDetector->expects(static::once())->method('isMobile')->willReturn(true);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isMobile());
    }

    public function testIsMobileFalse()
    {
        $this->mobileDetector->expects(static::once())->method('isMobile')->willReturn(false);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isMobile());
    }

    public function testIsTabletTrue()
    {
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(true);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isTablet());
    }

    public function testIsTabletFalse()
    {
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(false);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isTablet());
    }

    public function testIsDeviceIPhone()
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isiphone'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isDevice('iphone'));
    }

    public function testIsDeviceAndroid()
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isandroid'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isDevice('android'));
    }

    public function testIsFullViewTrue()
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isFullView());
    }

    public function testIsFullViewFalse()
    {
        $deviceView = new DeviceView();
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isFullView());
    }

    public function testIsMobileViewTrue()
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isMobileView());
    }

    public function testIsMobileViewFalse()
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isMobileView());
    }

    public function testIsTabletViewTrue()
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isTabletView());
    }

    public function testIsTabletViewFalse()
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isTabletView());
    }

    public function testIsNotMobileViewTrue()
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_NOT_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isNotMobileView());
    }

    public function testIsNotMobileViewFalse()
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isNotMobileView());
    }

    public function testIsIOSTrue()
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isIOS'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isIOS());
    }

    public function testIsIOSFalse()
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isIOS'))
            ->willReturn(false)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isIOS());
    }

    public function testIsAndroidOSTrue()
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isAndroidOS'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isAndroidOS());
    }

    public function testIsAndroidOSFalse()
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isAndroidOS'))
            ->willReturn(false)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isAndroidOS());
    }
}
