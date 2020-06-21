<?php

/**
 * @property Zefram_View_Abstract $view
 */
class Maniple_View_Helper_Abstract extends Zend_View_Helper_Abstract
{
    public function __construct()
    {
        /** @var Maniple_Di_Injector $injector */
        $injector = Zend_Controller_Front::getInstance()->getParam('bootstrap')->getResource('Maniple.Injector');
        $injector->inject($this);
    }
}
