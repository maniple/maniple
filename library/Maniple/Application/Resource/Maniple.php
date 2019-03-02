<?php

/**
 * Registers core Maniple services in resource container.
 *
 * This should be added
 */
class Maniple_Application_Resource_Maniple extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * @return Zend_Config
     */
    public function init()
    {
        $bootstrap = $this->getBootstrap();
        if (!$bootstrap instanceof Zend_Application_Bootstrap_BootstrapAbstract) {
            throw new Exception('Bootstrap must be an instance of Zend_Application_Bootstrap_BootstrapAbstract');
        }

        $container = $bootstrap->getContainer();
        if (!$container instanceof Maniple_Application_ResourceContainer) {
            throw new Exception('Container must be an instance of Maniple_Application_ResourceContainer');
        }

        $container->addResources(array(
            'Maniple.Injector' => new Maniple_Injector($container),
            'Maniple.AssetRegistry' => array(
                'class' => 'Maniple_Assets_AssetRegistry',
            ),
            'Maniple.AssetManager' => array(
                'class' => 'Maniple_Assets_AssetManager',
                'args'  => array(
                    'resource:FrontController',
                    'resource:View',
                    'resource:Modules',
                    'resource:Maniple.AssetRegistry',
                ),
            ),
        ));

        return $this;
    }
}
