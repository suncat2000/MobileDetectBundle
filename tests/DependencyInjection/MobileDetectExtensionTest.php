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
use MobileDetectBundle\DeviceDetector\MobileDetector;
use MobileDetectBundle\EventListener\RequestResponseListener;
use MobileDetectBundle\Helper\DeviceView;
use MobileDetectBundle\Twig\Extension\MobileDetectExtension as TwigMobileDetectExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 *
 * @internal
 * @coversNothing
 */
final class MobileDetectExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;
    /**
     * @var MobileDetectExtension
     */
    private $extension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder();
        $this->extension = new MobileDetectExtension();
    }

    public function testLoadDefaultConfig()
    {
        $config = [];
        $this->extension->load($config, $this->container);
        static::assertSame([
            'mobile' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
        static::assertTrue($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
        static::assertSame(DeviceView::COOKIE_KEY_DEFAULT, $this->container->getParameter('mobile_detect.cookie_key'));
        static::assertSame(
            DeviceView::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT,
            $this->container->getParameter('mobile_detect.cookie_expire_datetime_modifier')
        );
        static::assertSame(DeviceView::SWITCH_PARAM_DEFAULT, $this->container->getParameter('mobile_detect.switch_param'));
        static::assertSame(
            MobileDetector::class,
            $this->container->getParameter('mobile_detect.mobile_detector.class')
        );
        static::assertSame(
            DeviceView::class,
            $this->container->getParameter('mobile_detect.device_view.class')
        );
        static::assertSame(
            RequestResponseListener::class,
            $this->container->getParameter('mobile_detect.request_response_listener.class')
        );
        static::assertSame(
            TwigMobileDetectExtension::class,
            $this->container->getParameter('mobile_detect.twig.extension.class')
        );

        static::assertTrue($this->container->hasDefinition('mobile_detect.mobile_detector.default'));
        static::assertTrue($this->container->hasAlias('mobile_detect.mobile_detector'));
    }

    public function testCustomRedirectConfigMobileHost()
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame([
            'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    public function testCustomRedirectConfigWithMobileNotValidHost()
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://testsite', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame([
            'mobile' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    public function testCustomRedirectConfigWithTabletNotValidHost()
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => true, 'host' => 'http://testsite', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame([
            'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => null, 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    public function testCustomRedirectConfigWithFullNotValidHost()
    {
        $config = [
            'mobile_detect' => [
                'redirect' => [
                    'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'tablet' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'full' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
                    'detect_tablet_as_mobile' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame([
            'mobile' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'tablet' => ['is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'full' => ['is_enabled' => false, 'host' => 'http://testsite', 'status_code' => Response::HTTP_FOUND, 'action' => 'redirect'],
            'detect_tablet_as_mobile' => false,
        ], $this->container->getParameter('mobile_detect.redirect'));
    }

    public function testCustomConfigSaveRefererPathTrue()
    {
        $config = [
            'mobile_detect' => [
                'switch_device_view' => [
                    'save_referer_path' => true,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertTrue($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
    }

    public function testCustomConfigSaveRefererPathFalse()
    {
        $config = [
            'mobile_detect' => [
                'switch_device_view' => [
                    'save_referer_path' => false,
                ],
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertFalse($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
    }

    public function testCustomConfigCookieKey()
    {
        $config = [
            'mobile_detect' => [
                'cookie_key' => 'custom_key',
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame('custom_key', $this->container->getParameter('mobile_detect.cookie_key'));
    }

    public function testCustomConfigCookieExpire()
    {
        $config = [
            'mobile_detect' => [
                'cookie_expire_datetime_modifier' => '6 month',
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame('6 month', $this->container->getParameter('mobile_detect.cookie_expire_datetime_modifier'));
    }

    public function testCustomConfigSwitchParam()
    {
        $config = [
            'mobile_detect' => [
                'switch_param' => 'switch_param_custom',
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame('switch_param_custom', $this->container->getParameter('mobile_detect.switch_param'));
    }

    public function testCustomConfigMobileDetectorClass()
    {
        $config = [
            'mobile_detect' => [
                'mobile_detector_class' => 'Bla\Bla\Bla\Class',
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.mobile_detector.class'));
    }

    public function testCustomConfigDeviceViewClass()
    {
        $config = [
            'mobile_detect' => [
                'device_view_class' => 'Bla\Bla\Bla\Class',
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.device_view.class'));
    }

    public function testCustomConfigRequestResponseListenerClass()
    {
        $config = [
            'mobile_detect' => [
                'request_response_listener_class' => 'Bla\Bla\Bla\Class',
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.request_response_listener.class'));
    }

    public function testCustomConfigTwigExtensionClass()
    {
        $config = [
            'mobile_detect' => [
                'twig_extension_class' => 'Bla\Bla\Bla\Class',
            ],
        ];
        $this->extension->load($config, $this->container);
        static::assertSame('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.twig.extension.class'));
    }
}
