<?php

/**
 * @deprected Use {@link Maniple_View_Helper_Asset} instead
 */
class Maniple_View_Helper_ModuleAsset extends Zend_View_Helper_Abstract
{
    public function moduleAsset($path, $moduleName = null)
    {
        return $this->view->asset($path, $moduleName);
    }

    public function addManifest(array $manifest, $moduleName)
    {
        throw new Exception('Add manifest directly to Maniple.AssetManager service');
    }
}
