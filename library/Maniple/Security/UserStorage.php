<?php

/**
 * UserStorage is a session-based container for currently authenticated user.
 *
 * @version 2015-03-09 / 2014-05-27
 *
 * @TODO Base everything on Zend_Session_Namespace, no need to directly access $_SESSION
 */
class Maniple_Security_UserStorage implements Maniple_Security_UserStorageInterface
{
    const SESSION_KEY = '__security';

    public function __construct()
    {
        Zend_Session::start();
    }

    /**
     * @return bool
     */
    public function isAuthenticated() // {{{
    {
        return !empty($_SESSION[self::SESSION_KEY]['user']);
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

        $identity = $_SESSION[self::SESSION_KEY]['user'];

        if (!$identity instanceof Maniple_Security_UserInterface) {
            unset($_SESSION[self::SESSION_KEY]['user']);
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
     * @return bool
     */
    public function setUser(Maniple_Security_UserInterface $user) // {{{
    {
        $_SESSION[self::SESSION_KEY] = array(
            'user'  => $user,
            'token' => $this->_createToken(),
        );
    } // }}}

    /**
     * @return Maniple_Security_UserStorage
     * @throws Maniple_Security_Exception_AuthenticationException
     */
    public function clearUser() // {{{
    {
        if ($this->isAuthenticated()) {
            if (isset($_SESSION[self::SESSION_KEY]['impersonation'])) {
                // impersontaion frame detected, restore previous security data
                // and identity
                $impersonation = $_SESSION[self::SESSION_KEY]['impersonation'];

                if (isset($impersonation['security'])) {
                    $security = $impersonation['security'];
                } else {
                    $security = null;
                }

                $_SESSION[self::SESSION_KEY] = $security;

            } else {
                if (isset($_SESSION[self::SESSION_KEY])) {
                    unset($_SESSION[self::SESSION_KEY]);
                }
            }

            return $this;
        }

        throw new Maniple_Security_Exception_AuthenticationException(
            'User is not authenticated'
        );
    } // }}}

    /**
     * Impersonate as another user.
     *
     * @param  Maniple_Security_UserInterface $user
     */
    public function impersonate(Maniple_Security_UserInterface $user) // {{{
    {
        $_SESSION[self::SESSION_KEY] = array(
            'impersonation' => array(
                'security' => $_SESSION[self::SESSION_KEY],
            ),
            'user'  => $user,
            'token' => $this->_createToken(),
        );
    } // }}}

    /**
     * Get custom data associated with this storage.
     *
     * @param  string $name
     * @return mixed
     */
    public function get($name)
    {
        if (isset($_SESSION[self::SESSION_KEY]['data'][$name])) {
            return $_SESSION[self::SESSION_KEY]['data'][$name];
        }
    }

    /**
     * Set custom data associated with this storage.
     *
     * @param  string $name
     * @param  mixed $value
     * @return Maniple_Security_UserStorage
     */
    public function set($name, $value)
    {
        $_SESSION[self::SESSION_KEY]['data'][$name] = $value;
        return $this;
    }

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
