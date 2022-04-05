<?php

declare(strict_types=1);

namespace MobileDetectBundle\Tests\Twig\Extension;

use MobileDetectBundle\DeviceDetector\MobileDetector;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Twig\Extension\MobileDetectExtension;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\TwigFunction;

/**
 * @internal
 * @coversDefaultClass
 */
final class MobileDetectExtensionTest extends TestCase
{
    /**
     * @var MockObject|MobileDetector
     */
    private $mobileDetector;

    /**
     * @var MockObject|RequestStack
     */
    private $requestStack;

    /**
     * @var MockObject|Request
     */
    private $request;

    /**
     * @var array
     */
    private $config;

    private $switchParam = DeviceView::SWITCH_PARAM_DEFAULT;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mobileDetector = $this->createMock(MobileDetector::class);
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

    public function testGetFunctionsArray(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);

        $functions = $extension->getFunctions();
        static::assertCount(13, $functions);
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
            'is_windows_os' => 'isWindowsOS',
            'full_view_url' => 'fullViewUrl',
            'device_version' => 'deviceVersion',
            'rules_list' => 'getRules',
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

    public function testRulesList(): void
    {
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, new MobileDetector(), $deviceView, $this->config);
        static::assertEqualsWithDelta(173, \count($extension->getRules()), 190);
    }

    public function testDeviceVersion(): void
    {
        $this->mobileDetector->expects(static::exactly(2))
            ->method('version')
            ->withConsecutive(
                [static::equalTo('Version'), static::equalTo(MobileDetector::VERSION_TYPE_STRING)],
                [static::equalTo('Firefox'), static::equalTo(MobileDetector::VERSION_TYPE_STRING)]
            )
            ->willReturn(false, '98.0')
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertNull($extension->deviceVersion('Version', MobileDetector::VERSION_TYPE_STRING));
        static::assertSame('98.0', $extension->deviceVersion('Firefox', MobileDetector::VERSION_TYPE_STRING));
    }

    public function testFullViewUrlHostNull(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => null];

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertNull($extension->fullViewUrl());
    }

    public function testFullViewUrlHostEmpty(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => ''];

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertNull($extension->fullViewUrl());
    }

    public function testFullViewUrlNotSetRequest(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertSame('http://mobilehost.com', $extension->fullViewUrl());
    }

    public function testFullViewUrlWithRequestQuery(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->request->query = new ParameterBag(['myparam' => 'myvalue']);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertSame('http://mobilehost.com?myparam=myvalue', $extension->fullViewUrl());
    }

    public function testFullViewUrlWithRequestOnlyHost(): void
    {
        $this->config['full'] = ['is_enabled' => true, 'host' => 'http://mobilehost.com'];

        $this->request->query = new ParameterBag(['myparam' => 'myvalue']);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertSame('http://mobilehost.com', $extension->fullViewUrl(false));
    }

    public function testIsMobileTrue(): void
    {
        $this->mobileDetector->expects(static::once())->method('isMobile')->willReturn(true);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isMobile());
    }

    public function testIsMobileFalse(): void
    {
        $this->mobileDetector->expects(static::once())->method('isMobile')->willReturn(false);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isMobile());
    }

    public function testIsTabletTrue(): void
    {
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(true);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isTablet());
    }

    public function testIsTabletFalse(): void
    {
        $this->mobileDetector->expects(static::once())->method('isTablet')->willReturn(false);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isTablet());
    }

    public function testIsDeviceIPhone(): void
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isiphone'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isDevice('iphone'));
    }

    public function testIsDeviceAndroid(): void
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isandroid'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isDevice('android'));
    }

    public function testIsFullViewTrue(): void
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isFullView());
    }

    public function testIsFullViewFalse(): void
    {
        $deviceView = new DeviceView();
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isFullView());
    }

    public function testIsMobileViewTrue(): void
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isMobileView());
    }

    public function testIsMobileViewFalse(): void
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isMobileView());
    }

    public function testIsTabletViewTrue(): void
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_TABLET]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isTabletView());
    }

    public function testIsTabletViewFalse(): void
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isTabletView());
    }

    public function testIsNotMobileViewTrue(): void
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_NOT_MOBILE]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isNotMobileView());
    }

    public function testIsNotMobileViewFalse(): void
    {
        $this->request->cookies = new ParameterBag([$this->switchParam => DeviceView::VIEW_FULL]);
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isNotMobileView());
    }

    public function testIsIOSTrue(): void
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isIOS'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isIOS());
    }

    public function testIsIOSFalse(): void
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isIOS'))
            ->willReturn(false)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isIOS());
    }

    public function testIsAndroidOSTrue(): void
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isAndroidOS'))
            ->willReturn(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isAndroidOS());
    }

    public function testIsAndroidOSFalse(): void
    {
        $this->mobileDetector->expects(static::once())
            ->method('__call')
            ->with(static::equalTo('isAndroidOS'))
            ->willReturn(false)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isAndroidOS());
    }

    public function testIsWindowsOSTrue(): void
    {
        $this->mobileDetector->expects(static::exactly(1))
            ->method('__call')
            ->withConsecutive(
                [static::equalTo('isWindowsMobileOS')],
                [static::equalTo('isWindowsPhoneOS')]
            )
            ->willReturnOnConsecutiveCalls(true)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertTrue($extension->isWindowsOS());
    }

    public function testIsWindowsOSFalse(): void
    {
        $this->mobileDetector->expects(static::exactly(2))
            ->method('__call')
            ->withConsecutive(
                [static::equalTo('isWindowsMobileOS')],
                [static::equalTo('isWindowsPhoneOS')]
            )
            ->willReturn(false)
        ;
        $deviceView = new DeviceView($this->requestStack);
        $extension = new MobileDetectExtension($this->requestStack, $this->mobileDetector, $deviceView, $this->config);
        static::assertFalse($extension->isWindowsOS());
    }
}
