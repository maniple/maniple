<?php

class Maniple_Tool_Provider_Manifest implements Zend_Tool_Framework_Manifest_ProviderManifestable,
    Zend_Tool_Framework_Registry_EnabledInterface
{
    /**
     * @var Zend_Tool_Framework_Registry_Interface
     */
    protected $_registry = null;

    /**
     * @return array
     * @throws Zend_Tool_Framework_Provider_Exception
     */
    public function getProviders()
    {
        return array_merge(
            array(
                Maniple_Tool_Provider_Controller::className,
                Maniple_Tool_Provider_Module::className,
                Maniple_Tool_Provider_Model::className,
                Maniple_Tool_Provider_CliConfig::className,
                Maniple_Tool_Provider_Schema::className,
            ),
            $this->_loadModuleProviders()
        );
    }

    /**
     * @param Zend_Tool_Framework_Registry_Interface $registry
     * @return $this
     */
    public function setRegistry(Zend_Tool_Framework_Registry_Interface $registry)
    {
        $this->_registry = $registry;
        return $this;
    }

    /**
     * Retrieve application instance from client
     *
     * @return Zend_Application
     * @throws Zend_Tool_Framework_Provider_Exception
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

    /**
     * @return array
     * @throws Zend_Tool_Framework_Provider_Exception
     */
    protected function _loadModuleProviders()
    {
        $providers = array();

        if (is_dir('application/modules')) {
            $application = new Zefram_Application(__CLASS__);

            foreach (scandir(APPLICATION_PATH . '/modules') as $module) {
                if ($module === '.' || $module === '..') {
                    continue;
                }

                $bootstrapFile = APPLICATION_PATH . '/modules/' . $module . '/Bootstrap.php';
                if (!file_exists($bootstrapFile)) {
                    continue;
                }

                require_once $bootstrapFile;
                $bootstrapClass = $this->_formatModuleName($module) . '_Bootstrap';

                try {
                    $bootstrap = new $bootstrapClass($application);
                } catch (Zend_Application_Resource_Exception $e) {
                    continue;
                }

                if ($bootstrap instanceof Zend_Tool_Framework_Manifest_ProviderManifestable) {
                    foreach ($bootstrap->getProviders() as $provider) {
                        $providers[] = $provider;
                    }
                    continue;
                }

                $manifestFile = APPLICATION_PATH . '/modules/' . $module . '/Manifest.php';
                if (!file_exists($manifestFile)) {
                    continue;
                }

                $manifestClass = $this->_formatModuleName($module) . '_Manifest';

                if (!class_exists($manifestClass)) {
                    /** @noinspection PhpIncludeInspection */
                    include_once $manifestFile;
                }
                if (!class_exists($manifestClass, false) ||
                    !$this->_isManifestImplementation($manifestClass)
                ) {
                    continue;
                }

                $manifest = new $manifestClass();
                if (method_exists($manifest, 'getProviders')) {
                    foreach ($manifest->getProviders() as $provider) {
                        $providers[] = $provider;
                    }
                }
            }
        }

        return $providers;
    }

    /**
     * Format a module name to the module class prefix
     *
     * @param  string $name
     * @return string
     */
    protected function _formatModuleName($name)
    {
        $name = strtolower($name);
        $name = str_replace(array('-', '.'), ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        return $name;
    }

    /**
     * @param  string $className
     * @return bool
     */
    private function _isManifestImplementation($className)
    {
        $reflectionClass = new ReflectionClass($className);
        return (
            $reflectionClass->implementsInterface('Zend_Tool_Framework_Manifest_Interface')
            && !$reflectionClass->isAbstract()
        );
    }
}
