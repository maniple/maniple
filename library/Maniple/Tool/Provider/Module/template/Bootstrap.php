<?php

class __modulePrefix_Bootstrap extends Maniple_Application_Module_Bootstrap
{
    public function getModuleDependencies()
    {
        return array();
    }

    public function getResourcesConfig()
    {
        return require __DIR__ . '/configs/resources.config.php';
    }

    public function getRoutesConfig()
    {
        return require __DIR__ . '/configs/routes.config.php';
    }

    public function getTranslationsConfig()
    {
        return array(
            'scan'    => Zend_Translate::LOCALE_DIRECTORY,
            'content' => __DIR__ . '/languages',
        );
    }

    public function getViewConfig()
    {
        return array(
            'scriptPaths' => __DIR__ . '/views/scripts',
            'helperPaths' => array(
                '%modulePrefix%_View_Helper_' => __DIR__ . '/library/%modulePrefix%/View/Helper/',
            ),
        );
    }

    /**
     * Register autoloader paths
     */
    protected function _initAutoloader()
    {
        Zend_Loader_AutoloaderFactory::factory(array(
            'Zend_Loader_StandardAutoloader' => array(
                'prefixes' => array(
                    '%modulePrefix%_' => __DIR__ . '/library/%modulePrefix%/',
                ),
            ),
        ));
    }

    /**
     * Setup view path spec
     */
    protected function _initViewRenderer()
    {
        /** @var Zefram_Controller_Action_Helper_ViewRenderer $viewRenderer */
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setViewScriptPathSpec(':module/:controller/:action.:suffix', '%moduleName%');
        $viewRenderer->setViewSuffix('twig', '%moduleName%');
    }
}
