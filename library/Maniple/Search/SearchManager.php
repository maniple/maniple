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
    public function setIndexFactories(array $factories) // {{{
    {
        foreach ($factories as $factoryId => $factory) {
            $this->registerIndexFactory($factoryId, $factory);
        }
        return $this;
    } // }}}

    /**
     * @return Maniple_Search_IndexFactoryInterface[]
     */
    public function getIndexFactories() // {{{
    {
        return $this->_indexFactories;
    } // }}}

    /**
     * Register index factory at specified ID.
     *
     * @param  string $factoryId
     * @param  Maniple_Search_IndexFactoryInterface $factory
     * @return Maniple_Search_SearchManager
     */
    public function registerIndexFactory($factoryId, Maniple_Search_IndexFactoryInterface $factory) // {{{
    {
        $this->_indexFactories[(string) $factoryId] = $factory;
        return $this;
    } // }}}

    /**
     * Retrieve index factory stored at specified ID.
     *
     * @param  string $factoryId
     * @return Maniple_Search_IndexFactoryInterface
     * @throws DomainException
     */
    public function getIndexFactory($factoryId) // {{{
    {
        $factoryId = (string) $factoryId;
        if (empty($this->_indexFactories[$factoryId])) {
            throw new DomainException(sprintf('Invalid index factory ID (%s)', $factoryId));
        }
        return $this->_indexFactories[$factoryId];
    } // }}}
}
