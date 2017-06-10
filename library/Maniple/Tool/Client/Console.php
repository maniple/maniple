<?php

class Maniple_Tool_Client_Console extends Zefram_Tool_Framework_Client_Console
{
    /**
     * @var Zend_Application
     */
    protected $_application;

    /**
     * @param Zend_Application|array $application
     * @return $this
     */
    public function setApplication($application)
    {
        $this->_application = $application;
        return $this;
    }

    /**
     * @return Zend_Application
     * @throws Zend_Tool_Framework_Client_Exception
     */
    public function getApplication()
    {
        if (!$this->_application instanceof Zend_Application) {
            $this->_application = $this->_createApplication($this->_application);
        }
        return $this->_application;
    }

    /**
     * @param array $options
     * @return Zend_Application
     * @throws Zend_Tool_Framework_Client_Exception
     */
    protected function _createApplication($options)
    {
        $options = array_merge(array(
            'class'       => 'Zefram_Application',
            'environment' => 'production',
            'config'      => array(),
        ), (array) $options);

        $applicationClass = $options['class'];
        $application = new $applicationClass($options['environment'], $options['config']);

        if (!$application instanceof Zend_Application) {
            throw new Zend_Tool_Framework_Client_Exception(sprintf(
                'Application must be an instance of Zend_Application, %s given',
                get_class($application)
            ));
        }

        return $application;
    }
}
