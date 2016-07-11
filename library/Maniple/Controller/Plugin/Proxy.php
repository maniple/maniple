<?php

/**
 * Proxy for lazy plugin initialization
 */
class Maniple_Controller_Plugin_Proxy extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var Zefram_Stdlib_CallbackHandler
     */
    protected $_initializer;

    /**
     * @var Zend_Controller_Plugin_Abstract
     */
    protected $_plugin;

    /**
     * @param callable $initializer
     */
    public function __construct($initializer)
    {
        $this->_initializer = new Zefram_Stdlib_CallbackHandler($initializer);
    }

    /**
     * @return Zend_Controller_Plugin_Abstract
     * @throws Zend_Controller_Exception
     */
    public function getPlugin()
    {
        if (null === $this->_plugin) {
            $plugin = $this->_initializer->call();
            if (!$plugin instanceof Zend_Controller_Plugin_Abstract) {
                throw new Zend_Controller_Exception(
                    'Initializer must return an instance of Zend_Controller_Plugin_Abstract, received %s',
                    is_object($plugin) ? get_class($plugin) : gettype($plugin)
                );
            }
            $this->_plugin = $plugin;
        }
        return $this->_plugin;
    }

    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->getPlugin()->routeStartup($request);
    }

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $this->getPlugin()->routeShutdown($request);
    }

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->getPlugin()->dispatchLoopStartup($request);
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getPlugin()->preDispatch($request);
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getPlugin()->postDispatch($request);
    }

    public function dispatchLoopShutdown()
    {
        $this->getPlugin()->dispatchLoopShutdown();
    }
}
