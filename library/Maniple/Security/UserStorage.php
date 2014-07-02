<?php

/**
 * UserStorage is a container for currently authenticated user.
 *
 * @uses Zend_Auth_Storage
 * @version 2014-05-27
 */
class Maniple_Security_UserStorage implements Maniple_Security_UserStorageInterface
{
    const SESSION_KEY = '__security';

    /**
     * @var Zend_Auth_Storage_Interface
     */
    protected $_storage;

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
     * Retrieve currently authenticated user.
     *
     * @return Maniple_Security_UserInterface|null
     * @throws Maniple_Security_Exception_InvalidStateException
     */
    public function getUser() // {{{
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $identity = $this->getStorage()->read();

        if (!$identity instanceof Maniple_Security_UserInterface) {
            $this->getStorage()->clear();
            throw new Maniple_Security_Exception_InvalidStateException(
                'Invalid session state'
            );
        }

        return $identity;
    } // }}}

    /**
     * @return mixed
     * @throws Maniple_Security_Exception_AuthenticationException
     * @throws Maniple_Security_Exception_InvalidStateException
     */
    public function getUserId() // {{{
    {
        $user = $this->getUser();

        if (empty($user)) {
            throw new Maniple_Security_Exception_AuthenticationException(
                'User is not authenticated'
            );
        }

        return $user->getId();
    } // }}}

    /**
     * @param Maniple_Security_UserInterface $user
     * @param array $state OPTIONAL
     * @return bool
     */
    public function setUser(Maniple_Security_UserInterface $user, array $state = null) // {{{
    {
        $_SESSION[self::SESSION_KEY] = array(
            'state' => $state,
            'token' => $this->_createToken(),
        );

        $this->getStorage()->write($user);
    } // }}}

    /**
     * @return mixed state attached to user upon authentication
     * @throws Maniple_Security_Exception_AuthenticationException
     */
    public function clearUser() // {{{
    {
        if ($this->isAuthenticated()) {
            if (isset($_SESSION[self::SESSION_KEY]['state'])) {
                $state = $_SESSION[self::SESSION_KEY]['state'];
            } else {
                $state = null;
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

            return $state;
        }

        throw new Maniple_Security_Exception_AuthenticationException(
            'User is not authenticated'
        );
    } // }}}

    /**
     * Impersonate as another user.
     *
     * @param  Maniple_Security_UserInterface $user
     * @param  array $state OPTIONAL
     */
    public function impersonate(Maniple_Security_UserInterface $user, array $state = null) // {{{
    {
        $_SESSION[self::SESSION_KEY] = array(
            'impersonation' => array(
                'security' => $_SESSION[self::SESSION_KEY],
                'identity' => $this->getStorage()->read(),
            ),
            'state' => $state,
            'token' => $this->_createToken(),
        );

        $this->getStorage()->write($user);
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
