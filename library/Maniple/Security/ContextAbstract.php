<?php

/**
 * @version 2014-03-06
 */
abstract class Maniple_Security_ContextAbstract implements Maniple_Security_ContextInterface
{
    /**
     * @var Maniple_Security_UserStorageInterface
     */
    protected $_userStorage;

    /**
     * @var array
     */
    protected $_superUserIds = array();

    /**
     * Add superuser ID.
     *
     * @param mixed $superUserId
     * @return Maniple_Security_ContextAbstract
     */
    public function addSuperUserId($superUserId) // {{{
    {
        if (empty($superUserId)) {
            throw new Maniple_Security_Exception_InvalidArgumentException(
                'Superuser ID must not be empty'
            );
        }
        $this->_superUserIds[] = $this->_transformId($superUserId);
        return $this;
    } // }}}

    /**
     * Retrieve first superuser ID.
     *
     * @return mixed
     */
    public function getSuperUserId() // {{{
    {
        return reset($this->_superUserIds);
    } // }}}

    /**
     * Return all superuser IDs.
     *
     * @return array
     */
    public function getSuperUserIds() // {{{
    {
        return $this->_superUserIds;
    } // }}}

    /**
     * Remove all superuser IDs.
     *
     * @return Maniple_Security_ContextAbstract
     */
    public function clearSuperUserIds() // {{{
    {
        $this->_superUserIds = array();
        return $this;
    } // }}}

    /**
     * @param  Maniple_Security_UserStorageInterface $storage
     * @return Maniple_Security_ContextAbstract
     */
    public function setUserStorage(Maniple_Security_UserStorageInterface $storage = null) // {{{
    {
        $this->_userStorage = $storage;
        return $this;
    } // }}}

    /**
     * @return Maniple_Security_UserStorageInterface
     */
    public function getUserStorage() // {{{
    {
        if (empty($this->_userStorage)) {
            $this->_userStorage = new Maniple_Security_UserStorage();
        }
        return $this->_userStorage;
    } // }}}

    /**
     * Check whether the current user is authenticated.
     *
     * @return bool
     */
    public function isAuthenticated() // {{{
    {
        return (bool) $this->getUserStorage()->getUser();
    } // }}}

    /**
     * Is user with given ID or, if none given, currently authenticated user
     * a super-user.
     *
     * @param  mixed $userId OPTIONAL
     * @return bool
     * @throws Maniple_Security_Exception_InvalidStateException
     */
    public function isSuperUser($userId = null) // {{{
    {
        if (null === $userId) {
            if (!$this->isAuthenticated()) {
                return false;
            }
            $userId = $this->getUser()->getId();
        }

        // do not use strict type comparisons, so that arrays containing the
        // same key-value pairs can be matched regardless of theis ordering
        return in_array($this->_transformId($userId), $this->_superUserIds);
    } // }}}

    /**
     * @return Maniple_Security_UserInterface
     */
    public function getUser() // {{{
    {
        return $this->getUserStorage()->getUser();
    } // }}}

    /**
     * Create representation of given ID suitable for storing and checking if
     * it belongs to superusers.
     *
     * @param  mixed $id
     * @return string|array
     */
    protected function _transformId($id) // {{{
    {
        if (is_array($id)) {
            return array_map(array($this, __FUNCTION__), $id);
        }
        if (is_float($id)) {
            $id = sprintf('%F', $id);
        }
        return (string) $id;
    } // }}}
}
