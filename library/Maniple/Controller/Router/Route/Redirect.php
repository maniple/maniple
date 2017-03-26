<?php

/**
 * Class Maniple_Controller_Router_Route_Redirect
 *
 * Config specification:
 *
 * <pre>
 * array(
 *     'type' => 'Maniple_Controller_Router_Route_Redirect',
 *     'route' => 'route/path',
 *     'defaults' => array(),
 *     'code' => 302,
 *     'goto' => 'redirect/path',
 * )
 * </pre>
 *
 * HTTP status code provided in 'code' key is optional, default is 302.
 * Along with redirection headers a Cache-Control header is sent, to prevent
 * permanent caching in browser for 301 redirects.
 *
 * Setting 'goto' as string is equivalent to setting it as
 * <pre>
 * 'goto' => array(
 *     'route' => 'redirect/path'
 * )
 * </pre>
 *
 * Additionally any parameter placeholders will be replaced with matching
 * values from 'defaults' array, or you can specify them explicitly, along
 * with other parameters:
 *
 * <pre>
 * 'goto' => array(
 *     'route' => 'redirect/path',
 *     'defaults' => array(),
 *     'reset'  => false,
 *     'encode' => true,
 * )
 * </pre>
 *
 * Route in 'goto' is first matched against route names defined in router,
 * when no match was found it is assumed bo be an url.
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
     * Redirection url
     * @var array
     */
    protected $_goto = array();

    /**
     * Internal route used for matching
     * @var Zend_Controller_Router_Route_Interface
     */
    protected $_route;

    /**
     * @param Zend_Controller_Router_Route_Interface|string $route
     * @param string|array $goto
     * @param int $statusCode
     */
    public function __construct(
        Zend_Controller_Router_Route_Interface $route, $goto = null, $statusCode = null
    ) {
        $this->_route = $route;

        if ($goto !== null) {
            $this->_setGoto($goto);
        }

        if ($statusCode !== null) {
            $this->_setCode($statusCode);
        }
    }

    /**
     * @param string|array $goto
     * @throws Zend_Controller_Router_Exception
     */
    protected function _setGoto($goto)
    {
        if (is_string($goto)) {
            $goto = array('route' => $goto);
        }
        if (!is_array($goto)) {
            throw new Zend_Controller_Router_Exception('Goto is expected to be an array or string, received ' . gettype($goto));
        }
        $this->_goto = $goto;
    }

    /**
     * @param int $code
     * @return void
     * @throws Zend_Controller_Router_Exception
     */
    protected function _setCode($code)
    {
        $code = (int) $code;
        if ((300 > $code) || (307 < $code) || (304 == $code) || (306 == $code)) {
            throw new Zend_Controller_Router_Exception('Invalid redirect HTTP status code (' . $code  . ')');
        }
        $this->_code = $code;
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
    public function getGoto()
    {
        return $this->_goto;
    }

    public function match($path, $partial = false)
    {
        if (false !== ($matchParams = $this->_route->match($path, $partial))) {
            /** @var Zend_Controller_Action_Helper_Redirector $helper */
            $helper = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');

            $gotoRoute = $this->_goto + array(
                'route'    => null,
                'defaults' => array(),
                'reset'    => false,
                'encode'   => true
            );

            $urlOptions = array_merge($matchParams, (array) $gotoRoute['defaults']);

            /** @var mixed $route */
            /** @var mixed $reset */
            /** @var mixed $encode */
            extract($gotoRoute, EXTR_SKIP);

            try {
                $router = $helper->getFrontController()->getRouter();
                $url = $router->assemble($urlOptions, $route, $reset, $encode);

            } catch (Zend_Controller_Router_Exception $e) {
                $url = $gotoRoute['route'];
                $isAbsolute = preg_match('|^[a-z]+://|', $url);

                if (!$isAbsolute) {
                    $baseUrl = trim($helper->getFrontController()->getBaseUrl(), '/');
                    $url = $baseUrl . '/' . ltrim($url, '/');
                }
            }

            $helper->getResponse()->setHeader('Cache-Control', 'max-age=3600', true);
            $helper->gotoUrl($url, array(
                'code'        => $this->_code,
                'prependBase' => false,
            ));
        }

        return false;
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
        $goto = $config->goto instanceof Zend_Config  ? $config->goto->toArray() : $config->goto;

        return new self($route, $goto, $config->code);
    }
}
