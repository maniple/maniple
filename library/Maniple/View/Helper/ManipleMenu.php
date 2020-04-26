<?php

/**
 * @property Zefram_View_Abstract $view
 */
class Maniple_View_Helper_ManipleMenu extends Maniple_View_Helper_Abstract
    implements Zefram_Twig_SafeInterface
{
    /**
     * @Inject
     * @var Maniple_Menu_MenuManager
     */
    protected $_menuManager;

    public function manipleMenu()
    {
        return $this;
    }

    public function render(array $options = array())
    {
        return $this->renderMenu('maniple.primary') . $this->renderMenu('maniple.secondary');
    }

    /**
     * @param string $name
     * @return string
     */
    public function renderMenu($name)
    {
        $menu = $this->_menuManager->getMenu($name);

        $separators = $menu->findAllInstancesOf(Maniple_Menu_Page_Separator::className);
        foreach ($separators as $separator) {
            $separator->setClass(trim($separator->getClass() . ' dropdown-divider divider'));
        }

        return $this->view->navigation()->menu()->addPageClassToLi(false)->render($menu);
    }

    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return (string) $e;
        }
    }

    public function getSafe()
    {
        return array('html');
    }
}
