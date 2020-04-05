<?php

/**
 * @version 2014-05-19
 * @author xemlock
 * @deprecated
 */
class Maniple_Application_Bootstrap
    extends Maniple_Application_Bootstrap_Bootstrap
{
    protected $_containerClass = Maniple_Di_Container::className;

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

        mb_internal_encoding('UTF-8');

        if (PHP_VERSION_ID < 50600) {
            iconv_set_encoding('input_encoding', 'UTF-8');
            iconv_set_encoding('output_encoding', 'UTF-8');
            iconv_set_encoding('internal_encoding', 'UTF-8');
        } else {
            ini_set('default_charset', 'UTF-8');
        }

        $classFileIncCache = APPLICATION_PATH . '/../data/cache/PluginLoader.php';
        if (file_exists($classFileIncCache)) {
            include_once $classFileIncCache;
        }
        Zend_Loader_PluginLoader::setIncludeFileCache($classFileIncCache);
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
}
