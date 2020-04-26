<?php

/**
 * Retrieves asset contents, useful for embedding scripts, styles or SVG images
 *
 * @deprecated Use {@link Maniple_View_Helper_AssetContents}
 */
class Maniple_View_Helper_AssetSource extends Zend_View_Helper_Abstract
{
    /**
     * @param string $path
     * @param string $moduleName OPTIONAL
     * @return string
     */
    public function assetSource($path, $moduleName = null)
    {
        return $this->view->assetContents($path, $moduleName);
    }
}
