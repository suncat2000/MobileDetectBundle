<?php

namespace SunCat\MobileDetectBundle\Tests\RequestListener;

use SunCat\MobileDetectBundle\EventListener\RequestListener,
    SunCat\MobileDetectBundle\DeviceDetector\MobileDetector,
    SunCat\MobileDetectBundle\Helper\DeviceView,

    PHPUnit_Framework_TestCase,
    Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent AS Event,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpKernelInterface,
    Symfony\Component\HttpFoundation\HeaderBag,
    Symfony\Bundle\FrameworkBundle\Routing\Router,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\Routing\RouteCollection,
    Symfony\Component\Routing\Route;

class RequestListenerTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var MobileDetector
     */
    private $mobileDetector;

    /**
     * @var DeviceView
     */
    private $deviceView;

    /**
     * @var Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * @var Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    private $router;

    private $config = array();

    public function setUp() {
        parent::setUp();

        $this->mobileDetector = $this->getMockBuilder('SunCat\MobileDetectBundle\DeviceDetector\MobileDetector')->disableOriginalConstructor()->getMock();
        $this->deviceView = $this->getMockBuilder('SunCat\MobileDetectBundle\Helper\DeviceView')->disableOriginalConstructor()->getMock();
        $this->mobileDetector->expects($this->once())->method('getDeviceView')->will($this->returnValue($this->deviceView));

        $this->router = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Routing\Router')->disableOriginalConstructor()->getMock();
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $this->request->expects($this->once())->method('getScheme')->will($this->returnValue('http'));
        $this->request->expects($this->once())->method('getHost')->will($this->returnValue('testhost.com'));
    }



    /**
     * @test
     */
    public function handleRequestHasSwitchParam()
    {
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, array());
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('getRedirectResponseBySwitchParam')->will($this->returnValue($this->getMock('Symfony\Component\HttpFoundation\Response')));
        $event = $this->createEvent('some content');

        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestIsFullView()
    {
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, array());
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(false));
        $this->deviceView->expects($this->once())->method('isFullView')->will($this->returnValue(true));
        $this->deviceView->expects($this->never())->method('asTabletRedirect');
        $event = $this->createEvent('some content');
        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestIsNotMobileView()
    {
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, array());
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(false));
        $this->deviceView->expects($this->once())->method('isFullView')->will($this->returnValue(false));
        $this->deviceView->expects($this->once())->method('isNotMobileView')->will($this->returnValue(true));
        $this->deviceView->expects($this->never())->method('asTabletRedirect');
        $event = $this->createEvent('some content');
        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestHasTabletRedirect()
    {
        $this->config = array(
            'mobile' => array('is_enabled' => false),
                'tablet' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123)
        );
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, $this->config);
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(true));

        $this->request->expects($this->once())->method('getRequestUri')->will($this->returnValue('/some/parameters'));
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $this->deviceView->expects($this->once())->method('getTabletRedirectResponse')->with('http://testsite.com/some/parameters', 123)->will($this->returnValue($response));
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT, 2)));
        $event = $this->createEvent('some content');
        $listener->handleRequest($event);

        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestHasTabletRedirectWithoutPath()
    {
        $this->config = array(
            'mobile' => array('is_enabled' => false),
            'tablet' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123)
        );
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, $this->config);
        $this->mobileDetector->expects($this->once())->method('isTablet')->will($this->returnValue(true));

        $this->request->expects($this->never())->method('getRequestUri')->will($this->returnValue('/some/parameters'));
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $this->deviceView->expects($this->once())->method('getTabletRedirectResponse')->with('http://testsite.com', 123)->will($this->returnValue($response));
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT_WITHOUT_PATH, 2)));
        $event = $this->createEvent('some content');
        $listener->handleRequest($event);

        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestHasTabletNoRedirect()
    {
        $this->config = array(
            'mobile' => array('is_enabled' => false),
            'tablet' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123)
        );
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, $this->config);
        $this->mobileDetector->expects($this->exactly(2))->method('isTablet')->will($this->returnValue(true));

        $this->router->expects($this->exactly(1))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::NO_REDIRECT, 1)));
        $event = $this->createEvent('some content');
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(false));
        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestHasMobiletRedirect()
    {
        $this->config = array(
            'mobile' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123),
            'tablet' => array('is_enabled' => false)
        );
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, $this->config);
        $this->mobileDetector->expects($this->once())->method('isMobile')->will($this->returnValue(true));

        $this->request->expects($this->once())->method('getRequestUri')->will($this->returnValue('/some/parameters'));
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $this->deviceView->expects($this->once())->method('getMobileRedirectResponse')->with('http://testsite.com/some/parameters', 123)->will($this->returnValue($response));
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT, 2)));
        $event = $this->createEvent('some content');
        $listener->handleRequest($event);

        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestHasMobiletRedirectWithoutPath()
    {
        $this->config = array(
            'mobile' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123),
            'tablet' => array('is_enabled' => false)
        );
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, $this->config);
        $this->mobileDetector->expects($this->once())->method('isMobile')->will($this->returnValue(true));

        $this->request->expects($this->never())->method('getRequestUri')->will($this->returnValue('/some/parameters'));
        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');

        $this->deviceView->expects($this->once())->method('getMobileRedirectResponse')->with('http://testsite.com', 123)->will($this->returnValue($response));
        $this->router->expects($this->exactly(2))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::REDIRECT_WITHOUT_PATH, 2)));
        $event = $this->createEvent('some content');
        $listener->handleRequest($event);

        $this->assertEquals($response, $event->getResponse());
    }

    /**
     * @test
     */
    public function handleRequestHasMobileNoRedirect()
    {
        $this->config = array(
            'mobile' => array('is_enabled' => true, 'host' => 'http://testsite.com', 'status_code' => 123),
            'tablet' => array('is_enabled' => false)
        );
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, $this->config);
        $this->mobileDetector->expects($this->exactly(2))->method('isMobile')->will($this->returnValue(true));

        $this->router->expects($this->exactly(1))->method('getRouteCollection')->will($this->returnValue($this->createRouteCollecitonWithRouteAndRoutingOption(RequestListener::NO_REDIRECT, 1)));
        $event = $this->createEvent('some content');
        $this->deviceView->expects($this->once())->method('hasSwitchParam')->will($this->returnValue(false));
        $listener->handleRequest($event);
    }

    /**
     * @test
     */
    public function handleRequestNeedNotMobileResponseModify()
    {
        $this->config = array(
            'mobile' => array('is_enabled' => false),
            'tablet' => array('is_enabled' => false)
        );
        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, $this->config);
        $this->deviceView->expects($this->once())->method('setNotMobileView');
        $listener->handleRequest($this->createEvent('some content'));
    }

    /**
     * @test
     */
    public function handleRequestNeedTabletResponseModify()
    {
        $this->config = array(
            'mobile' => array('is_enabled' => false, 'host' => 'http://mobilesite.com', 'status_code' => 123),
            'tablet' => array('is_enabled' => true, 'host' => 'http://testhost.com', 'status_code' => 321)git status
        );


        $this->mobileDetector->expects($this->exactly(2))->method('isTablet')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('getViewType')->will($this->returnValue('some device'));
        $this->deviceView->expects($this->once())->method('isTabletView')->will($this->returnValue(true));

      //  $this->deviceView->expects($this->once())->method('modifyTabletResponse')->will($this->returnValue(true));
        $this->deviceView->expects($this->once())->method('setTabletView');
        $event = $this->createEvent('some content');

        $listener = new RequestListener($this->mobileDetector, $this->request, $this->router, $this->config);
        $listener->handleRequest($event);

    }





    /**
     * @param $returnValue
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
     * @param $content
     * @param string $method
     * @param array $headers
     * @return \Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent
     */
    private function createEvent($content, $method = 'GET', $headers = array())
    {
        $server = array('REQUEST_METHOD' => $method);
        $event = new Event($this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface'), $this->request, HttpKernelInterface::MASTER_REQUEST, $content);
        $event->getRequest()->headers = new HeaderBag($headers);
        return $event;
    }




}



