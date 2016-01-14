<?php

interface Maniple_StringUtils_StringLength_Adapter
{
    /**
     * @param string|null $encoding
     */
    public function setEncoding($encoding);

    /**
     * @return string|null
     */
    public function getEncoding();

    /**
     * @param $string
     * @return int
     */
    public function getLength($string);
}
