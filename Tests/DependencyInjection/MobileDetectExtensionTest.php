<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SunCat\MobileDetectBundle\Tests\DependencyInjection;

use PHPUnit_Framework_MockObject_MockBuilder;
use PHPUnit\Framework\TestCase;
use SunCat\MobileDetectBundle\DependencyInjection\MobileDetectExtension;
use SunCat\MobileDetectBundle\Helper\DeviceView;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * MobileDetectExtensionTest
 *
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
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
     * Set up
     */
    public function setUp()
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
        $config = array();
        $this->extension->load($config, $this->container);
        $this->assertEquals(array(
            'mobile' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'tablet' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'full' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'detect_tablet_as_mobile' => false
        ), $this->container->getParameter('mobile_detect.redirect'));
        $this->assertTrue($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
        $this->assertEquals(DeviceView::COOKIE_KEY_DEFAULT, $this->container->getParameter('mobile_detect.cookie_key'));
        $this->assertEquals(
            DeviceView::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT,
            $this->container->getParameter('mobile_detect.cookie_expire_datetime_modifier')
        );
        $this->assertEquals(DeviceView::SWITCH_PARAM_DEFAULT, $this->container->getParameter('mobile_detect.switch_param'));
        $this->assertEquals(
            'SunCat\MobileDetectBundle\DeviceDetector\MobileDetector',
            $this->container->getParameter('mobile_detect.mobile_detector.class')
        );
        $this->assertEquals(
            'SunCat\MobileDetectBundle\Helper\DeviceView',
            $this->container->getParameter('mobile_detect.device_view.class')
        );
        $this->assertEquals(
            'SunCat\MobileDetectBundle\EventListener\RequestResponseListener',
            $this->container->getParameter('mobile_detect.request_response_listener.class')
        );
        $this->assertEquals(
            'SunCat\MobileDetectBundle\Twig\Extension\MobileDetectExtension',
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
        $config = array(
            'mobile_detect' => array(
                'redirect' => array(
                    'mobile' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'),
                    'tablet' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
                    'full' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
                    'detect_tablet_as_mobile' => false
                )
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals(array(
            'mobile' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'),
            'tablet' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'full' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'detect_tablet_as_mobile' => false
        ), $this->container->getParameter('mobile_detect.redirect'));
    }

    /**
     * @test
     */
    public function customRedirectConfigWithMobileNotValidHost()
    {
        $config = array(
            'mobile_detect' => array(
                'redirect' => array(
                    'mobile' => array('is_enabled' => true, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'),
                    'tablet' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
                    'full' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
                    'detect_tablet_as_mobile' => false
                )
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals(array(
            'mobile' => array('is_enabled' => false, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'),
            'tablet' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'full' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'detect_tablet_as_mobile' => false
        ), $this->container->getParameter('mobile_detect.redirect'));
    }

    /**
     * @test
     */
    public function customRedirectConfigWithTabletNotValidHost()
    {
        $config = array(
            'mobile_detect' => array(
                'redirect' => array(
                    'mobile' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'),
                    'tablet' => array('is_enabled' => true, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'),
                    'full' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
                    'detect_tablet_as_mobile' => false
                )
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals(array(
            'mobile' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'),
            'tablet' => array('is_enabled' => false, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'),
            'full' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'detect_tablet_as_mobile' => false
        ), $this->container->getParameter('mobile_detect.redirect'));
    }

    /**
     * @test
     */
    public function customRedirectConfigWithFullNotValidHost()
    {
        $config = array(
            'mobile_detect' => array(
                'redirect' => array(
                    'mobile' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'),
                    'tablet' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'),
                    'full' => array('is_enabled' => false, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'),
                    'detect_tablet_as_mobile' => false
                )
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals(array(
            'mobile' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'),
            'tablet' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 302, 'action' => 'redirect'),
            'full' => array('is_enabled' => false, 'host' => 'http://testsite', 'status_code' => 302, 'action' => 'redirect'),
            'detect_tablet_as_mobile' => false
        ), $this->container->getParameter('mobile_detect.redirect'));
    }

    /**
     * @test
     */
    public function customConfigSaveRefererPathTrue()
    {
        $config = array(
            'mobile_detect' => array(
                'switch_device_view' => array(
                    'save_referer_path' => true
                )
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertTrue($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
    }

    /**
     * @test
     */
    public function customConfigSaveRefererPathFalse()
    {
        $config = array(
            'mobile_detect' => array(
                'switch_device_view' => array(
                    'save_referer_path' => false
                )
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertFalse($this->container->getParameter('mobile_detect.switch_device_view.save_referer_path'));
    }

    /**
     * @test
     */
    public function customConfigCookieKey()
    {
        $config = array(
            'mobile_detect' => array(
                'cookie_key' => 'custom_key'
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals('custom_key', $this->container->getParameter('mobile_detect.cookie_key'));
    }

    /**
     * @test
     */
    public function customConfigCookieExpire()
    {
        $config = array(
            'mobile_detect' => array(
                'cookie_expire_datetime_modifier' => '6 month'
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals('6 month', $this->container->getParameter('mobile_detect.cookie_expire_datetime_modifier'));
    }

    /**
     * @test
     */
    public function customConfigSwitchParam()
    {
        $config = array(
            'mobile_detect' => array(
                'switch_param' => 'switch_param_custom'
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals('switch_param_custom', $this->container->getParameter('mobile_detect.switch_param'));
    }

    /**
     * @test
     */
    public function customConfigMobileDetectorClass()
    {
        $config = array(
            'mobile_detect' => array(
                'mobile_detector_class' => 'Bla\Bla\Bla\Class'
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.mobile_detector.class'));
    }

    /**
     * @test
     */
    public function customConfigDeviceViewClass()
    {
        $config = array(
            'mobile_detect' => array(
                'device_view_class' => 'Bla\Bla\Bla\Class'
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.device_view.class'));
    }

    /**
     * @test
     */
    public function customConfigRequestResponseListenerClass()
    {
        $config = array(
            'mobile_detect' => array(
                'request_response_listener_class' => 'Bla\Bla\Bla\Class'
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.request_response_listener.class'));
    }

    /**
     * @test
     */
    public function customConfigTwigExtensionClass()
    {
        $config = array(
            'mobile_detect' => array(
                'twig_extension_class' => 'Bla\Bla\Bla\Class'
            )
        );
        $this->extension->load($config, $this->container);
        $this->assertEquals('Bla\Bla\Bla\Class', $this->container->getParameter('mobile_detect.twig.extension.class'));
    }
}
