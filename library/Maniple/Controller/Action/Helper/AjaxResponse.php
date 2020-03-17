<?php

/**
 * @version 2013-05-02
 */
class Maniple_Controller_Action_Helper_AjaxResponse extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var string
     */
    protected $_ajaxResponseClass = 'Maniple_Controller_Action_AjaxResponse';

    /**
     * @param string $ajaxResponseClass
     */
    public function setAjaxResponseClass($ajaxResponseClass)
    {
        $this->_ajaxResponseClass = (string) $ajaxResponseClass;
        return $this;
    }

    /**
     * @return Maniple_Controller_Action_AjaxResponse_Abstract
     */
    public function createAjaxResponse()
    {
        $ajaxResponseClass = $this->_ajaxResponseClass;
        $ajaxResponse = new $ajaxResponseClass;
        if (!$ajaxResponse instanceof Maniple_Controller_Action_AjaxResponse_Abstract) {
            throw new Maniple_Controller_Action_Exception_InvalidArgument(
                "AjaxResponse must be an instance of Maniple_Controller_Action_AjaxResponse_Abstract"
            );
        }
        return $ajaxResponse;
    }

    /**
     * @return Maniple_Controller_Action_AjaxResponse_Abstract
     */
    public function direct()
    {
        return $this->createAjaxResponse();
    }
}
