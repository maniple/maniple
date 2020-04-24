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
        if (!$container instanceof Maniple_Di_Container) {
            throw new Exception('Container must be an instance of Maniple_Di_Container');
        }

        $container->addResources(array(
            'Maniple.Injector' => $container->getInjector(),

            'Maniple.SharedEventManager' => $this->_initSharedEventManager(),
            'SharedEventManager' => 'resource:Maniple.SharedEventManager',
            'Zend_EventManager_SharedEventManager' => 'resource:Maniple.SharedEventManager',

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

            'Maniple_Menu_MenuManager' => array(
                'callback' => 'Maniple_Menu_MenuManagerFactory::factory',
            ),
            'maniple.menuManager' => 'resource:Maniple_Menu_MenuManager',
        ));

        return $this;
    }

    /**
     * @return Zend_EventManager_SharedEventManager
     */
    protected function _initSharedEventManager()
    {
        // There is a cyclic requires when autoloading Zend_EventManager_SharedEventManager,
        // resulting in Fatal error: Class 'Zend_EventManager_SharedEventManager' not found
        // in Zend/EventManager/StaticEventManager.php on line 33
        //
        // SharedEventManager.php:
        // - EventManager.php
        // - SharedEventCollection.php
        //
        // EventManager.php:
        // - StaticEventManager.php
        //
        // StaticEventManager.php:
        // - EventManager.php
        // - SharedEventManager.php
        //
        // To break circular dependency we need to autoload the StaticEventManager first
        class_exists('Zend_EventManager_StaticEventManager');

        return new Zend_EventManager_SharedEventManager();
    }
}
