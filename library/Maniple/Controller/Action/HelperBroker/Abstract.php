<?php

/**
 * This interface exists only for PHPDoc-based autocompletion purposes.
 *
 * @property Zend_Controller_Action_Helper_ActionStack $actionStack
 * @property Zend_Controller_Action_Helper_AjaxContext $ajaxContext
 * @property Maniple_Controller_Action_Helper_AjaxResponse $ajaxResponse
 * @property Zend_Controller_Action_Helper_AutoCompleteDojo $autoCompleteDojo
 * @property Zend_Controller_Action_Helper_AutoCompleteScriptaculous $autoCompleteScriptaculous
 * @property Zend_Controller_Action_Helper_Cache $cache
 * @property Zend_Controller_Action_Helper_ContextSwitch $contextSwitch
 * @property Zefram_Controller_Action_Helper_FlashMessenger $flashMessenger
 * @property Zend_Controller_Action_Helper_Json $json
 * @property Zefram_Controller_Action_Helper_Redirector $redirector
 * @property Zend_Controller_Action_Helper_Url $url
 * @property Zend_Controller_Action_Helper_ViewRenderer $viewRenderer
 * @property Zend_Layout_Controller_Action_Helper_Layout $layout
 * @method string|void json(mixed $data, bool $sendNow = true, bool $keepLayouts = false, bool $encodeData = true)
 * @method Maniple_Controller_Action_AjaxResponse_Abstract ajaxResponse()
 *
 * @category   Maniple
 * @package    Maniple_Controller
 * @author     xemlock <xemlock@gmail.com>
 */
abstract class Maniple_Controller_Action_HelperBroker_Abstract extends Zend_Controller_Action_HelperBroker
{}
