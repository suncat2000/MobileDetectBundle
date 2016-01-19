<?php

namespace SunCat\MobileDetectBundle\Tests\RequestListener;

use PHPUnit_Framework_TestCase;
use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use SunCat\MobileDetectBundle\EventListener\RequestListener;
use SunCat\MobileDetectBundle\Helper\DeviceView;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Request Listener Test
 */
class RequestListenerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var PHPUnit_Framework_MockObject_MockBuilder
     */
    private $mobileDetector;

    /**
     * @var PHPUnit_Framework_MockObject_MockBuilder
     */
    private $deviceView;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $serviceContainer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var array
     */
    private $config;

    /**
     * Set up
     */
    public function setUp()
    {
        parent::setUp();

        $test = $this;

        $this->mobileDetector = $this->getMockBuilder('SunCat\MobileDetectBundle\DeviceDetector\MobileDetector')->disableOriginalConstructor()->getMock();
        $this->deviceView = $this->getMockBuilder('SunCat\MobileDetectBundle\Helper\DeviceView')->disableOriginalConstructor()->getMock();
        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')
            ->disableOriginalConstructor()
            ->setMethods(array('getRouteCollection'))
            ->getMock();

        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $this->request->expects($this->any())->method('getScheme')->will($this->returnValue('http'));
        $this->request->expects($this->any())->method('getHost')->will($this->returnValue('testhost.com'));
        $this->request->expects($this->any())->method('getUriForPath')->will($this->returnValue('/'));
        $this->request->query = new ParameterBag();

        $this->serviceContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')->disableOriginalConstructor()->getMock();
        $this->serviceContainer->expects($this->any())
            ->method('get')
            ->with($this->logicalOr(
            $this->equalTo('mobile_detect.mobile_detector'),
            $this->equalTo('mobile_detect.device_view'),
            $this->equalTo('request'),
            $this->equalTo('router')
        ))
            ->will($this->returnCallback(
                function ($param) use ($test) {
                    return $test->serviceContainerReturnsRequestedMockClass($param);
                }
        ));

        $this->config = array(
            'mobile' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'tablet' => array('is_enabled' => false, 'host' => null, 'status_code' => 302, 'action' => 'redirect'),
            'detect_tablet_as_mobile' => false
        );
    }

    /**
     * @param $param
     * @return PHPUnit_Framework_MockObject_MockBuilder
     */
    public function serviceContainerReturnsRequestedMockClass($param)
    {
        switch ($param) {
            case 'mobile_detect.mobile_detector':
                return $this->mobileDetector;
            case 'mobile_detect.device_view':
                return $this->deviceView;
            case 'request':
                return $this->request;
            case 'router':
                return $this->router;
        }
    }

    /**
     * @test
     */
    public function handleRequestHasSwitchParam()
    {
        $listener = new RequestListener($this->serviceContainer, array());
        $this->request->query = new ParameterBag(array('myparam'=>'myvalue','device_view'=>'mobile'));
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(true));
        $this->deviceView
            ->expects($this->once())
            ->method('getRedirectResponseBySwitchParam')
            ->with($this->stringEndsWith('?myparam=myvalue'))
            ->will($this->returnValue($this->getMock('Symfony\Component\HttpFoundation\Response')));
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);
    }


    /**
     * @test
     */
    public function handleRequestHasSwitchParamAndQuery()
    {
        $this->config['mobile'] = array('is_enabled' => true, 'host' => 'http://mobilehost.com');

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $this->request->query = new ParameterBag(array('myparam'=>'myvalue','device_view'=>'mobile'));
        $this->request->expects($this->any())->method('getPathInfo')->will($this->returnValue('/'));
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT, 2)));
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(true));
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue(DeviceView::VIEW_MOBILE));
        $this->deviceView
            ->expects($this->once())
            ->method('getRedirectResponseBySwitchParam')
            ->with($this->stringEndsWith('/?device_view=mobile&myparam=myvalue'))
            ->will($this->returnValue($this->getMock('Symfony\Component\HttpFoundation\Response')));
        $event = $this->createGetResponseEvent('some content');

        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestIsFullView()
    {
        $listener = new RequestListener($this->serviceContainer, array());
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(false));
        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue('full'));
        $this->deviceView->expects($this->never())->method('getRedirectResponse');
        $event = $this->createGetResponseEvent('some content');
        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestIsNotMobileView()
    {
        $listener = new RequestListener($this->serviceContainer, array());
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(false));
        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue('not_mobile'));
        $this->deviceView->expects($this->never())->method('getRedirectResponse');
        $event = $this->createGetResponseEvent('some content');
        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestHasTabletRedirect()
    {
        $this->config['tablet'] = array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123);

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setTabletView');
        $this->deviceView->expects($this->once())->method('isNotMobileView')->will($this->returnValue(false));
        $this->deviceView->expects($this->once())->method('getSwitchParam')->will($this->returnValue('device_view'));
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('tablet'));
        $this->request->query = new ParameterBag(array('some'=>'param'));
        $this->request->expects($this->any())->method('getPathInfo')->will($this->returnValue('/some/parameters'));
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT, 2)));
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');
        $this->deviceView
            ->expects($this->once())
            ->method('getRedirectResponse')
            ->with('tablet', 'http://testsite.com/some/parameters?device_view=tablet&some=param', 123)
            ->will($this->returnValue($response));

        $event = $this->createGetResponseEvent('some content');
        $listener->handleRequest($event);

        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestWithDifferentSwitchParamRedirect()
    {
        $this->config['tablet'] = array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123);

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $switchParam = 'custom_param';

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setTabletView');
        $this->deviceView->expects($this->once())->method('isNotMobileView')->will($this->returnValue(false));
        $this->deviceView->expects($this->once())->method('getSwitchParam')->will($this->returnValue($switchParam));
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('tablet'));
        $this->request->query = new ParameterBag(array('some'=>'param'));
        $this->request->expects($this->any())->method('getPathInfo')->will($this->returnValue('/some/parameters'));
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT, 2)));
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');
        $this->deviceView
            ->expects($this->once())
            ->method('getRedirectResponse')
            ->with('tablet', 'http://testsite.com/some/parameters?'.$switchParam.'=tablet&some=param', 123)
            ->will($this->returnValue($response));

        $event = $this->createGetResponseEvent('some content');
        $listener->handleRequest($event);

        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleDeviceIsTabletAndTabletRedirectIsDisabledAndDetectTabletAsMobileIsFalse()
    {
        $this->config['mobile'] = array('is_enabled' => true, 'host' => 'http://mobilehost.com');

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(true));

        $this->deviceView->expects($this->never())
            ->method('getRedirectResponse')
            ->with('tablet', $this->anything(), $this->anything());
        $this->deviceView->expects($this->never())
            ->method('getRedirectResponse')
            ->with('mobile', $this->anything(), $this->anything());

        $event = $this->createGetResponseEvent('some content');
        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleDeviceIsTabletAndTabletRedirectIsDisabledAndDetectTabletAsMobileIsTrue()
    {
        $this->config['mobile'] = array('is_enabled' => true, 'host' => 'http://mobilehost.com', 'status_code' => 123);
        $this->config['detect_tablet_as_mobile'] = true;

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isMobile')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setMobileView');
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('mobile'));
        $this->deviceView->expects($this->once())->method('getSwitchParam')->will($this->returnValue('device_view'));

        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $this->deviceView->expects($this->once())
            ->method('getRedirectResponse')
            ->with('mobile', 'http://mobilehost.com?device_view=mobile', 123)
            ->will($this->returnValue($response));

        $this->router->expects($this->exactly(2))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT, 2)));

        $event = $this->createGetResponseEvent('some content');
        $listener->handleRequest($event);

        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestHasTabletRedirectWithoutPath()
    {
        $this->config['tablet'] = array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123);

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setTabletView');
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('tablet'));
        $this->deviceView->expects($this->once())->method('getSwitchParam')->will($this->returnValue('device_view'));

        $this->request->expects($this->never())->method('getPathInfo')->will($this->returnValue('/some/parameters'));
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $this->deviceView
            ->expects($this->once())
            ->method('getRedirectResponse')
            ->with('tablet', 'http://testsite.com?device_view=tablet', 123)
            ->will($this->returnValue($response));

        $this->router->expects($this->exactly(2))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT_WITHOUT_PATH, 2)));

        $event = $this->createGetResponseEvent('some content');
        $listener->handleRequest($event);
        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestHasTabletNoRedirect()
    {
        $this->config['tablet'] = array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123);

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setTabletView');
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('tablet'));

        $this->router->expects($this->exactly(1))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::NO_REDIRECT, 1)));
        $event = $this->createGetResponseEvent('some content');
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(false));
        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestHasMobileRedirect()
    {
        $this->config['mobile'] = array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123);

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(false));
        $this->mobileDetector->expects($this->once())->method('isMobile')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setMobileView');
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('mobile'));
        $this->deviceView->expects($this->once())->method('getSwitchParam')->will($this->returnValue('device_view'));

        $this->request->expects($this->once())->method('getPathInfo')->will($this->returnValue('/some/parameters'));
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $this->deviceView
            ->expects($this->once())
            ->method('getRedirectResponse')
            ->with('mobile', 'http://testsite.com/some/parameters?device_view=mobile', 123)
            ->will($this->returnValue($response));
        $this->router
            ->expects($this->exactly(2))
            ->method('getRouteCollection')
            ->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT, 2)));
        $event = $this->createGetResponseEvent('some content');
        $listener->handleRequest($event);

        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestHasMobileRedirectWithoutPath()
    {
        $this->config['mobile'] = array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123);

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(false));
        $this->mobileDetector->expects($this->once())->method('isMobile')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setMobileView');
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('mobile'));
        $this->deviceView->expects($this->once())->method('getSwitchParam')->will($this->returnValue('device_view'));

        $this->request->expects($this->never())->method('getRequestUri')->will($this->returnValue('/some/parameters'));
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $this->deviceView
            ->expects($this->once())
            ->method('getRedirectResponse')
            ->with('mobile', 'http://testsite.com?device_view=mobile', 123)
            ->will($this->returnValue($response));
        $this->router
            ->expects($this->exactly(2))
            ->method('getRouteCollection')
            ->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT_WITHOUT_PATH, 2)));
        $event = $this->createGetResponseEvent('some content');
        $listener->handleRequest($event);

        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestHasMobileNoRedirect()
    {
        $this->config['mobile'] = array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123);

        $listener = new RequestListener($this->serviceContainer, $this->config);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(false));
        $this->mobileDetector->expects($this->once())->method('isMobile')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setMobileView');
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('mobile'));

        $this->router->expects($this->exactly(1))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::NO_REDIRECT, 1)));
        $event = $this->createGetResponseEvent('some content');
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(false));
        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestNeedNotMobileResponseModify()
    {
        $listener = new RequestListener($this->serviceContainer, $this->config);
        $this->deviceView->expects($this->once())->method('isNotMobileView')->will($this->returnValue(true));
        $this->deviceView->expects($this->never())->method('getRequestedViewType');
        $listener->handleRequest($this->createGetResponseEvent('some content'));
    }

    /**
     * @test
     */
    public function handleRequestNeedTabletResponseModify()
    {
        $this->config['mobile'] = array('is_enabled' => true, 'host' => 'http://mobilesite.com', 'status_code' => 123);
        $this->config['tablet'] = array('is_enabled' => false, 'host' => 'http://testhost.com', 'status_code' => 321);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setTabletView');
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('tablet'));

        $event = $this->createGetResponseEvent('some content');

        $listener = new RequestListener($this->serviceContainer, $this->config);
        $listener->handleRequest($event);

        $this->assertTrue($listener->needsResponseModification(), 'needModifyResponse flag should be set');
    }

    /**
     * @test
     */
    public function handleRequestNeedMobileResponseModify()
    {
        $this->config['tablet'] = array('is_enabled' => true, 'host' => 'http://testhost.com', 'status_code' => 321);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(false));
        $this->mobileDetector->expects($this->once())->method('isMobile')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setMobileView');
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('mobile'));

        $event = $this->createGetResponseEvent('some content');

        $listener = new RequestListener($this->serviceContainer, $this->config);
        $listener->handleRequest($event);

        $this->assertTrue($listener->needsResponseModification(), 'needModifyResponse flag should be set');
    }

    /**
     * @test
     */
    public function handleResponse()
    {
        $this->config['tablet'] = array('is_enabled' => true, 'host' => 'http://testhost.com', 'status_code' => 321);

        $this->deviceView->expects($this->once())->method('getRequestedViewType')->will($this->returnValue(null));
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(false));
        $this->mobileDetector->expects($this->once())->method('isMobile')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setMobileView');
        $this->deviceView->expects($this->atLeastOnce())->method('getViewType')->will($this->returnValue('mobile'));

        $event = $this->createGetResponseEvent('some content');

        $listener = new RequestListener($this->serviceContainer, $this->config);
        $listener->handleRequest($event);

        $this->assertTrue($listener->needsResponseModification(), 'needModifyResponse flag should be set');

        $response = new Response('Some content', 200);
        $modifiedResponse = new Response('Some modified content', 200);
        $this->deviceView->expects($this->once())->method('modifyResponse')->will($this->returnValue($modifiedResponse));

        $event = $this->createFilterResponseEvent($response);
        $listener->handleResponse($event);

        // Make sure the response has actually been modified
        $this->assertEquals($modifiedResponse, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestUpdatedMobileDetectorUserAgent()
    {
        $this->mobileDetector->expects($this->once())->method('setUserAgent')->with($this->equalTo('agent'));

        $event = $this->createGetResponseEvent('some content');
        $event->getRequest()->headers->set('user-agent', 'agent');

        $listener = new RequestListener($this->serviceContainer, $this->config);
        $listener->handleRequest($event);
    }

    /**
     * createRouteCollecitonWithRouteAndRoutingOption
     *
     * @param type $returnValue Return value
     * @param type $times       Times
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createRouteCollecitonWithRouteAndRoutingOption($returnValue, $times)
    {
        $route = $this->getMockBuilder('Symfony\Component\Routing\Route')->disableOriginalConstructor()->getMock();
        $route->expects($this->exactly($times))->method('getOption')->will($this->returnValue($returnValue));
        $routeCollection = $this->getMock('Symfony\Component\Routing\RouteCollection');
        $routeCollection->expects($this->exactly($times))->method('get')->will($this->returnValue($route));

        return $routeCollection;
    }

    /**
     * createGetResponseEvent
     *
     * @param type   $content Content
     * @param string $method  Method
     * @param array  $headers Headers
     *
     * @return \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent
     */
    private function createGetResponseEvent($content, $method = 'GET', $headers = array())
    {
        $event = new GetResponseForControllerResultEvent($this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'), $this->request, HttpKernelInterface::MASTER_REQUEST, $content);
        $event->getRequest()->headers = new HeaderBag($headers);

        return $event;
    }

    /**
     * createFilterResponseEvent
     *
     * @param type   $response Response
     * @param string $method   Method
     * @param array  $headers  Headers
     *
     * @return \Symfony\Component\HttpKernel\Event\FilterResponseEvent
     */
    private function createFilterResponseEvent($response, $method = 'GET', $headers = array())
    {
        $event = new FilterResponseEvent($this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'), $this->request, HttpKernelInterface::MASTER_REQUEST, $response);
        $event->getRequest()->headers = new HeaderBag($headers);

        return $event;
    }

}
