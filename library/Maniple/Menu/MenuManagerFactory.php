<?php

class Maniple_Menu_MenuManagerFactory
{
    /**
     * @param Maniple_Di_Container $container
     * @return Maniple_Menu_MenuManager
     */
    public static function factory(Maniple_Di_Container $container)
    {
        $menuManager = new Maniple_Menu_MenuManager();

        // Bootstraps are expected to be in bootstrapping order
        $modules = $container->getResource('Modules');
        foreach ($modules as $moduleBootstrap) {
            if (!$moduleBootstrap instanceof Maniple_Menu_MenuManagerProviderInterface
                && !method_exists($moduleBootstrap, 'getMenuManagerConfig')
            ) {
                continue;
            }
            $menuManagerConfig = $moduleBootstrap->getMenuManagerConfig();
            if (isset($menuManagerConfig['builders'])) {
                foreach ($menuManagerConfig['builders'] as $builder) {
                    if (is_string($builder)) {
                        $builder = $container->hasResource($builder)
                            ? $container->getResource($builder)
                            : $container->getInjector()->newInstance($builder);
                    }
                    $menuManager->addBuilder($builder);
                }
            }
        }

        return $menuManager;
    }
}
