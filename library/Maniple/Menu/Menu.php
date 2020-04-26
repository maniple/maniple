<?php /** @noinspection PhpMissingParentConstructorInspection */

class Maniple_Menu_Menu extends Zend_Navigation
{
    /**
     * @var string
     */
    protected $_name;

    /**
     * @param string $name
     * @param array $pages
     */
    public function __construct($name, array $pages = null)
    {
        $this->_name = (string) $name;
        if ($pages) {
            $this->setPages($pages);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param array|Zend_Config|Zend_Navigation_Page $page
     * @return Maniple_Menu_Menu
     */
    public function addPage($page)
    {
        if ($page instanceof Zend_Config) {
            $page = $page->toArray();
        }
        $defaultPageType = Zend_Navigation_Page::getDefaultPageType();
        Zend_Navigation_Page::setDefaultPageType(Maniple_Menu_Page::className);

        try {
            $page = parent::addPage($page);
            Zend_Navigation_Page::setDefaultPageType($defaultPageType);
        } catch (Exception $e) {
            Zend_Navigation_Page::setDefaultPageType($defaultPageType);
            throw $e;
        }

        return $page;
    }

    /**
     * Returns a child page that is an instance of a given class.
     *
     * @param string $class
     * @return Zend_Navigation_Page|null
     */
    public function findInstanceOf($class)
    {
        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $page) {
            if ($page instanceof $class) {
                return $page;
            }
        }

        return null;
    }

    /**
     * Returns all child pages that are instances of a given class.
     *
     * @param string $class
     * @return Zend_Navigation_Page[]
     */
    public function findAllInstancesOf($class)
    {
        $found = array();
        $iterator = new RecursiveIteratorIterator($this, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $page) {
            if ($page instanceof $class) {
                $found[] = $page;
            }
        }

        return $found;
    }
}
