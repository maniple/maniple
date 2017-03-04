<?php

/**
 * Standalone action to handle form related logic.
 * This class provides encapsulation of form-related logic as well as allows
 * avoiding repetitively writing form handling skeleton code.
 *
 * @version    2014-02-25 / 2013-12-13 / 2013-09-12
 */
abstract class Maniple_Controller_Action_StandaloneForm extends Maniple_Controller_Action_Standalone
{
    const VALIDATION_FAILED = 'validationFailed';

    /**
     * Messages used in AJAX responses.
     * @var string[]
     */
    protected $_ajaxMessages = array(
        self::VALIDATION_FAILED => 'Form validation failed',
    );

    /**
     * Return form markup rather than form errors map on failed validation.
     * @var bool
     */
    protected $_ajaxFormHtml = false;

    /**
     * Treat every request as AJAX.
     * @var bool
     */
    protected $_ajaxOnly = false;

    /**
     * View script to be used for rendering form when page is accessed via
     * XmlHttpRequest.
     * @var string
     */
    protected $_ajaxViewScript;

    /**
     * Allow processing of partially valid form?
     * @var bool
     */
    protected $_processPartialForm = false;

    /**
     * Form to process, must be initialized either in {@see _init()}
     * or {@see _prepare()}.
     *
     * @var Zend_Form
     */
    protected $_form;

    /**
     * Name of the view variable the form will be stored in.
     * @var string
     */
    protected $_formKey = 'form';

    /**
     * Method executed before form validation. This is where the form
     * should be instantiated.
     *
     * @return Zend_Form
     */
    protected function _prepare()
    {}

    /**
     * Retrieve default form values, called when no data is submitted.
     *
     * @return array
     */
    protected function _defaults()
    {
        return array();
    }

    /**
     * Method called to populate existing form with default values,
     * when no data is submitted.
     *
     * @return void
     */
    protected function _populate()
    {
        $this->_form->setDefaults($this->_defaults());
    }

    /**
     * Validate form against given data.
     *
     * @param  array $data
     * @return bool
     */
    protected function _validate(array $data)
    {
        $form = $this->getForm();

        if ($this->_processPartialForm) {
            return $form->isValidPartial($data);
        }

        return $form->isValid($data);
    }

    /**
     * Validated form processing routine.
     *
     * This method is marked as protected to disallow direct calls
     * when the form is invalid.
     *
     * @return bool|string
     */
    abstract protected function _process();

    /**
     * Retrieve form instance.
     *
     * @return Zend_Form
     * @throws Maniple_Controller_Action_Exception_InvalidState
     */
    public function getForm()
    {
        if (!$this->_form instanceof Zend_Form) {
            throw new Maniple_Controller_Action_Exception_InvalidState(
                '_form property was not properly initialized.'
            );
        }
        return $this->_form;
    }

    /**
     * Retrieve all form element values.
     *
     * @param  bool $suppressArrayNotation
     * @return array
     * @deprecated
     */
    public function getFormValues($suppressArrayNotation = false)
    {
        return $this->getForm()->getValues($suppressArrayNotation);
    }

    /**
     * Retrieve value for a single form element.
     *
     * @param  string $name
     * @return mixed
     * @deprecated
     */
    public function getFormValue($name)
    {
        return $this->getForm()->getValue($name);
    }

    /**
     * @return bool
     */
    public function isAjax()
    {
        return $this->_ajaxOnly || $this->_request->isXmlHttpRequest();
    }

    /**
     * Retrieves AJAX response.
     *
     * @return Zefram_Controller_Action_AjaxResponse_Abstract
     * @throws Maniple_Controller_Action_Exception_InvalidArgument
     */
    public function getAjaxResponse()
    {
        $ajaxResponse = $this->_helper->ajaxResponse();
        if (!$ajaxResponse instanceof Zefram_Controller_Action_AjaxResponse_Abstract) {
            throw new Maniple_Controller_Action_Exception_InvalidArgument(
                'AjaxResponse must be an instance of Zefram_Controller_Action_AjaxResponse_Abstract'
            );
        }
        return $ajaxResponse;
    }

    /**
     * Renders form.
     *
     * @return string
     */
    public function renderForm()
    {
        $controller = $this->getActionController();
        $view = $controller->initView();

        $form = $this->getForm()->setView($view);

        if ($this->isAjax()) {
            $script = $this->_ajaxViewScript;
        }
        if (empty($script)) {
            $script = $controller->getViewScript();
        }

        if (!is_file($view->getScriptPath($script))) {
            // render form directly if view script does not exist
            $content = $form->render();
        } else {
            $view->assign($this->_formKey, $form);
            $content = $view->render($script);
        }

        return $content;
    }

