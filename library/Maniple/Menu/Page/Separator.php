<?php

class Maniple_Menu_Page_Separator extends Zend_Navigation_Page
{
    const className = __CLASS__;

    /**
     * @return array
     */
    public function getLiHtmlAttribs()
    {
        return array(
            'role' => 'separator',
        );
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return null;
    }

    /**
     * @param Zend_View_Abstract $view
     * @return string
     */
    public function render(Zend_View_Abstract $view)
    {
        return '';
    }
}
