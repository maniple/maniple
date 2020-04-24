<?php

/**
 * @deprecated
 */
class Maniple_Application_Resource_NavigationManager extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * @var Maniple_Navigation_Manager
     */
    protected $_manager;

    /**
     * @return Maniple_Navigation_Manager
     */
    public function getNavigationManager()
    {
        if (null === $this->_manager) {
            $this->_manager = new Maniple_Navigation_Manager();
        }
        return $this->_manager;
    }

    public function init()
    {
        return $this->getNavigationManager();
    }
}
