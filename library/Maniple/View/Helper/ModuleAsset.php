<?php

class Maniple_View_Helper_ModuleAsset extends Zend_View_Helper_Abstract
{
    public function moduleAsset($path, $moduleName = null)
    {
        $frontController = Zend_Controller_Front::getInstance();

        if ($moduleName === null) {
            $moduleName = $frontController->getDefaultModule();
        }

        $modules = $frontController->getParam('bootstrap')->getResource('modules');

        if (isset($modules->{$moduleName})) {
            $module = $modules->{$moduleName};
        } else {
            /** @var $modules \Zend\ModuleManager\ModuleManager */
            $modules = $frontController->getParam('bootstrap')->getResource('ModuleManager');

            $name = str_replace(array('.', '-'), ' ', $moduleName);
            $name = ucwords($name);
            $name = str_replace(' ', '', $name);

            $module = $modules->getModule($name);
        }

        if (empty($module)) {
            throw new InvalidArgumentException("Module {$moduleName} was not found");
        }

        if (method_exists($module, 'getAssetsBaseDir')) {
            $baseDir = $module->getAssetsBaseDir();
        } else {
            $baseDir = $moduleName;
        }

        $path = trim($path, '/');

        // TODO get module's assetMap and append checksum for path

        return $this->view->baseUrl('/assets/' . basename($baseDir) . '/' . $path);
    }
}
