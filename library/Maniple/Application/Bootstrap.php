<?php

/**
 * @version 2014-05-19
 * @author xemlock
 */
class Maniple_Application_Bootstrap
    extends Maniple_Application_Bootstrap_Bootstrap
    implements ArrayAccess
{
    public function __construct($application)
    {
        self::setupEnvironment();
        parent::__construct($application);
    }

    public static function setupEnvironment()
    {
        if (!extension_loaded('json')) {
            throw new Exception('<a href="http://www.php.net/manual/en/book.json.php">JSON</a> extension required');
        }

        if (!extension_loaded('mbstring')) {
            throw new Exception('<a href="http://www.php.net/manual/en/book.mbstring.php">Multibyte String</a> extension required');
        }

        if (!extension_loaded('fileinfo')) {
            throw new Exception('<a href="http://www.php.net/manual/en/book.fileinfo.php">Fileinfo</a> extension required');
        }

        mb_internal_encoding('utf-8');
        ini_set('iconv.internal_encoding', 'utf-8');

        $temp_dir = realpath(APPLICATION_PATH . '/../variable/temp');

        // determination of a temporary directory in ZF is inconsistent,
        // temporary directory must be set via these Env variables
        foreach (array('TMPDIR', 'TEMP', 'TMP') as $key) {
            Zefram_Os::setEnv($key, $temp_dir);
	}

        Zend_Loader_PluginLoader::setIncludeFileCache(APPLICATION_PATH . '/../variable/cache/PluginLoader');
    }

    /**
     * Initialize resource of a given name, if it's not already initialized
     * and return the result.
     *
     * @param  null|string|array $resource OPTIONAL
     * @return mixed
     */
    protected function _bootstrap($resource = null) // {{{
    {
        parent::_bootstrap($resource);

        if (null !== $resource && $this->hasResource($resource)) {
            return $this->getResource($resource);
        }
    } // }}}

    /**
     * @deprecated
     */
    protected function _setResource($name, $value)
    {
        return $this->setResource($name, $value);
    }

    /**
     * Proxy to {@see getResource()}.
     *
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset) // {{{
    {
        return $this->getResource($offset);
    } // }}}

    /**
     * Proxy to {@see setResource()}.
     *
     * @param  string $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) // {{{
    {
        $this->setResource($offset, $value);
    } // }}}

    /**
     * Does resource of given name exist.
     *
     * @param  string $offset
     * @return boolean
     */
    public function offsetExists($offset) // {{{
    {
        return isset($this->getContainer()->{$offset});
    } // }}}

    /**
     * Removes resource from container.
     *
     * @param  string $offset
     * @return void
     */
    public function offsetUnset($offset) // {{{
    {
        // TODO does it initialize resource before removing it? Check!
        unset($this->getContainer()->{$offset});
    } // }}}
}
