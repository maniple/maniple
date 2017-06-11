<?php

class Maniple_Tool_Provider_Abstract extends Zend_Tool_Framework_Provider_Abstract
{
    /**
     * Retrieve application instance from client
     *
     * @return Zend_Application
     * @throws Exception
     */
    protected function _getApplication()
    {
        $client = $this->_registry->getClient();
        $application = method_exists($client, 'getApplication') ? $client->getApplication() : null;

        if (!$application instanceof Zend_Application) {
            throw new Zend_Tool_Framework_Provider_Exception('Unable to retrieve Zend_Application from client');
        }

        return $application;
    }
}
