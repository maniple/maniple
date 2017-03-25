<?php

class Maniple_Controller_Router_Route_RedirectTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $sourceRoute = new Zend_Controller_Router_Route('route/path');
        $route = new Maniple_Controller_Router_Route_Redirect($sourceRoute, array('name' => 'routeName'), 301);

        $this->assertSame($sourceRoute, $route->getRoute());
        $this->assertEquals(array('name' => 'routeName'), $route->getGotoRoute());
        $this->assertEquals(301, $route->getCode());
    }

    /**
     * @expectedException Zend_Controller_Router_Exception
     * @expectedExceptionMessage Invalid HTTP redirection status code: 500
     */
    public function testConstructorWithInvalidCode()
    {
        $sourceRoute = new Zend_Controller_Router_Route('route/path');
        new Maniple_Controller_Router_Route_Redirect($sourceRoute, array(), 500);
    }

    public function testAssemble()
    {
        $route = new Maniple_Controller_Router_Route_Redirect(
            new Zend_Controller_Router_Route('route/:path')
        );

        $assembleData = array('path' => 'path');
        $this->assertEquals($route->assemble($assembleData), $route->getRoute()->assemble($assembleData));
    }

    public function testMatch()
    {
        /** @var  $router */
        $router = new Zend_Controller_Router_Rewrite();
        $router->addConfig(new Zend_Config(array(
            'routeName' => array(
                'route' => 'route/name/path'
            ),
        )));

        $response = new Zend_Controller_Response_HttpTestCase();

        Zend_Controller_Front::getInstance()->setRouter($router);
        Zend_Controller_Front::getInstance()->setResponse($response);

        /** @var Zend_Controller_Action_Helper_Redirector $redirector */
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
        $redirector->setPrependBase(false);
        $redirector->setExit(false);

        $route = new Maniple_Controller_Router_Route_Redirect(
            new Zend_Controller_Router_Route('route/path'),
            array('name' => 'routeName'),
            301
        );
        $route->match('route/path');

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(301, $response->getHttpResponseCode());
        $this->assertEquals(array(
            array(
                'name'    => 'Location',
                'value'   => '/route/name/path',
                'replace' => true,
            )
        ), $response->getHeaders());
    }

    public function testGetInstanceWithFullConfig()
    {
        $route = Maniple_Controller_Router_Route_Redirect::getInstance(
            new Zend_Config(array(
                'type' => 'Maniple_Controller_Router_Route_Redirect',
                'code' => 302,
                'route' => array(
                    'route' => 'route/path',
                    'type' => 'Zend_Controller_Router_Route',
                    'defaults' => array()
                ),
                'gotoRoute' => array(
                    'name' => 'routeName',
                ),
            ))
        );

        $this->assertInstanceOf('Maniple_Controller_Router_Route_Redirect', $route);
        $this->assertEquals($route->getCode(), 302);

        $this->assertInstanceOf('Zend_Controller_Router_Route', $route->getRoute());
        $this->assertTrue(false !== $route->getRoute()->match('route/path'));

        $this->assertEquals(array('name' => 'routeName'), $route->getGotoRoute());
    }

    public function testGetInstanceWithShortConfig()
    {
        $route = Maniple_Controller_Router_Route_Redirect::getInstance(
            new Zend_Config(array(
                'type' => 'Maniple_Controller_Router_Route_Redirect',
                'code' => 301,
                'route' => 'route/path',
                'gotoRoute' => 'routeName',
            ))
        );

        $this->assertInstanceOf('Maniple_Controller_Router_Route_Redirect', $route);
        $this->assertEquals($route->getCode(), 301);

        $this->assertInstanceOf('Zend_Controller_Router_Route', $route->getRoute());
        $this->assertTrue(false !== $route->getRoute()->match('route/path'));

        $this->assertEquals(array('name' => 'routeName'), $route->getGotoRoute());
    }

}
