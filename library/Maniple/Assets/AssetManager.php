<?php

/**
 * Generates URLs and paths for module assets.
 *
 * Provides logic for {@link Maniple_View_Helper_Asset} and {@link Maniple_View_Helper_AssetSource}
 * view helpers.
 *
 * @version 2019-02-11
 */
class Maniple_Assets_AssetManager
{
    const className = __CLASS__;

    /**
     * @var Zend_Controller_Front
     */
    protected $_frontController;

    /**
     * @var Zend_View_Abstract
     */
    protected $_view;

    /**
     * @var Maniple_Application_Resource_Modules
     */
    protected $_modules;

    /**
     * @var Maniple_Assets_AssetRegistry
     */
    protected $_assetRegistry;

    /**
     * @param Zend_Controller_Front $frontController
     * @param Zend_View_Abstract $view
     * @param object $modules
     * @param Maniple_Assets_AssetRegistry $assetRegistry
     */
    public function __construct(
        Zend_Controller_Front $frontController,
        Zend_View_Abstract $view,
        $modules,
        Maniple_Assets_AssetRegistry $assetRegistry
    ) {
        $this->_frontController = $frontController;
        $this->_view = $view;
        $this->_modules = $modules;
        $this->_assetRegistry = $assetRegistry;
    }

    /**
     * Get relative URL of an asset.
     *
     * @param string $path       Path to asset relative to module assets/ or public/ folder.
     *                           If contains ':', part of string preceding it will be treated
     *                           as a $moduleName.
     * @param string $moduleName OPTIONAL module name. If not given a front controller's default
     *                           module will be used.
     * @return string
     */
    public function getAssetUrl($path, $moduleName = null)
    {
        list($path, $module, $moduleName) = $this->_getPathAndModule($path, $moduleName);

        if (method_exists($module, 'getAssetsBaseDir')) {
            $baseDir = baseName($module->getAssetsBaseDir());
        } else {
            $baseDir = $moduleName;
        }

        $hash = $this->_assetRegistry->getAssetHash($path, $moduleName);
        $url = $this->_view->baseUrl('/assets/' . $baseDir . '/' . ltrim($path, '/'));

        if ($hash !== null) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . urlencode($hash);
        }

        return $url;
    }

    /**
     * @param string $path
     * @param string $moduleName OPTIONAL
     * @return string|null
     */
    public function getAssetPath($path, $moduleName = null)
    {
        list($path, $module) = $this->_getPathAndModule($path, $moduleName);

        $ref = new ReflectionClass($module);
        $moduleDir = dirname($ref->getFileName());

        $assetsDir = null;
        if (is_dir($moduleDir . '/assets')) {
            $assetsDir = $moduleDir . '/assets';
        } elseif (is_dir($moduleDir . '/public')) {
            $assetsDir = $moduleDir . '/public';
        }

        if (!$assetsDir) {
            return null;
        }

        $assetPath = $assetsDir . '/' . $path;
        if (file_exists($assetPath) && is_readable($assetPath)) {
            return $assetPath;
        }

        return null;
    }

    /**
     * @param string $path
     * @param string $moduleName
     * @return array
     */
    protected function _getPathAndModule($path, $moduleName)
    {
        if (strpos($path, ':') !== false) {
            list($moduleName, $path) = explode(':', $path, 2);
        }

        if ($moduleName === null) {
            $moduleName = $this->_frontController->getDefaultModule();
        }

        if (isset($this->_modules->{$moduleName})) {
            $module = $this->_modules->{$moduleName};
        }

        if (empty($module)) {
            throw new InvalidArgumentException("Module {$moduleName} was not found");
        }

        $path = str_replace('\\', '//', $path);
        $path = preg_replace('/[.]{2,}/', '.', $path);

        return array(ltrim($path, '/'), $module, $moduleName);
    }
}
