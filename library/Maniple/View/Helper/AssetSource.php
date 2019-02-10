<?php

/**
 * Retrieves asset contents, useful for embedding scripts, styles or SVG images
 *
 * @version 2019-02-11
 */
class Maniple_View_Helper_AssetSource extends Zend_View_Helper_Abstract
{
    /**
     * @return Maniple_AssetMananger_Service
     */
    protected function _getAssetManager()
    {
        /** @var Maniple_AssetMananger_Service $assetManager */
        $assetManager = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('Maniple.AssetManager');
        return $assetManager;
    }

    /**
     * @param string $path
     * @param string $moduleName OPTIONAL
     * @return string
     */
    public function assetSource($path, $moduleName = null)
    {
        $filePath = $this->_getAssetManager()->getAssetPath($path, $moduleName);
        if (is_file($filePath) && is_readable(($filePath))) {
            return file_get_contents($filePath);
        }
        return '';
    }
}
