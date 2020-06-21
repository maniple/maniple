<?php

class Maniple_Menu_MenuManager
{
    const className = __CLASS__;

    /**
     * @var Maniple_Menu_MenuBuilderInterface[]
     */
    protected $_builders;

    /**
     * @var ManipleCore_Menu_Menu[]
     */
    protected $_menuRegistry;

    /**
     * @param Maniple_Menu_MenuBuilderInterface[] $builders
     * @return $this
     */
    public function addBuilders(array $builders)
    {
        foreach ($builders as $builder) {
            $this->addBuilder($builder);
        }
        return $this;
    }

    /**
     * @param Maniple_Menu_MenuBuilderInterface $builder
     * @return $this
     */
    public function addBuilder(Maniple_Menu_MenuBuilderInterface $builder)
    {
        $this->_builders[] = $builder;
        return $this;
    }

    /**
     * @param string $menuName
     * @return Maniple_Menu_Menu
     */
    public function getMenu($menuName)
    {
        if (empty($this->_menuRegistry[$menuName])) {
            $this->_menuRegistry[$menuName] = $this->_buildMenu($menuName);
        }
        return $this->_menuRegistry[$menuName];
    }

    /**
     * @param string $menuName
     * @return Maniple_Menu_Menu
     */
    protected function _buildMenu($menuName)
    {
        $menu = new Maniple_Menu_Menu($menuName);

        foreach ($this->_builders as $builder) {
            $builder->buildMenu($menu);
        }

        foreach ($this->_builders as $builder) {
            if (method_exists($builder, 'postBuildMenu')) {
                $builder->postBuildMenu($menu);
            }
        }

        return $menu;
    }
}