    /**
     * Execute form handling logic
     *
     * @return void
     */
    public function run()
    {
        $this->_prepare();

        // check for redirect and forwards
        if ($this->getResponse()->isRedirect() || !$this->getRequest()->isDispatched()) {
            return;
        }

        $form = $this->getForm();
        $data = self::getSentData($form->getMethod(), $this->_request);

        if (false !== $data) {
            $isValid = $this->_validate($data);
            if ($isValid) {
                // any success response should be sent in _process() method
                // by calling ajaxResponse helper
                $result = $this->_process();

                // form was handled successfully, try to perform redirection,
                // or do nothing if redirect ion was explicitly cancelled by
                // FALSE value returned from _process()

                // Check if redirect or forward issued explicitly, if so, do
                // nothing as the rest will be performed by the dispatcher
                if ($this->getResponse()->isRedirect() || !$this->getRequest()->isDispatched()) {
                    return;

                } elseif (false !== $result) {
                    // if a string is returned from the _process() method,
                    // treat it as a redirection url, otherwise use current
                    // request uri
                    if (!is_string($result)) {
                        $result = $this->_request->getRequestUri();
                        $prependBase = false;
                    } else {
                        $baseUrl = $this->_request->getBaseUrl();
                        $prependBase = strncmp($result, $baseUrl, strlen($baseUrl));
                    }
                    $this->_helper->redirector->gotoUrl(
                        $result, array('prependBase' => $prependBase)
                    );
                    return;
                }
            }

            if ($this->isAjax()) {
                $ajaxResponse = $this->getAjaxResponse();
                if ($isValid) {
                    // form validated successfully, no redirection performed,
                    // no success response was sent in _process()
                    $ajaxResponse->setSuccess();

                } else {
                    // form contains invalid values, send response containing
                    // human-readable message and either full form markup or
                    // form errors map
                    $message = $this->_ajaxMessages[self::VALIDATION_FAILED];

                    // translate error message using form translator (if any)
                    $translator = $form->getTranslator();
                    if ($translator) {
                        $message = $translator->translate($message);
                    }

                    $ajaxResponse->setFail($message);
                    $ajaxResponse->setData($this->_ajaxFormHtml
                        ? $this->renderForm()
                        : self::getFormMessages($form)
                    );
                }
                $ajaxResponse->sendAndExit();
                return;
            }
        } else {
            $this->_populate();
        }

        if ($this->isAjax()) {
            // if form is accessed for the first time, i.e. was not submitted,
            // return its markup regardless of _ajaxFormHtml setting
            $ajaxResponse = $this->getAjaxResponse();
            $ajaxResponse->setSuccess();
            $ajaxResponse->setData($this->renderForm());
            $ajaxResponse->sendAndExit();

        } else {
            // mark page as already rendered, so that it isn't auto rendered
            // in viewRenderer::postDispatch(). Append form rendering to
            // response.
            $this->_helper->viewRenderer->setNoRender();
            $this->getResponse()->appendBody($this->renderForm());
        }
    }

    /**
     * HTTP request method aware retrieval of submitted form data.
     *
     * @param  string $method
     * @param  Zend_Controller_Request_Http $request
     * @return false|array
     */
    public static function getSentData($method, Zend_Controller_Request_Http $request)
    {
        switch (strtoupper($method)) {
            case 'POST':
                if ($request->isPost()) {
                    return $request->getPost();
                }
                break;

            case 'GET':
                // consider form as submitted using the GET method only if
                // the request's query part is not empty
                if ($request->isGet() && ($query = $request->getQuery())) {
                    return $query;
                }
                break;

            default:
                break;
        }

        return false;
    }

    /**
     * Retrieve error messages from elements failing validations organized
     * by elements' fully qualified names.
     *
     * @param  Zend_Form $form
     * @return array
     */
    public static function getFormMessages(Zend_Form $form)
    {
        $messages = array();
        $forms = array($form);

        while ($form = array_shift($forms)) {
            foreach ($form->getElements() as $element) {
                /** @var Zend_Form_Element $element */
                // Element error messages are grouped by fully qualified names
                // so that the corresponding DOM elements may be easily found.
                // Validation error codes are irrelevant for the client-side
                // and so they are, for easier handling by JavaScript, not
                // returned.
                $elementMessages = $element->getMessages();
                if (count($elementMessages)) {
                    $name = $element->getFullyQualifiedName();
                    $messages[$name] = array_values($elementMessages);
                }
            }

            foreach ($form->getSubForms() as $subform) {
                $forms[] = $subform;
            }
        }

        return $messages;
    }
}
