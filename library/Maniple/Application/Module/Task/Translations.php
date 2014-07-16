<?php

class Maniple_Application_Module_Task_Translations
    implements Maniple_Application_Module_Task_TaskInterface
{
    /**
     * Adds module's translations directory to 'translate' resource.
     *
     * @return void
     */
    public function run(Maniple_Application_Module_Bootstrap $bootstrap) // {{{
    {
        $parentBootstrap = $bootstrap->getParentBootstrap();
        if (empty($parentBootstrap)) {
            $parentBootstrap = $bootstrap;
        }

        $translate = $parentBootstrap->bootstrap('translate')->getResource('translate');
        $locale = $translate->getLocale();

        $translationsDir = $bootstrap->getPath('languages/' . $locale);

        if (is_dir($translationsDir)) {
            $translate->addTranslation(array(
                'content' => $translationsDir,
                'locale'  => $locale,
            ));
        }
    } // }}}
}
