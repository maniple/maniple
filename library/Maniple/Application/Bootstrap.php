<?php

/**
 * @version 2014-05-19
 * @author xemlock
 */
class Maniple_Application_Bootstrap
    extends Maniple_Application_Bootstrap_Bootstrap
    implements ArrayAccess
{
    protected $_containerClass = 'Maniple_Application_ResourceContainer';

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
     * Proxy to {@see getResource()}.
     *
     * @param  string $offset
     * @return mixed
     * @deprecated
     */
    public function offsetGet($offset) // {{{
    {
        trigger_error(__METHOD__ . ' is deprecated');
        return $this->getResource($offset);
    } // }}}

    /**
     * Proxy to {@see setResource()}.
     *
     * @param  string $offset
     * @param  mixed $value
     * @return void
     * @deprecated
     */
    public function offsetSet($offset, $value) // {{{
    {
        trigger_error(__METHOD__ . ' is deprecated');
        $this->setResource($offset, $value);
    } // }}}

    /**
     * Does resource of given name exist.
     *
     * @param  string $offset
     * @return boolean
     * @deprecated
     */
    public function offsetExists($offset) // {{{
    {
        trigger_error(__METHOD__ . ' is deprecated');
        return isset($this->getContainer()->{$offset});
    } // }}}

    /**
     * Removes resource from container.
     *
     * @param  string $offset
     * @return void
     * @deprecated
     */
    public function offsetUnset($offset) // {{{
    {
        trigger_error(__METHOD__ . ' is deprecated');
        // no, this does not initialize resource before removing it
        unset($this->getContainer()->{$offset});
    } // }}}
}
