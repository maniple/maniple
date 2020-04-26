<?php

/**
 * Retrieves asset contents, useful for embedding scripts, styles or SVG images
 */
class Maniple_View_Helper_AssetContents extends Maniple_View_Helper_Abstract
{
    /**
     * @Inject('Maniple.AssetManager')
     * @var Maniple_AssetMananger_Service
     */
    protected $_assetManager;

    /**
     * @param string $path
     * @param string $moduleName OPTIONAL
     * @return string
     */
    public function assetContents($path, $moduleName = null)
    {
        $filePath = $this->_assetManager->getAssetPath($path, $moduleName);
        if (is_file($filePath) && is_readable(($filePath))) {
            return file_get_contents($filePath);
        }
        return '';
    }
}
