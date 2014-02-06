<?php

/**
 * @uses Zend_Auth_Storage
 * @version 2014-02-06
 */
class Maniple_Security_SecurityManager
{
    const SESSION_KEY = '__security';

    /**
     * @var Zend_Auth_Storage_Interface
     */
    protected $_storage;

    /**
     * @var array
     */
    protected $_superUserIds = array();

    /**
     * Add superuser ID.
     *
     * @param mixed $superUserId
     * @return Maniple_Security_SecurityManager
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
     * @return Maniple_Security_SecurityManager
     */
    public function clearSuperUserIds() // {{{
    {
        $this->_superUserIds = array();
        return $this;
    } // }}}

    /**
     * Set authentication storage.
     *
     * @param Zend_Auth_Storage_Interface $storage
     */
    public function setStorage(Zend_Auth_Storage_Interface $storage) // {{{
    {
        $this->_storage = $storage;
        return $this;
    } // }}}

    /**
     * Retrieve authentication storage.
     *
     * @return Zend_Auth_Storage_Interface
     */
    public function getStorage() // {{{
    {
        if (empty($this->_storage)) {
            $this->setStorage(new Zend_Auth_Storage_Session());
        }
        return $this->_storage;
    } // }}}

    /**
     * @return bool
     */
    public function isAuthenticated() // {{{
    {
        return !$this->getStorage()->isEmpty();
    } // }}}

    /**
     * @return bool
     */
    public function isImpersonated() // {{{
    {
        return $this->isAuthenticated()
            && isset($_SESSION[self::SESSION_KEY]['impersonation']);
    } // }}}

    /**
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
     * Retrieve currently authenticated user.
     *
     * @return Maniple_Security_User
     * @throws Maniple_Security_Exception_InvalidStateException
     */
    public function getUser() // {{{
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $identity = $this->getStorage()->read();

        if (!$identity instanceof Maniple_Security_User) {
            $this->getStorage()->clear();
            throw new Maniple_Security_Exception_InvalidStateException(
                'Invalid session state'
            );
        }

        return $identity;
    } // }}}

    /**
     * @param Zend_Auth_Adapter_DbTable $adapter
     * @return bool
     */
    public function setUser(Maniple_Security_User $user, array $context = null) // {{{
    {
        $_SESSION[self::SESSION_KEY] = array(
            'context' => $context,
            'token' => $this->_createToken(),
        );

        $this->getStorage()->write($user);
    } // }}}

    /**
     * @return mixed context attached to user upon authentication
     * @throws Maniple_Security_Exception_AuthenticationException
     */
    public function clearUser() // {{{
    {
        if ($this->isAuthenticated()) {
            if (isset($_SESSION[self::SESSION_KEY]['context'])) {
                $context = $_SESSION[self::SESSION_KEY]['context'];
            } else {
                $context = null;
            }

            if (isset($_SESSION[self::SESSION_KEY]['impersonation'])) {
                // impersontaion frame detected, restore previous security data
                // and identity
                $impersonation = $_SESSION[self::SESSION_KEY]['impersonation'];

                if (isset($impersonation['identity'])) {
                    $identity = $impersonation['identity'];
                } else {
                    $identity = null;
                }

                if (isset($impersonation['security'])) {
                    $security = $impersonation['security'];
                } else {
                    $security = null;
                }

                $this->getStorage()->write($identity);
                $_SESSION[self::SESSION_KEY] = $security;

            } else {
                $this->getStorage()->clear();

                if (isset($_SESSION[self::SESSION_KEY])) {
                    unset($_SESSION[self::SESSION_KEY]);
                }
            }

            return $context;
        }

        throw new Maniple_Security_Exception_AuthenticationException(
            'User is not authenticated'
        );
    } // }}}

    /**
     * Impersonate as another user.
     *
     * @param  Maniple_Security_User $user
     * @param  array $context
     * @throws Maniple_Security_Exception_NotAllowedException
     */
    public function impersonate(Maniple_Security_User $user, array $context = null) // {{{
    {
        if (!$this->isSuperUser()) {
            throw new Maniple_Security_Exception_NotAllowedException(
                'You must be Superuser to impersonate'
            );
        }

        $_SESSION[self::SESSION_KEY] = array(
            'impersonation' => array(
                'security' => $_SESSION[self::SESSION_KEY],
                'identity' => $this->getStorage()->read(),
            ),
            'context' => $context,
            'token' => $this->_createToken(),
        );

        $this->getStorage()->write($user);
    } // }}}

    /**
     * Does the authenticated user have access to given resource.
     * Superusers are automatically allowed.
     *
     * @param  Zend_Acl $acl
     * @param  string|Zend_Acl_Resource_Interface $resource
     * @param  string $privilege
     * @return bool
     */
    public function isAllowed(Zend_Acl $acl, $resource = null, $privilege = null) // {{{
    {
        if ($this->isSuperUser()) {
            return true;
        }

        if (null !== ($user = $this->getUser())) {
            foreach ((array) $user->getRoles() as $role) {
                try {
                    if ($acl->isAllowed($role, $resource, $privilege)) {
                        return true;
                    }
                } catch (Zend_Acl_Exception $e) {
                    // role or resource not found
                }
            }
        }

        return false;
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

    /**
     * Generate anti-CSRF token to be stored in session.
     *
     * @return string
     */
    protected function _createToken() // {{{
    {
        return Zend_Crypt::hash('sha256', microtime() . mt_rand());
    } // }}}
}
