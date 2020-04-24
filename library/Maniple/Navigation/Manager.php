<?php

/**
 * @deprecated Use {@link Maniple_Menu_MenuManager}
 */
class Maniple_Navigation_Manager
{
    /**
     * Array of navigation containers
     *
     * @var array
     */
    protected $_containers = array();

    /**
     * @param string $name
     * @param array|Zend_Navigation_Container|Zend_Config $container OPTIONAL
     * @return Maniple_Navigation_Manager
     * @throws Zend_Navigation_Exception
     */
    public function setContainer($name, $container = null)
    {
        switch (true) {
            case null === $container:
            case is_array($container):
            case $container instanceof Zend_Navigation_Container:
            case $container instanceof Zend_Config:
                $this->_containers[$name] = $container;
                break;

            default:
                throw new Zend_Navigation_Exception(sprintf(
                    'Navigation must be an array or an instance of Zend_Navigation_Container or Zend_Config, %s given',
                    is_object($container) ? get_class($container) : gettype($container)
                ));
        }
        return $this;
    }

    /**
     * @param string $name
     * @return Zend_Navigation_Container
     * @throws Zend_Navigation_Exception
     */
    public function getContainer($name)
    {
        if ($this->hasContainer($name)) {
            $container = $this->_containers[$name];
            if (!$container instanceof Zend_Navigation_Container) {
                $container = new Zend_Navigation($container);
                $this->_containers[$name] = $container;
            }
            return $container;
        }
        throw new Zend_Navigation_Exception(sprintf('Invalid navigation container name: %s', $name));
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasContainer($name)
    {
        return isset($this->_containers[$name]) || array_key_exists($name, $this->_containers);
    }
}
