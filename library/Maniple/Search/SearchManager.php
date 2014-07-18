<?php

class Maniple_Search_SearchManager
{
    /**
     * Registered index factories.
     * @var Maniple_Search_IndexFactoryInterface[]
     */
    protected $_indexFactories = array();

    /**
     * @param  Maniple_Search_IndexFactoryInterface[] $factories
     * @return Maniple_Search_SearchManager
     */
    public function addIndexFactories(array $factories) // {{{
    {
        foreach ($factories as $id => $factory) {
            $this->registerIndexFactory($id, $factory);
        }
        return $this;
    } // }}}

    /**
     * Register index factory at specified ID.
     *
     * @param  string $id
     * @param  Maniple_Search_IndexFactoryInterface $factory
     * @return Maniple_Search_SearchManager
     */
    public function registerIndexFactory($id, Maniple_Search_IndexFactoryInterface $factory) // {{{
    {
        $this->_indexFactories[(string) $id] = $factory;
        return $this;
    } // }}}

    /**
     * Retrieve index factory stored at specified ID.
     *
     * @param  string $id
     * @return Maniple_Search_IndexFactoryInterface
     * @throws DomainException
     */
    public function getIndexFactory($id) // {{{
    {
        $id = (string) $id;
        if (empty($this->_indexFactories[$id])) {
            throw new DomainException('Invalid index factory ID');
        }
        return $this->_indexFactories[$id];
    } // }}}
}
