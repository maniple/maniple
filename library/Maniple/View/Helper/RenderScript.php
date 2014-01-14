<?php

/**
 * Helper to render scripts across different modules
 *
 * @package Maniple_View
 * @version 2014-01-14
 * @author  xemlock
 */
class Maniple_View_Helper_RenderScript extends Zend_View_Helper_Abstract
{
    /**
     * @param  string $script
     * @param  string $module OPTIONAL
     * @return string
     */
    public function renderScript($script, $module = null) // {{{
    {
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');

        // strip off current view suffix from script name
        $suffix = '.' . $viewRenderer->getViewSuffix();
        if (substr($script, -strlen($suffix)) === $suffix) {
            $script = substr($script, 0, -strlen($suffix));
        }

        if (false !== strpos($script, '/')) {
            list($controller, $action) = explode('/', $script, 2);
        } else {
            $controller = null;
            $action = $script;
        }

        $request = $viewRenderer->getRequest();

        if (empty($controller)) {
            $controller = $request->getControllerName();
        }

        $origModule = $request->getModuleName();
        $request->setModuleName($module);

        $moduleDir = $viewRenderer->getModuleDirectory();

        // restore original module name
        $request->setModuleName($origModule);

        // path to script is built without using inflector as this method is
        // intended for inline template use only
        $viewScriptPath = strtr(
            $viewRenderer->getViewScriptPathSpec(),
            array(
                ':moduleDir'  => $moduleDir,
                ':module'     => $module,
                ':controller' => $controller,
                ':action'     => $action,
                ':suffix'     => $viewRenderer->getViewSuffix(),
            )
        );

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

        // TODO add paths to helpers and filters

        $result = $this->view->render($viewScriptPath);

        // restore original script paths
        $this->view->setScriptPath(null);
        foreach ($origPaths as $path) {
            $this->view->addScriptPath($path);
        }

        return $result;
    } // }}}
}
