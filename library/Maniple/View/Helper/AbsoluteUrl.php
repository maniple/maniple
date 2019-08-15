<?php

/**
 * Wrapping url() with serverUrl() to generate absolute route URL is somewhat
 * inconvenient. So instead of writing:
 *
 * <pre>
 * $this->view->serverUrl($this->view->url($name, $options))
 * </pre>
 *
 * you can simply write:
 *
 * <pre>
 * $this->view->absoluteUrl($name, $options);
 * </pre>
 */
class Maniple_View_Helper_AbsoluteUrl extends Zend_View_Helper_Abstract
{
    /**
     * Generates an absolute URL based on a given route
     *
     * @param string $name      Name of a route to use
     * @param array $urlOptions Route options
     * @param bool $reset       Whether or not to reset the route defaults with those provided
     * @param bool $encode      Whether or not to encode URL parts on output
     * @return string
     */
    public function absoluteUrl($name = null, $urlOptions = null, $reset = false, $encode = true)
    {
        return $this->view->serverUrl($this->view->url($name, $urlOptions, $reset, $encode));
    }
}
