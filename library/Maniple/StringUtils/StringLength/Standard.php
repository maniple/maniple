<?php

/**
 * String length adapter that uses iconv library for length determination.
 */
class Maniple_StringUtils_StringLength_Standard implements Maniple_StringUtils_StringLength_Adapter
{
    /**
     * @var string
     */
    protected $_encoding;

    /**
     * @param null|string $encoding
     * @return $this
     */
    public function setEncoding($encoding)
    {
        $this->_encoding = $encoding;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * @param $string
     * @return int
     */
    public function getLength($string)
    {
        if ($this->_encoding !== null) {
            $length = iconv_strlen($string, $this->_encoding);
        } else {
            $length = iconv_strlen($string);
        }
        return $length;
    }
}
