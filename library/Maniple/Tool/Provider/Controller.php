<?php

class Maniple_Tool_Provider_Controller extends Zend_Tool_Framework_Provider_Abstract
{
    const className = __CLASS__;

    /**
     * Create controller
     *
     * @param string $name   The name of the controller to be created
     * @param string $module The module in which the controller should be created
     * @throws Zend_Tool_Framework_Client_Exception
     */
    public function create($name, $module)
    {
        if (!preg_match('/^[A-Z][-A-Z0-9]*$/i', $name)) {
            throw new Exception("Invalid controller name: '$name'");
        }

        $moduleDir = 'application/modules/' . $module;
        if (!is_dir($moduleDir)) {
            throw new Exception("Module '$module' does not exist");
        }

        $modulePrefix = $this->_toCamelCase($module);

        $controllerName = $this->_toCamelCase($name) . 'Controller';
        $controllerClass = $modulePrefix . '_' . $controllerName;

        $controllerDir = $moduleDir . '/controllers';

        // create directory for standalone actions
        @mkdir($controllerDir . '/' . $controllerName, 0777, true);
        $controllerFile = $controllerDir . '/' . $controllerName . '.php';

        if (file_exists($controllerFile)) {
            throw new Exception("Controller file already exists: {$controllerFile}");
        }

        file_put_contents($controllerFile,
"<?php

/**
 * @property Zend_Controller_Request_Http \$_request
 */
class {$controllerClass} extends Maniple_Controller_Action
{
    const className = __CLASS__;
}
");
        $this->_registry->getResponse()->appendContent(
            sprintf('Created controller %s in %s', $controllerName, $controllerDir)
        );

        $indexActionFile = $controllerDir . '/' . $controllerName . '/IndexAction.php';

        if (!file_exists($indexActionFile)) {
            file_put_contents($indexActionFile,
"<?php

/**
 * @property Zend_Controller_Request_Http \$_request
 */
class {$controllerClass}_IndexAction extends Maniple_Controller_Action_Standalone
{
    const className = __CLASS__;

    protected \$_actionControllerClass = {$controllerClass}::className;

    public function run(Zend_Controller_Request_Abstract \$request = null, Zend_Controller_Response_Abstract \$response = null)
    {
    }
}
");
            $this->_registry->getResponse()->appendContent(
                sprintf('Created index action for controller %s in %s', $controllerName, $indexActionFile)
            );
        }

        $indexActionViewDir = $moduleDir . '/views/scripts/' . $this->_toDashCase($module) . '/' . $this->_toDashCase($name);
        $indexActionViewFile = $indexActionViewDir . '/index.twig';

        @mkdir($indexActionViewDir, 0777, true);

        if (!file_exists($indexActionViewFile)) {
            file_put_contents($indexActionViewFile, "<h1>{$controllerName}::indexAction works!</h1>\n");
            $this->_registry->getResponse()->appendContent(
                sprintf('Created view script for controller\'s %s index action in %s', $controllerName, $indexActionFile)
            );
        }
    }

    /**
     * @param string $string
     * @return string
     */
    protected function _toCamelCase($string)
    {
        return str_replace(' ', '', ucfirst(ucwords(str_replace('-', ' ', $string))));
    }

    /**
     * @param string $string
     * @return string
     */
    protected function _toDashCase($string)
    {
        // filters from Zend_Controller_Action_Helper_ViewRenderer::getInflector()
        $filter = new Zend_Filter();
        $filter->addFilter(new Zend_Filter_Word_CamelCaseToDash());
        $filter->addFilter(new Zend_Filter_PregReplace('#[^a-z0-9' . preg_quote('/', '#') . ']+#i', '-'));
        $filter->addFilter(new Zend_Filter_StringToLower());

        return $filter->filter($string);
    }
}