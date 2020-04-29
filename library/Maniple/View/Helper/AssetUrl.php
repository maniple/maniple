<?php

/**
 * Generates asset url with cache-busting suffix
 */
class Maniple_View_Helper_AssetUrl extends Maniple_View_Helper_Abstract
{
    /**
     * @Inject
     * @var Maniple_Assets_AssetManager
     */
    protected $_assetManager;

    /**
     * @param string $path
     * @param string $moduleName OPTIONAL
     * @return string
     */
    public function assetUrl($path, $moduleName = null)
    {
        return $this->_assetManager->getAssetUrl($path, $moduleName);
    }
}
