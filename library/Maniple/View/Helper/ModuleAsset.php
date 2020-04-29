<?php

/**
 * @deprected Use {@link Maniple_View_Helper_AssetUrl} instead
 */
class Maniple_View_Helper_ModuleAsset extends Zend_View_Helper_Abstract
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

    public function moduleAsset($path, $moduleName = null)
    {
        return $this->_getAssetManager()->getAssetUrl($path, $moduleName);
    }

    public function addManifest(array $manifest, $moduleName)
    {
        throw new Exception('Add manifest directly to Maniple_Assets_AssetManager service');
    }
}
