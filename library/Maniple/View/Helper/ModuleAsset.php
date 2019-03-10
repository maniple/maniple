<?php

/**
 * @deprected Use {@link Maniple_View_Helper_Asset} instead
 */
class Maniple_View_Helper_ModuleAsset extends Zend_View_Helper_Abstract
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

    public function moduleAsset($path, $moduleName = null)
    {
        return $this->_getAssetManager()->getAssetUrl($path, $moduleName);
    }

    public function addManifest(array $manifest, $moduleName)
    {
        throw new Exception('Add manifest directly to Maniple.AssetManager service');
    }
}
