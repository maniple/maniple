<?php

/**
 * View helper to render scripts across different modules.
 *
 * This implementation has two advantages over Partial view helper (ZF 1.12.3):
 * - it does not clone view to render script
 * - it correctly determines module views directory based on the output of
 *   Zend_Controller_Action_Helper_ViewRenderer::getViewBasePathSpec(), not
 *   by using a hard-coded directory name
 *
 * @package Maniple_View
 * @version 2014-07-29
 * @author  xemlock
 */
class Maniple_View_Helper_RenderScript extends Zend_View_Helper_Abstract
{
    /**
     * @param  string $script
     * @param  string|array $module OPTIONAL
     * @param  array $vars OPTIONAL
     * @return string
     */
    public function renderScript($script, $module = null, $vars = null) // {{{
    {
        if (is_array($module)) {
            $vars = $module;
            $module = null;
        }

        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');

        $request = $viewRenderer->getRequest();

        $origModule = $request->getModuleName();
        $request->setModuleName($module);

        $moduleDir = $viewRenderer->getModuleDirectory();

        // restore original module name
        $request->setModuleName($origModule);

        // base path is built without using inflector as this method is
        // intended for inline template use only
        // (btw, this is how it should be done in Partial view helper,
        // not by hard-coding views/ subdirectory)
        $viewBasePath = strtr(
            $viewRenderer->getViewBasePathSpec(),
            array(
                ':moduleDir' => $moduleDir,
            )
        );

        // add script base path to view, scripts/ folder is hardcoded in
        // Zend_View_Abstract::setBasePath()
        $origPaths = $this->view->getScriptPaths();
        $this->view->addScriptPath($viewBasePath . '/scripts');

        // TODO add paths to helpers and filters, addBasePath()

        // assign variables before rendering script, this variables will not
        // be removed after rendering
        if (is_array($vars)) {
            $this->view->assign($vars);
        }

        // ensure script has proper suffix (extension)
        if (strpos($viewRenderer->getViewBasePathSpec(), ':suffix') !== false) {
            $suffix = '.' . ltrim($viewRenderer->getViewSuffix(), '.');
            if (substr($script, -strlen($suffix)) !== $suffix) {
                $script .= $suffix;
            }
        }

        $result = $this->view->render($script);

        // TODO cleanup assigned variables, restore variables

        // restore original script paths
        $this->view->setScriptPath(null);
        foreach ($origPaths as $path) {
            $this->view->addScriptPath($path);
        }

        return $result;
    } // }}}
}
