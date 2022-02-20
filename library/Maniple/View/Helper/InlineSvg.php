<?php

/**
 * Helper for insterint inline SVG images in the document
 */
class Maniple_View_Helper_InlineSvg extends Maniple_View_Helper_Abstract
{
    /**
     * @Inject
     * @var Maniple_Assets_AssetManager
     */
    protected $_assetManager;

    /**
     * @param string $path
     * @param array $attribs
     * @return string
     */
    public function inlineSvg($path, array $attribs = array())
    {
        return ($file = $this->_assetManager->getAssetPath($path))
            ? $this->renderSvgContents($file, $attribs)
            : '';
    }

    /**
     * @param string $file
     * @param array $attribs
     * @return string
     */
    public function renderSvgContents($file, array $attribs)
    {
        $svg = file_get_contents($file);
        $svg = preg_replace('/^\s*<\?xml[^?]*\?>\s*/', '', $svg);

        // The xmlns attribute is only required on the outermost svg element of SVG documents.
        // It is unnecessary for inner svg elements or inside HTML documents.
        // https://developer.mozilla.org/en-US/docs/Web/SVG/Element/svg
        $svg = preg_replace('/\s+xmlns(=|:[a-z]+=)(\'[^\']*\'|"[^"]*")/', '', $svg);

        if ($attribs) {
            list($pre, $post) = explode('>', $svg, 2);
            $svg = $pre;
            foreach ($attribs as $key => $value) {
                $svg = preg_replace('/\s+' . preg_quote($key) . '\s*=\s*("[^"]*"|\'[^\']*\')/', '', $svg);
                $svg .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            }
            $svg .= '>' . $post;
        }

        return $svg;
    }
}
