<?php

class Maniple_Search_Document
    implements Maniple_Search_DocumentInterface
{
    const DATA_SIZE      = 5;

    const DATA_TOKENIZED = 0;
    const DATA_KEYWORDS  = 1;
    const DATA_BINARY    = 2;
    const DATA_UNSTORED  = 3;
    const DATA_UNINDEXED = 4;

    /**
     * @var array
     */
    protected $_data;

    /**
     * @param  int $index
     * @param  string $key
     * @param  string $value
     * @return Maniple_Search_Document
     */
    protected function _addData($index, $key, $value) // {{{
    {
        if ($index < 0 || $index >= self::DATA_SIZE) {
            throw new OutOfBoundsException('Invalid data index');
        }
        $this->_data[$index][(string) $key] = (string) $value;
        return $this;
    } // }}}

    /**
     * @param  int $index
     * @return array
     */
    protected function _getData($index) // {{{
    {
        if (isset($this->_data[$index])) {
            return $this->_data[$index];
        }
        return array();
    } // }}}

    public function addTokenized($key, $value) // {{{
    {
        return $this->_addData(self::DATA_TOKENIZED, $key, $value);
    } // }}}

    public function addKeyword($key, $value) // {{{
    {
        return $this->_addData(self::DATA_KEYWORDS, $key, $value);
    } // }}}

    public function addBinary($key, $value) // {{{
    {
        return $this->_addData(self::DATA_BINARY, $key, $value);
    } // }}}

    public function addUnstored($key, $value) // {{{
    {
        return $this->_addData(self::DATA_UNSTORED, $key, $value);
    } // }}}

    public function addUnindexed($key, $value) // {{{
    {
        return $this->_addData(self::DATA_UNINDEXED, $key, $value);
    } // }}}

    public function getTokenized() // {{{
    {
        return $this->_getData(self::DATA_TOKENIZED);
    } // }}}

    public function getKeywords() // {{{
    {
        return $this->_getData(self::DATA_KEYWORDS);
    } // }}}

    public function getBinary() // {{{
    {
        return $this->_getData(self::DATA_BINARY);
    } // }}}

    public function getUnstored() // {{{
    {
        return $this->_getData(self::DATA_UNSTORED);
    } // }}}

    public function getUnindexed() // {{{
    {
        return $this->_getData(self::DATA_UNINDEXED);
    } // }}}
}
