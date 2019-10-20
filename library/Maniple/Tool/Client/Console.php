<?php

class Maniple_Tool_Client_Console extends Zefram_Tool_Framework_Client_Console
{
    /**
     * @var Zend_Application|array
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
     * @throws Zend_Config_Exception
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
     * @throws Zend_Config_Exception
     * @throws Zend_Tool_Framework_Client_Exception
     */
    protected function _createApplication($options)
    {
        $options = array_merge(array(
            'class'       => 'Zefram_Application',
            'environment' => 'production',
            'config'      => array(),
        ), (array) $options);

        if (is_string($options['config'])) {
            $config = Zefram_Config::factory($options['config'], $options['environment'])->toArray();
        } else {
            $config = (array) $options['config'];
        }

        if (is_file('application/configs/cli.config.php')) {
            $config['config'][] = 'application/configs/cli.config.php';
        }

        $applicationClass = $options['class'];
        $application = new $applicationClass($options['environment'], $config);

        if (!$application instanceof Zend_Application) {
            throw new Zend_Tool_Framework_Client_Exception(sprintf(
                'Application must be an instance of Zend_Application, %s given',
                get_class($application)
            ));
        }

        return $application;
    }
}
