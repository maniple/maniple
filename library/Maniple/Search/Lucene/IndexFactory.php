<?php

class Maniple_Search_Lucene_IndexFactory
    implements Maniple_Search_IndexFactoryInterface
{
    /**
     * @var string
     */
    protected $_storageDir;

    /**
     * @var Zend_Cache_Core
     */
    protected $_cache;

    /**
     * @param  string $storageDir
     * @return ManipleCore_Search_IndexFactory
     * @throws InvalidArgumentException
     */
    public function setStorageDir($storageDir) // {{{
    {
        if (!is_dir($storageDir) || !is_readable($storageDir) || !is_writable($storageDir)) {
            throw new InvalidArgumentException(sprintf('Invalid storage directory (%s)', $storageDir));
        }
        $this->_storageDir = realpath($storageDir);
        return $this;
    } // }}}

    /**
     * @return string|null
     */
    public function getStorageDir() // {{{
    {
        return $this->_storageDir;
    } // }}}

    /**
     * @param  Zend_Cache_Core $cache
     * @return ManipleCore_Search_IndexFactory
     */
    public function setCache(Zend_Cache_Core $cache) // {{{
    {
        $this->_cache = $cache;
        return $this;
    } // }}}

    /**
     * @return Zend_Cache_Core|null
     */
    public function getCache() // {{{
    {
        return $this->_cache;
    } // }}}

    /**
     * @return string
     * @throws Exception
     */
    protected function _getIndexDir($name) // {{{
    {
        $storageDir = $this->getStorageDir();
        if (empty($storageDir)) {
            throw new Exception('Storage directory is not initialized');
        }
        return $storageDir . '/' . trim($name, '/');
    } // }}}

    /**
     * @param  string $name
     * @return ManipleCore_Search_IndexInterface
     */
    public function getIndex($name) // {{{
    {
        $lucene = Zend_Search_Lucene::open($this->_getIndexDir($name));
        $index = new ManipleCore_Search_Lucene_Index($lucene);
        $cache = $this->getCache();
        if ($cache) {
            $index->setCache($cache);
        }
        return $index;
    } // }}}

    /**
     * @param  string $name
     * @return ManipleCore_Search_IndexInterface
     */
    public function createIndex($name) // {{{
    {
        $lucene = Zend_Search_Lucene::create($this->_getIndexDir($name));
        $index = new ManipleCore_Search_Lucene_Index($lucene);
        $cache = $this->getCache();
        if ($cache) {
            $index->setCache($cache);
        }
        return $index;
    } // }}}
}
