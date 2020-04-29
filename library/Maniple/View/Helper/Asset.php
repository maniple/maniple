<?php

/**
 * Generates asset url with cache-busting suffix
 *
 * @version 2019-02-11
 * @deprecated Use Maniple_View_Helper_AssetUrl
 */
class Maniple_View_Helper_Asset extends Zend_View_Helper_Abstract
{
    /**
     * @return Maniple_Assets_AssetManager
     */
    protected function _getAssetManager()
    {
        /** @var Maniple_Assets_AssetManager $assetManager */
        $assetManager = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('Maniple_Assets_AssetManager');
        return $assetManager;
    }

    /**
     * @param string $path
     * @param string $moduleName OPTIONAL
     * @return string
     */
    public function asset($path, $moduleName = null)
    {
        return $this->_getAssetManager()->getAssetUrl($path, $moduleName);
    }
}
