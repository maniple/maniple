<?php

class Maniple_Application_Resource_LazyResource
    extends Maniple_Application_Resource_ResourceAbstract
{
    /**
     * @var string
     */
    protected $_class;

    /**
     * @var array
     */
    protected $_params;

    /**
     * Sets resource class.
     *
     * @param  string $class
     * @return Maniple_Application_Resource_DeferredResource
     */
    public function setClass($class)
    {
        $this->_class = (string) $class;
        return $this;
    }

    /**
     * Sets initialization parameters.
     *
     * @param  mixed $params
     * @return Maniple_Application_Resource_DeferredResource
     */
    public function setParams($params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * Returns an instantiation data for a given resource.
     *
     * @return array
     * @throws Exception
     */
    public function init()
    {
        if (empty($this->_class)) {
            throw new Exception('Class name of a lazy resource must not be empty upon initialization');
        }
        return array(
            'class' => $this->_class,
            'params' => $this->_params,
        );
    }
}
