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
     * @var string
     */
    protected $_idField = 'id';

    /**
     * @param  string $storageDir
     * @return Maniple_Search_IndexFactory
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
     * @return Maniple_Search_IndexFactory
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
     * @param  string $idField
     * @return Maniple_Search_Lucene_IndexFactory
     */
    public function setIdField($idField) // {{{
    {
        $this->_idField = (string) $idField;
        return $this;
    } // }}}

    /**
     * @return string
     */
    public function getIdField() // {{{
    {
        return $this->_idField;
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
     * @return Maniple_Search_IndexInterface|null
     */
    public function getIndex($name) // {{{
    {
        $index = null;

        try {
            $lucene = Zend_Search_Lucene::open($this->_getIndexDir($name));
            $index = new Maniple_Search_Lucene_Index($lucene, $this->_idField);
            $cache = $this->getCache();
            if ($cache) {
                // $index->setCache($cache);
            }
        } catch (Zend_Search_Lucene_Exception $e) {
            // index was not found or is unreadable
        }

        return $index;
    } // }}}

    /**
     * @param  string $name
     * @return Maniple_Search_IndexInterface
     */
    public function createIndex($name) // {{{
    {
        $lucene = Zend_Search_Lucene::create($this->_getIndexDir($name));
        $index = new Maniple_Search_Lucene_Index($lucene, $this->_idField);
        $cache = $this->getCache();
        if ($cache) {
            // $index->setCache($cache);
        }
        return $index;
    } // }}}
}
