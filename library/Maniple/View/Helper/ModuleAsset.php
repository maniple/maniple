<?php

class Maniple_View_Helper_ModuleAsset extends Zend_View_Helper_Abstract
{
    /**
     * @var array
     */
    protected $_manifest = array();

    public function moduleAsset($path, $moduleName = null)
    {
        $frontController = Zend_Controller_Front::getInstance();

        if ($moduleName === null) {
            $moduleName = $frontController->getDefaultModule();
        }

        /** @var $modules \Zend\ModuleManager\ModuleManager */
        $modules = $frontController->getParam('bootstrap')->getResource('ModuleManager');
        if ($modules) {
            $name = str_replace(array('.', '-'), ' ', $moduleName);
            $name = ucwords($name);
            $name = str_replace(' ', '', $name);

            $module = $modules->getModule($name);
        } else {
            $modules = $frontController->getParam('bootstrap')->getResource('modules');
            if (isset($modules->{$moduleName})) {
                $module = $modules->{$moduleName};
            }
        }

        if (empty($module)) {
            throw new InvalidArgumentException("Module {$moduleName} was not found");
        }

        if (method_exists($module, 'getAssetsBaseDir')) {
            $baseDir = $module->getAssetsBaseDir();
        } else {
            $baseDir = $moduleName;
        }

        $path = $this->appendAssetHash($path, $moduleName);

        return $this->view->baseUrl('/assets/' . basename($baseDir) . '/' . $path);
    }

    /**
     * @param array $manifest
     * @param string $moduleName
     * @return $this
     */
    public function addManifest(array $manifest, $moduleName)
    {
        $this->_manifest[$moduleName] = array_merge(
            isset($this->_manifest[$moduleName]) ? $this->_manifest[$moduleName] : array(),
            array_map('strval', $manifest)
        );
        return $this;
    }

    /**
     * @param string $path
     * @param string $moduleName
     * @return string
     */
    public function appendAssetHash($path, $moduleName)
    {
        $path = trim($path, '/');
        $hash = null;

        if (isset($this->_manifest[$moduleName][$path])) {
            $hash = $this->_manifest[$moduleName][$path];

            if (strpos($path, '?') !== false) {
                $path .= '&' . $hash;
            } else {
                $path .= '?' . $hash;
            }
        }

        return $path;
    }
}
