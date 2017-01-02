<?php

class Maniple_Tool_Provider_Manifest extends Zend_Tool_Project_Provider_Manifest
{
    public function getProviders()
    {
        return array(
//                'init',
//                'vendorUpdate',
//                'moduleInstall',
//                'install',
//                'createModule',
//                'dbDump',
            'Maniple_Tool_Provider_Module',
        );
    }
}
