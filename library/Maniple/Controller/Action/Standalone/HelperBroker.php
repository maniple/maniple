<?php

/**
 * Proxy imitating action controller's helper broker.
 *
 * @version 2013-06-30
 */
class Maniple_Controller_Action_Standalone_HelperBroker
{
    /**
     * @var Maniple_Controller_Action_Standalone
     */
    protected $_standaloneAction;

    /**
     * @param Maniple_Controller_Action_Standalone
     */
    public function __construct(Maniple_Controller_Action_Standalone $action)
    {
        $this->_standaloneAction = $action;
    }

    /**
     * Invoke direct() method on a given helper.
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     * @throws Maniple_Controller_Action_Exception_BadMethodCall
     *         If helper does not have a direct() method.
     */
    public function __call($method, $args)
    {
        $helper = $this->_standaloneAction->getHelper($method);

        if (!method_exists($helper, 'direct')) {
            throw new Maniple_Controller_Action_Exception_BadMethodCall(
                "Helper '$method' does not support overloading via direct()"
            );
        }

        return call_user_func_array(array($helper, 'direct'), $args);
    }

    /**
     * Get action helper by name.
     *
     * @param  string $helper
     * @return Zend_Controller_Action_Helper_Abstract
     */
    public function __get($helper)
    {
        return $this->_standaloneAction->getHelper($helper);
    }
}
