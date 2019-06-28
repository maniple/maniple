<?php

class Maniple_Tool_Provider_Manifest implements Zend_Tool_Framework_Manifest_ProviderManifestable
{
    public function getProviders()
    {
        return array_merge(
            array(
                Maniple_Tool_Provider_Controller::className,
                Maniple_Tool_Provider_Module::className,
                Maniple_Tool_Provider_Model::className,
                Maniple_Tool_Provider_CliConfig::className,
            ),
            $this->_loadModuleProviders()
        );
    }

    /**
     * @return array
     */
    protected function _loadModuleProviders()
    {
        $providers = array();

        if (is_dir('application/modules')) {
            foreach (scandir('application/modules') as $module) {
                if ($module === '.' || $module === '..') {
                    continue;
                }

                $manifestFile = 'application/modules/' . $module . '/Manifest.php';
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
