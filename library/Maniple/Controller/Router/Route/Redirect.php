<?php

/**
 * Class Maniple_Controller_Router_Route_Redirect
 *
 * Config specification:
 *
 * <pre>
 * array(
 *     'type' => 'Maniple_Controller_Router_Route_Redirect',
 *     'code' => 302,
 *     'route' => array(
 *         'route' => 'route/path',
 *         'type' => 'Zend_Controller_Router_Route',
 *         'defaults' => array()
 *     ),
 *     'gotoRoute' => array(
 *         'name' => 'routeName',
 *         'reset' => false,
 *         'encode' => true,
 *         'urlOptions' => array()
 *     ),
 * )
 * </pre>
 *
 * a shorthand version is also available, which uses default route type,
 * and default redirection params:
 *
 * <pre>
 * array(
 *     'type' => 'Maniple_Controller_Router_Route_Redirect',
 *     'route' => 'route/path',
 *     'defaults' => array(),
 *     'gotoRoute' => 'routeName',
 * )
 * </pre>
 *
 */
class Maniple_Controller_Router_Route_Redirect implements Zend_Controller_Router_Route_Interface
{
    /**
     * HTTP status code to use in headers
     *
     * @var int
     */
    protected $_code = 302;

    /**
     * @var string
     */
    protected $_gotoRoute;

    /**
     * @var Zend_Controller_Router_Route_Interface
     */
    protected $_route;

    /**
     * @param Zend_Controller_Router_Route_Interface|string $route
     * @param array $gotoRoute
     * @param int $statusCode
     */
    public function __construct(
        Zend_Controller_Router_Route_Interface $route, array $gotoRoute = array(), $statusCode = null
    ) {
        $this->_route = $route;
        $this->_gotoRoute = $gotoRoute;

        if ($statusCode !== null) {
            $this->_setCode($statusCode);
        }
    }

    /**
     * @param int $code
     * @return Maniple_Controller_Router_Route_Redirect
     * @throws Zend_Controller_Router_Exception
     */
    protected function _setCode($code)
    {
        if (!is_int($code) || (300 > $code) || (399 < $code)) {
            throw new Zend_Controller_Router_Exception('Invalid HTTP redirection status code: ' . $code);
        }
        $this->_code = $code;
        return $this;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->_code;
    }

    /**
     * @return Zend_Controller_Router_Route_Interface
     */
    public function getRoute()
    {
        return $this->_route;
    }

    /**
     * @return array
     */
    public function getGotoRoute()
    {
        return $this->_gotoRoute;
    }

    public function match($path, $partial = false)
    {
        if (false !== ($matchParams = $this->_route->match($path, $partial))) {
            /** @var Zend_Controller_Action_Helper_Redirector $helper */
            $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
            $helper->setCode($this->_code);

            $gotoRoute = $this->_gotoRoute + array(
                'name'       => null,
                'urlOptions' => array(),
                'reset'      => false,
                'encode'     => true
            );

            $urlOptions = array_merge($matchParams, (array) $gotoRoute['urlOptions']);

            /** @var mixed $name */
            /** @var mixed $reset */
            /** @var mixed $encode */
            extract($gotoRoute, EXTR_SKIP);

            $helper->gotoRoute($urlOptions, $name, $reset, $encode);
        }
    }

    public function assemble($data = array(), $reset = false, $encode = false)
    {
        return $this->_route->assemble($data, $reset, $encode);
    }

    /**
     * @param Zend_Config $config
     * @return Maniple_Controller_Router_Route_Redirect
     */
    public static function getInstance(Zend_Config $config)
    {
        if ($config->route instanceof Zend_Config) {
            $routeConfig = $config->route;
            $routeClass = $routeConfig->type;
        } else {
            $routeConfig = $config;
        }

        if (empty($routeClass)) {
            $routeClass = 'Zend_Controller_Router_Route';
        }

        $route = $routeClass::getInstance($routeConfig);

        $gotoRoute = $config->gotoRoute instanceof Zend_Config
            ? $config->gotoRoute->toArray()
            : array('name' => (string) $config->gotoRoute);


        return new self($route, $gotoRoute, $config->code);
    }
}
