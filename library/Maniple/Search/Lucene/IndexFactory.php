<?php

class Maniple_Search_Lucene_IndexFactory
    implements Maniple_Search_IndexFactoryInterface
{
    /**
     * Directory where Lucene indexes are stored.
     * @var string
     */
    protected $_storageDir;

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
     * @return Maniple_Search_IndexInterface
     * @throws Zend_Search_Lucene_Exception
     */
    public function getIndex($name) // {{{
    {
        $path = $this->_getIndexDir($name);

        try {
            $lucene = Zend_Search_Lucene::open($path);

        } catch (Zend_Search_Lucene_Exception $e) {
            // Lucene index was not found or is unreadable
        }

        if (empty($lucene)) {
            $lucene = Zend_Search_Lucene::create($path);
        }

        $index = new Maniple_Search_Lucene_Index($lucene);
        return $index;
    } // }}}
}
