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
     * @var Maniple_Security_AccessControl_Manager
     */
    protected $_accessControlManager;

    /**
     * @return Maniple_Security_AccessControl_Manager
     */
    public function getAccessControlManager()
    {
        if (null === $this->_accessControlManager) {
            $this->_accessControlManager = new Maniple_Security_AccessControl_Manager();
        }
        return $this->_accessControlManager;
    }

    /**
     * Add superuser ID.
     *
     * @param mixed $superUserId
     * @return Maniple_Security_ContextAbstract
     * @throws Maniple_Security_Exception_InvalidArgumentException
     */
    public function addSuperUserId($superUserId) // {{{
    {
        if (empty($superUserId)) {
            throw new Maniple_Security_Exception_InvalidArgumentException(
                'Superuser ID must not be empty'
            );
        }
        if (is_array($superUserId) || $superUserId instanceof Traversable) {
            foreach ($superUserId as $userId) {
                $this->addSuperUserId($userId);
            }
        } else {
            $this->_superUserIds[] = $this->_transformId($superUserId);
        }
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

    public function isImpersonated()
    {
        return $this->getUserStorage()->isImpersonated();
    }

    /**
     * @return Maniple_Security_UserInterface
     */
    public function getUser() // {{{
    {
        return $this->getUserStorage()->getUser();
    } // }}}

    /**
     * Proxy to {@link getIdentity()}.
     *
     * @return mixed
     * @deprecated
     */
    public function getUserId()
    {
        return $this->getIdentity();
    }

    /**
     * @return mixed|null
     */
    public function getIdentity()
    {
        $user = $this->getUserStorage()->getUser();
        return $user ? $user->getId() : null;
    }

    /**
     * Create representation of given ID suitable for storing and checking if
     * it belongs to superusers.
     *
     * @param  mixed $id
     * @return string|array
     */
    protected function _transformId($id) // {{{
    {
        if (is_float($id)) {
            $id = sprintf('%F', $id);
        }
        return (string) $id;
    } // }}}

    /**
     * @param mixed $permission
     * @param mixed $resource
     * @return bool
     */
    public function isAllowed($resource, $permission = null)
    {
        if ($permission === null) {
            $permission = $resource;
            $resource = null;
        }
        return $this->getAccessControlManager()->isAllowed($this, $resource, $permission);
    }
}
