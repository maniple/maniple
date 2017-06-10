<?php

class Maniple_Tool_Provider_Abstract extends Zend_Tool_Framework_Provider_Abstract
{
    /**
     * @return Zend_Application
     * @throws Exception
     */
    function getApplication()
    {
        $client = $this->_registry->getClient();
        $application = method_exists($client, 'getApplication') ? $client->getApplication() : null;

        if (!$application instanceof Zend_Application) {
            throw new Exception('Unable to retrieve Zend_Application');
        }

        return $application;
    }
}
