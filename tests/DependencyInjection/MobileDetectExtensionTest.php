<?php
/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MobileDetectBundle\Tests\DependencyInjection;

use MobileDetectBundle\DependencyInjection\MobileDetectExtension;
use MobileDetectBundle\Helper\DeviceView;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 *
 * @internal
 * @coversNothing
 */
class MobileDetectExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;
    /**
     * @var MobileDetectExtension
     */
    private $extension;

    /**
     * Set up.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder();
        $this->extension = new MobileDetectExtension();
    }

    /**
     * @test
     */
    public function loadDefaultConfig()
    {
        $config = [];
        $this->extension->load($config, $this->container);
        $this->assertEquals([
            'mobile' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
        $this->assertTrue($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
        $this->assertEquals(DeviceView::COOKIE_KEY_DEFAULT, $this->container->getParameter('mobile_detect.cookie_key'));
        $this->assertEquals(
            DeviceView::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT,
            $this->container->getParameter('mobile_detect.cookie_expire_datetime_modifier')
        );
        $this->assertEquals(DeviceView::SWITCH_PARAM_DEFAULT, $this->container->getParameter('mobile_detect.switch_param'));
        $this->assertEquals(
            'MobileDetectBundle\DeviceDetector\MobileDetector',
            $this->container->getParameter('mobile_detect.mobile_detector.class')
        );
        $this->assertEquals(
            'MobileDetectBundle\Helper\DeviceView',
            $this->container->getParameter('mobile_detect.device_view.class')
        );
        $this->assertEquals(
            'MobileDetectBundle\EventListener\RequestResponseListener',
            $this->container->getParameter('mobile_detect.request_response_listener.class')
        );
        $this->assertEquals(
            'MobileDetectBundle\Twig\Extension\MobileDetectExtension',
            $this->container->getParameter('mobile_detect.twig.extension.class')
        );

        $this->assertTrue($this->container->hasDefinition('mobile_detect.mobile_detector.default'));
        $this->assertTrue($this->container->hasAlias('mobile_detect.mobile_detector'));
    }

    /**
     * @test
     */
    public function customRedirectConfigMobileHost()
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals([
            'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    /**
     * @test
     */
    public function customRedirectConfigWithMobileNotValidHost()
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals([
            'mobile' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    /**
     * @test
     */
    public function customRedirectConfigWithTabletNotValidHost()
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => true, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals([
            'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    /**
     * @test
     */
    public function customRedirectConfigWithFullNotValidHost()
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals([
            'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    /**
     * @test
     */
    public function customConfigSaveRefererPathTrue()
    {
        $config = [
            'mobile_detect' => [
                'switch_device_view' => [
                    'save_referer_path' => true,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertTrue($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
    }

    /**
     * @test
     */
    public function customConfigSaveRefererPathFalse()
    {
        $config = [
            'mobile_detect' => [
                'switch_device_view' => [
                    'save_referer_path' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertFalse($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
    }

    /**
     * @test
     */
    public function customConfigCookieKey()
    {
        $config = [
            'mobile_detect' => [
                'cookie_key' => 'custom_key',
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals('custom_key', $this->container->getParameter('mobile_detect.cookie_key'));
    }

    /**
     * @test
     */
    public function customConfigCookieExpire()
    {
        $config = [
            'mobile_detect' => [
                'cookie_expire_datetime_modifier' => '6 month',
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals('6 month', $this->container->getParameter('mobile_detect.cookie_expire_datetime_modifier'));
    }

    /**
     * @test
     */
    public function customConfigSwitchParam()
    {
        $config = [
            'mobile_detect' => [
                'switch_param' => 'switch_param_custom',
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals('switch_param_custom', $this->container->getParameter('mobile_detect.switch_param'));
    }

    /**
     * @test
     */
    public function customConfigMobileDetectorClass()
    {
        $config = [
            'mobile_detect' => [
                'mobile_detector_class' => 'Bla\Bla\Bla\Class',
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.mobile_detector.class'));
    }

    /**
     * @test
     */
    public function customConfigDeviceViewClass()
    {
        $config = [
            'mobile_detect' => [
                'device_view_class' => 'Bla\Bla\Bla\Class',
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.device_view.class'));
    }

    /**
     * @test
     */
    public function customConfigRequestResponseListenerClass()
    {
        $config = [
            'mobile_detect' => [
                'request_response_listener_class' => 'Bla\Bla\Bla\Class',
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.request_response_listener.class'));
    }

    /**
     * @test
     */
    public function customConfigTwigExtensionClass()
    {
        $config = [
            'mobile_detect' => [
                'twig_extension_class' => 'Bla\Bla\Bla\Class',
            ],
        ];
        $this->extension->load($config, $this->container);
        $this->assertEquals('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.twig.extension.class'));
    }
}
